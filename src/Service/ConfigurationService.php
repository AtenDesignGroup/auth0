<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Auth0 configuration service.
 *
 * Provides centralized access to all Auth0 module configuration values
 * with Key module integration for sensitive credentials.
 *
 * This service encapsulates all Auth0 configuration access and provides:
 * - Type-safe configuration getters
 * - Key module integration for secure credential storage
 * - Backward compatibility with direct configuration storage
 * - Configuration caching for improved performance
 * - Centralized configuration management
 *
 * Usage examples:
 *
 * @code
 * // Inject via dependency injection
 * public function __construct(ConfigurationServiceInterface $config_service) {
 *   $this->configService = $config_service;
 * }
 *
 * // Access configuration values
 * $domain = $this->configService->getDomain();
 * $clientId = $this->configService->getClientId();
 * $isSignupAllowed = $this->configService->isAllowSignup();
 *
 * // Generic access for custom configurations
 * $customValue = $this->configService->get('custom_setting', 'default');
 * @endcode
 *
 * Key Module Integration:
 * For sensitive credentials (client_secret, cookie_secret), the service
 * automatically checks for Key module entities first before falling back
 * to direct configuration storage. This provides enhanced security for
 * production environments.
 *
 * @see \Drupal\auth0\Contracts\ConfigurationServiceInterface
 * @see \Drupal\key\KeyRepositoryInterface
 */
class ConfigurationService implements ConfigurationServiceInterface {

  /**
   * The configuration object name.
   */
  public const string CONFIG_NAME = 'auth0.settings';

  /**
   * Default JWT signing algorithm.
   */
  private const string DEFAULT_JWT_ALGORITHM = 'RS256';

  /**
   * Default username claim.
   */
  private const string DEFAULT_USERNAME_CLAIM = 'nickname';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Cached configuration data.
   *
   * @var array|null
   */
  private ?array $configCache = NULL;

  /**
   * Constructs a new ConfigurationService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    KeyRepositoryInterface $key_repository,
    LoggerInterface $logger,
  ) {
    $this->configFactory = $config_factory;
    $this->keyRepository = $key_repository;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomain(): string {
    return $this->get('auth0_domain', '');
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomDomain(): ?string {
    return $this->get('auth0_custom_domain') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientId(): string {
    return $this->get('auth0_client_id', '');
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret(): string {
    // Check for Key module integration first.
    $key_id = $this->get('auth0_client_secret_key');
    if ($key_id && $key = $this->keyRepository->getKey($key_id)) {
      return $key->getKeyValue();
    }

    // Fallback to direct config for backward compatibility.
    $direct_value = $this->get('auth0_client_secret', '');
    if ($direct_value) {
      $this->logger->warning('Using client_secret from configuration. Consider using Key module for better security.');
    }

    return $direct_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieSecret(): string {
    // Check for Key module integration first.
    $key_id = $this->get('auth0_cookie_secret_key');
    if ($key_id && $key = $this->keyRepository->getKey($key_id)) {
      return $key->getKeyValue();
    }

    // Fallback to direct config for backward compatibility.
    $direct_value = $this->get('auth0_cookie_secret', '');
    if ($direct_value) {
      $this->logger->warning('Using cookie_secret from configuration. Consider using Key module for better security.');
    }

    return $direct_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecretKey(): ?string {
    return $this->get('auth0_client_secret_key');
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieSecretKey(): ?string {
    return $this->get('auth0_cookie_secret_key');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultScopes(): array {
    // Get scopes from constant or configuration.
    $scopes = defined('AUTH0_DEFAULT_SCOPES') ? AUTH0_DEFAULT_SCOPES : 'openid email profile';
    return explode(' ', trim($scopes));
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoutReturnUrl(): ?string {
    return $this->get('auth0_logout_return_url') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isOfflineAccess(): bool {
    return (bool) $this->get('auth0_allow_offline_access', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSecretBase64Encoded(): bool {
    return (bool) $this->get('auth0_secret_base64_encoded', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRedirectForSso(): bool {
    return (bool) $this->get('auth0_redirect_for_sso', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getJwtSigningAlgorithm(): string {
    return $this->get('auth0_jwt_signature_alg', self::DEFAULT_JWT_ALGORITHM);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormTitle(): string {
    return $this->get('auth0_form_title', '');
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowSignup(): bool {
    return (bool) $this->get('auth0_allow_signup', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRequiresVerifiedEmail(): bool {
    return (bool) $this->get('auth0_requires_verified_email', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isJoinUserByMailEnabled(): bool {
    return (bool) $this->get('auth0_join_user_by_mail_enabled', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsernameClaim(): string {
    return $this->get('auth0_username_claim', self::DEFAULT_USERNAME_CLAIM);
  }

  /**
   * {@inheritdoc}
   */
  public function getLoginCss(): ?string {
    return $this->get('auth0_login_css') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLockExtraSettings(): ?string {
    return $this->get('auth0_lock_extra_settings') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutoRegister(): bool {
    return (bool) $this->get('auth0_auto_register', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimMapping(): ?string {
    return $this->get('auth0_claim_mapping') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimToUseForRole(): ?string {
    return $this->get('auth0_claim_to_use_for_role') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleMapping(): ?string {
    return $this->get('auth0_role_mapping') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetCdn(): ?string {
    return $this->get('auth0_widget_cdn') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key, mixed $default = NULL): mixed {
    $config = $this->loadConfiguration();
    return $config[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(): array {
    return $this->loadConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, mixed $value): self {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    $config->set($key, $value)->save();

    // Clear cache since config has changed.
    $this->configCache = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $values): self {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);

    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();

    // Clear cache since config has changed.
    $this->configCache = NULL;

    return $this;
  }

  /**
   * Loads configuration data with simple caching.
   *
   * @return array
   *   The configuration data.
   */
  private function loadConfiguration(): array {
    if ($this->configCache === NULL) {
      $this->configCache = $this->configFactory->get(self::CONFIG_NAME)->get();
    }

    return $this->configCache;
  }

}
