<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Auth0\SDK\Auth0;
use Auth0\SDK\Store\SessionStore;
use Auth0\SDK\Contract\StoreInterface;
use Drupal\auth0\ValueObject\Auth0User;
use Auth0\SDK\Utility\TransientStoreHandler;
use Auth0\SDK\Configuration\SdkConfiguration;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\auth0\Contracts\ClientServiceInterface;
use Drupal\auth0\Exception\AuthenticationLoginException;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;

class ClientService implements ClientServiceInterface {

  /** @var \Auth0\SDK\Auth0 */
  protected Auth0 $client;

  /** @var \Symfony\Component\HttpFoundation\Request|null */
  protected ?Request $request = NULL;

  /**
   * The client service constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory.
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configurationService
   *   The Auth0 configuration service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(
    RequestStack $requestStack,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected ConfigurationServiceInterface $configurationService,
    protected LoggerChannelInterface $logger
  ) {
    $this->request = $requestStack->getCurrentRequest();
    $configuration = $this->configuration();
    $configuration->setTransientStorage(
      new SessionStore($configuration)
    );
    $this->client = new Auth0($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function loginUrl(
    string $return_to = NULL
  ): string {
    return $this->client->login($return_to);
  }

  /**
   * {@inheritdoc}
   */
  public function logoutUrl(
    string $return_to = NULL
  ): string {
    return $this->client->logout($return_to);
  }

  /**
   * {@inheritdoc}
   */
  public function exchange(): ?Auth0User {
    try {
      $this->client->exchange();

      if ($userInfo = $this->getUserInfo()) {
        $this->validateTokenSubject($userInfo['sub']);

        return Auth0User::make(
          $userInfo,
          $this->client->getRefreshToken()
        );
      }
    }
    catch (\Exception $exception) {
      $this->client->logout();
      throw new AuthenticationLoginException(
        $exception->getMessage()
      );
    }

    return NULL;
  }

  /**
   * @return \Auth0\SDK\Utility\TransientStoreHandler
   */
  public function transientStoreHandler(): TransientStoreHandler {
    return new TransientStoreHandler($this->transientStorage());
  }

  /**
   * Define the Auth0 configuration
   *
   * @return \Auth0\SDK\Configuration\SdkConfiguration|array
   */
  protected function configuration(): SdkConfiguration|array {
    try {
      return new SdkConfiguration([
        'scope' => $this->configurationService->getDefaultScopes(),
        'domain' => $this->configurationService->getDomain(),
        'clientId' => $this->configurationService->getClientId(),
        'clientSecret' => $this->configurationService->getClientSecret(),
        'cookieSecret' => $this->configurationService->getCookieSecret(),
        'redirectUri' => $this->configurationService->redirectUri(),
      ]);
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      return [];
    }
  }

  /**
   * Get the Auth0 user info.
   *
   * @return array
   */
  protected function getUserInfo(): array {
    $userInfo = $this->client->getUser();
    $userInfo['sub'] = $userInfo['sub'] ?? $userInfo['user_id'] ?? NULL;
    $userInfo['user_id'] = $userInfo['user_id'] ?? $userInfo['sub'] ?? NULL;
    $userInfo['roles'] = $this->getUserRoles($userInfo['user_id']);
    return $userInfo;
  }

  /**
   * @param string $userId
   *
   * @return array
   */
  protected function getUserRoles(string $userId): array {
    try {
      $management = $this->client->management();

      $response = $management->users()->getRoles($userId);

      $rolesData = json_decode(
        $response->getBody()->getContents(),
        TRUE,
        512,
        JSON_THROW_ON_ERROR
      ) ?? [];

      $roles = [];

      foreach ($rolesData as $role) {
        if (!isset($role['name'])) {
          continue;
        }
        $roles[] = $this->toSnakeCase($role['name']);
      }

      return $roles;
    }
    catch (\Exception $exception) {
      $this->logger->error(
        'Failed to fetch user roles from Auth0: @message',
        ['@message' => $exception->getMessage()]
      );
      return [];
    }
  }

  /**
   * Validate the user token subject.
   *
   * @throws \Drupal\auth0\Exception\AuthenticationLoginException
   * @throws \Auth0\SDK\Exception\InvalidTokenException
   */
  protected function validateTokenSubject(
    string $user_subject
  ): void {
    $idToken = $this->client->getIdToken();
    $userToken = $this->client->decode($idToken);

    if ($user_subject !== $userToken->getSubject()) {
      throw new AuthenticationLoginException(
        'Failed to validate the user token subject.'
      );
    }
  }

  /**
   * @return \Auth0\SDK\Contract\StoreInterface
   */
  protected function transientStorage(): StoreInterface {
    return new SessionStore($this->configuration());
  }

  /**
   * Converts a given string to snake_case format.
   *
   * @param string $string
   *   The input string to be converted.
   *
   * @return string
   *   The converted string in snake_case format.
   */
  protected function toSnakeCase(string $string): string {
    return strtolower(str_replace(' ', '_', $string));
  }

}
