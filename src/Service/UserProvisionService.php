<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\auth0\ValueObject\Auth0User;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\auth0\Exception\Auth0SecurityException;
use Drupal\auth0\Exception\Auth0ValidationException;
use Drupal\auth0\Contracts\UserProvisionServiceInterface;

/**
 * Define the Auth0 user provision service.
 */
class UserProvisionService implements UserProvisionServiceInterface {

  /** @var string */
  protected const string AUTH0_PROVIDER = 'auth0';

  /**
   * @param \Drupal\externalauth\Authmap $authmap
   *   The authmap service.
   * @param \Drupal\externalauth\ExternalAuth $externalAuth
   *   The external auth service.
   * @param \Drupal\auth0\Service\ConfigurationService $configurationService
   *   The Auth0 configuration service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel for Auth0 operations.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for role validation.
   */
  public function __construct(
    protected Authmap $authmap,
    protected ExternalAuth $externalAuth,
    protected ConfigurationService $configurationService,
    protected LoggerChannelInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function findUser(string $identifier): ?User {
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
    $name = $this->generateUsername($user);

    return $this->externalAuth->loginRegister(
      $userId,
      static::AUTH0_PROVIDER,
      [
        'name' => $name,
        'email' => $user->email(),
        'roles' => $this->mapAuth0Roles($user),
      ]
    );
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

    return [$this->getDefaultRole()];
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

  /**
   * Get the default role from configuration.
   *
   * @return string
   *   The default role ID.
   */
  private function getDefaultRole(): string {
    return $this->configurationService->getDefaultRole();
  }

}
