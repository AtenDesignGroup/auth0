<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\auth0\Contracts\ClientServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\auth0\Exception\AuthenticationLoginException;
use Drupal\auth0\Contracts\UserProvisionServiceInterface;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Drupal\auth0\Contracts\AuthenticationServiceInterface;

/**
 * Define the Auth0 authentication service.
 */
class AuthenticationService implements AuthenticationServiceInterface {

  /**
   * The Auth0 authentication service constructor.
   *
   * @param \Drupal\auth0\Service\ClientService $clientService
   *   The Auth0 client service.
   * @param \Drupal\auth0\Contracts\UserProvisionServiceInterface $userProvisionService
   *   The Auth0 user provision service.
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configurationService
   *   The Auth0 configuration service.
   */
  public function __construct(
    protected ClientServiceInterface $clientService,
    protected UserProvisionServiceInterface $userProvisionService,
    protected ConfigurationServiceInterface $configurationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handleLogin(
    Request $request
  ): Response {
    $this->validateLogin($request);

    if ($auth0User = $this->clientService->exchange()) {
      $this->userProvisionService->login($auth0User);
    }

    return new RedirectResponse('/user');
  }

  /**
   * {@inheritdoc}
   */
  public function handleLogout(Request $request): Response {
    try {
      user_logout();

      $return_to = $request->get(
        'returnTo',
        $request->getSchemeAndHttpHost()
      );

      return new TrustedRedirectResponse(
        $this->clientService->logoutUrl($return_to)
      );
    }
    catch (\Exception) {
      return new RedirectResponse('/');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleLoginPage(Request $request): array|Response {
    try {
      return new TrustedRedirectResponse(
        $this->clientService->loginUrl()
      );
    }
    catch (\Exception) {
      return new RedirectResponse('/');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): string {
    return $this->clientService->transientStoreHandler()->issue('state');
  }

  /**
   * {@inheritdoc}
   */
  public function getNonce(): string {
    return $this->clientService->transientStoreHandler()->getNonce();
  }

  /**
   * Validate the Auth0 login request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @throws \Drupal\auth0\Exception\AuthenticationLoginException
   */
  protected function validateLogin(Request $request): void {
    if ($error_code = $request->get('error')) {
      if (in_array($error_code, [
        'login_required',
        'consent_required',
        'interaction_required',
      ], TRUE)
      ) {
        throw new AuthenticationLoginException($error_code);
      }
      $error_description = $this->request->get('error_description')
        ?? t('An error occurred during login.');

      throw new AuthenticationLoginException((string) $error_description);
    }
  }


}
