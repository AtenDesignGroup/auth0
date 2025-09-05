<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\auth0\ValueObject\Auth0User;
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
   */
  public function __construct(
    protected Authmap $authmap,
    protected ExternalAuth $externalAuth,
    protected ConfigurationService $configurationService,
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

}
