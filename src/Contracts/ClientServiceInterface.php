<?php

declare(strict_types=1);

namespace Drupal\auth0\Contracts;

use Auth0\SDK\Contract\API\ManagementInterface;
use Drupal\auth0\ValueObject\Auth0User;

/**
 * Define the Auth0 client service interface.
 */
interface ClientServiceInterface {

  /**
   * Get the Auth0 login URL.
   *
   * @param string|NULL $return_to
   *   The authentication redirect URL.
   *
   * @return string
   *   The Auth0 login URL.
   *
   * @throws \Auth0\SDK\Exception\ConfigurationException
   */
  public function loginUrl(
    string $return_to = NULL
  ): string;

  /**
   * Get the Auth0 logout URL.
   *
   * @param string|NULL $return_to
   *   The authentication redirect URL.
   *
   * @return string
   *  The Auth0 log-out URL.
   *
   * @throws \Auth0\SDK\Exception\ConfigurationException
   */
  public function logoutUrl(
    string $return_to = NULL
  ): string;

  /**
   * Exchange the Auth0 access token for an Auth0 user.
   *
   * @return \Drupal\auth0\ValueObject\Auth0User|null
   *   The Auth0 user.
   *
   * @throws \Drupal\auth0\Exception\AuthenticationLoginException
   */
  public function exchange(): ?Auth0User;

  /**
   * Request a password reset email for an Auth0 user.
   *
   * Triggers Auth0's Authentication API to send a password reset email
   * to the user. Works only for database connections.
   *
   * @param string $email
   *   The user's email address.
   *
   * @return bool
   *   TRUE if the request was successful, FALSE otherwise.
   */
  public function requestPasswordReset(string $email): bool;

  /**
   * Get the Auth0 Management API client.
   *
   * @return \Auth0\SDK\Contract\API\ManagementInterface
   *   The Management API client for user operations.
   *
   * @throws \Auth0\SDK\Exception\ConfigurationException
   */
  public function management(): ManagementInterface;

}
