<?php

declare(strict_types=1);

namespace Drupal\auth0\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\auth0\Contracts\ClientServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles Auth0 password reset requests.
 */
class PasswordResetController extends ControllerBase {

  /**
   * Constructs a PasswordResetController.
   *
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configurationService
   *   The Auth0 configuration service.
   * @param \Drupal\auth0\Contracts\ClientServiceInterface $clientService
   *   The Auth0 client service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected ConfigurationServiceInterface $configurationService,
    protected ClientServiceInterface $clientService,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auth0.configuration'),
      $container->get('auth0.client'),
      $container->get('logger.channel.auth0'),
    );
  }

  /**
   * Requests a password reset email from Auth0.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the user edit form with a status message.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function requestPasswordReset(): RedirectResponse {
    $currentUser = $this->currentUser();
    $uid = $currentUser->id();

    if (!$this->configurationService->isPasswordResetEnabled()) {
      $this->messenger()->addError(
        $this->t('Password reset via Auth0 is not enabled.')
      );
      return $this->redirect('entity.user.edit_form', ['user' => $uid]);
    }
    $user = $this->entityTypeManager()->getStorage('user')->load($uid);

    if (!$user) {
      $this->messenger()->addError(
        $this->t('Unable to load a user account.')
      );
      return $this->redirect('entity.user.edit_form', ['user' => $uid]);
    }

    $userEmail = $user->getEmail();

    if (empty($userEmail)) {
      $this->messenger()->addError(
        $this->t('Your account does not have an email address.')
      );
      return $this->redirect('entity.user.edit_form', ['user' => $uid]);
    }

    try {
      $success = $this->clientService->requestPasswordReset($userEmail);

      if ($success) {
        $this->logger->info(
          'Auth0 password reset requested for user @email',
          ['@email' => $userEmail]
        );

        $this->messenger()->addStatus(
          $this->t(
            'A password-reset email has been sent to your email address. Please
            check your inbox.'
          )
        );
      }
      else {
        $this->messenger()->addError(
          $this->t(
            'Failed to send password reset email. Please try again later or
            contact support.'
          )
        );
      }
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Failed to request Auth0 password reset: @message',
        ['@message' => $e->getMessage()]
      );

      $this->messenger()->addError(
        $this->t(
          'Failed to send password reset email. Please try again later or
          contact support.'
        )
      );
    }

    return $this->redirect('entity.user.edit_form', ['user' => $uid]);
  }

}
