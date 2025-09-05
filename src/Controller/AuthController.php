<?php

declare(strict_types=1);

namespace Drupal\auth0\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\auth0\Exception\AuthenticationLoginException;
use Drupal\auth0\Contracts\AuthenticationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the Auth0 authentication controller.
 */
class AuthController extends ControllerBase {

  /**
   * Define the Auth0 controller constructor.
   *
   * @param \Drupal\auth0\Contracts\AuthenticationServiceInterface $authenticationService
   *   The Auth0 authentication service.
   */
  public function __construct(
    protected LoggerChannelInterface $logger,
    protected AuthenticationServiceInterface $authenticationService
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.auth0'),
      $container->get('auth0.authentication')
    );
  }

  /**
   * Handles the Auth0 login callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The redirect response.
   */
  public function callback(Request $request): Response {
    try {
      return $this->authenticationService->handleLogin(
        $request
      );
    }
    catch (AuthenticationLoginException $exception) {
      $this->logger->error($exception->getMessage());

      if ($error_message = $exception->errorMessage()) {
        $this->messenger()->addError($error_message);
      }

      return new TrustedRedirectResponse(
        $exception->getRedirectUrl()
      );
    }
  }

  /**
   * Handles the Auth0 login page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return array|\Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response or renderable array.
   */
  public function login(Request $request): array|Response {
    return $this->authenticationService->handleLoginPage($request);
  }

  /**
   * Handles the Auth0 logout page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The response after logout.
   */
  public function logout(Request $request): Response {
    return $this->authenticationService->handleLogout($request);
  }

}
