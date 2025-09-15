<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Drupal\user\UserInterface;
use Drupal\auth0\ValueObject\Auth0User;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\auth0\Exception\Auth0SecurityException;
use Drupal\auth0\Exception\Auth0ValidationException;
use Drupal\auth0\Contracts\UserProvisionServiceInterface;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;

/**
 * Define the Auth0 user provision service.
 */
class UserProvisionService implements UserProvisionServiceInterface {

  /** @var string */
  protected const string AUTH0_PROVIDER = 'auth0';

  /**
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   The external auth service.
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configurationService
   *   The Auth0 configuration service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel for Auth0 operations.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for role validation.
   */
  public function __construct(
    protected ExternalAuthInterface $externalAuth,
    protected ConfigurationServiceInterface $configurationService,
    protected LoggerChannelInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function findUser(string $identifier): ?UserInterface {
    return $this->externalAuth->load(
      $identifier,
      static::AUTH0_PROVIDER
    );
  }

  /**
   * {@inheritdoc}
   */
  public function login(Auth0User $user): ?UserInterface {
    $userId = $user->userId();
    if ($userId === NULL) {
      return NULL;
    }
    $account = $this->externalAuth->login(
      $userId,
      static::AUTH0_PROVIDER
    );

    if ($account instanceof UserInterface) {
      $rolesSynced = $this->syncAccountRoles(
        $account, $user
      );
      $fieldsSynced = $this->syncAccountProfileFields(
        $account, $user
      );

      if ($rolesSynced || $fieldsSynced) {
        $account->save();
      }

      return $account;
    }
    $name = $this->generateUsername($user);

    $account = $this->externalAuth->register(
      $userId,
      static::AUTH0_PROVIDER,
      [
        'name' => $name,
        'email' => $user->email(),
        'roles' => $this->mapAuth0Roles($user),
        ...$this->mapAuth0ProfileFields($user),
      ]);

    return $this->externalAuth->userLoginFinalize(
      $account,
      $userId,
      static::AUTH0_PROVIDER
    );
  }

  /**
   * Map Auth0 user claims to Drupal profile fields.
   *
   * @param \Drupal\auth0\ValueObject\Auth0User $auth0User
   *   The Auth0 user object.
   *
   * @return array
   *   Associative array of Drupal field names mapped to claim values.
   */
  public function mapAuth0ProfileFields(Auth0User $auth0User): array {
    $mappedFields = [];
    $mappingRules = $this->configurationService->getProfileFieldMappingRules();

    if (empty($mappingRules)) {
      return [];
    }

    foreach ($mappingRules as $auth0Claim => $drupalField) {
      $claimValue = $auth0User->get($auth0Claim);

      if (empty($claimValue)) {
        continue;
      }
      $mappedFields[$drupalField] = $claimValue;
    }

    return $mappedFields;
  }

  /**
   * Sync account roles.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param \Drupal\auth0\ValueObject\Auth0User $user
   *   The Auth0 user object.
   *
   * @return bool
   */
  protected function syncAccountRoles(
    UserInterface $account,
    Auth0User $user
  ): bool {
    if (
      !empty($this->configurationService->getRoleMapping())
      && $this->configurationService->isSyncRoleMapping()
    ) {
      $account->set(
        'roles',
        $this->mapAuth0Roles($user)
      );
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Sync account profile fields.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param \Drupal\auth0\ValueObject\Auth0User $user
   *   The Auth0 user object.
   *
   * @return bool
   */
  protected function syncAccountProfileFields(
    UserInterface $account,
    Auth0User $user
  ): bool {
    if (
      !empty($this->configurationService->getClaimMapping())
      && $this->configurationService->isSyncClaimMapping()
    ) {
      foreach ($this->mapAuth0ProfileFields($user) as $field => $value) {
        if (in_array($field, $this->restrictedProfileFields(), TRUE)) {
          continue;
        }
        $account->set($field, $value);
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Define the list of restricted profile fields.
   *
   * @return string[]
   */
  protected function restrictedProfileFields(): array {
    return [
      'uid',
      'init',
      'name',
      'uuid',
      'pass',
      'roles',
      'status',
    ];
  }

  /**
   * Get the username from the Auth0 user.
   */
  protected function generateUsername(
    Auth0User $user
  ): string {
    $username_claim = $this->configurationService->getUsernameClaim();
    return $user->get($username_claim);
  }

  /**
   * Map Auth0 user roles and claims to Drupal roles.
   *
   * @param \Drupal\auth0\ValueObject\Auth0User $auth0User
   *   The Auth0 user object.
   *
   * @return array
   *   Array of Drupal role IDs.
   */
  protected function mapAuth0Roles(Auth0User $auth0User): array {
    if ($mappingRules = $this->configurationService->getRoleMappingRules()) {
      return $this->mapUserRoles($auth0User, $mappingRules);
    }

    return [];
  }

  /**
   * Map Auth0 user roles to Drupal roles.
   *
   * @param \Drupal\auth0\ValueObject\Auth0User $auth0User
   *   The Auth0 user object.
   * @param array $mappingRules
   *   The role mapping rules.
   * @param array $mappedRoles
   *   The current mapped roles array.
   *
   * @return array
   *   Updated mapped roles array.
   */
  protected function mapUserRoles(
    Auth0User $auth0User,
    array $mappingRules,
    array $mappedRoles = []
  ): array {
    $auth0Roles = $auth0User->get('roles') ?? [];

    foreach ($auth0Roles as $auth0Role) {
      if (!isset($mappingRules[$auth0Role])) {
        continue;
      }
      $mappedRoles += $mappingRules[$auth0Role];
    }

    return array_unique($mappedRoles);
  }

}
