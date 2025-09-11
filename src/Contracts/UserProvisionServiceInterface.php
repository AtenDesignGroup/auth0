<?php

declare(strict_types=1);
namespace Drupal\auth0\Contracts;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\auth0\ValueObject\Auth0User;

/**
 * Define the Auth0 user provision service interface.
 */
interface UserProvisionServiceInterface {

  /**
   * Find the Drupal user by Auth0 identifier.
   *
   * @param string $identifier
   *   The Auth0 identifier.
   *
   * @return \Drupal\user\Entity\User|null
   *   The Drupal user entity.
   */
  public function findUser(string $identifier): ?User;

  /**
   * Authenticates Auth0 user and manages the login process.
   *
   * @param Auth0User $user
   *   The user instance has to be authenticated and logged in.
   *
   * @return \Drupal\user\UserInterface|null
   *   The Drupal user entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\externalauth\Exception\ExternalAuthRegisterException
   */
  public function login(Auth0User $user): ?UserInterface;

}
