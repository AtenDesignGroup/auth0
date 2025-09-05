<?php

declare(strict_types=1);

namespace Drupal\auth0\Contracts;

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

}
