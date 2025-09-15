<?php

declare(strict_types=1);

namespace Drupal\auth0\Contracts;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define the Auth0 authentication service interface.
 */
interface AuthenticationServiceInterface {

  /**
   * Handle the Auth0 login request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return a redirect response.
   *
   * @throws \Drupal\auth0\Exception\AuthenticationLoginException
   */
  public function handleLogin(Request $request): Response;

  /**
   * Handle the Auth0 logout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *    Return a trusted redirect response.
   */
  public function handleLogout(Request $request): TrustedRedirectResponse;

  /**
   * Handle the Auth0 login page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   Return an array or a redirect response.
   */
  public function handleLoginPage(Request $request): array|Response;

  /**
   * @return string
   */
  public function getNonce(): string;

  /**
   * @return string
   */
  public function getState(): string;

}
