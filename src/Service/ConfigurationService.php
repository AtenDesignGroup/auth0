<?php

declare(strict_types=1);

namespace Drupal\auth0\Service;

use Psr\Log\LoggerInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;

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
  protected const string DEFAULT_JWT_ALGORITHM = 'RS256';

  /**
   * Default username claim.
   */
  protected const string DEFAULT_USERNAME_CLAIM = 'nickname';

  /**
   * Default authentication scopes.
   */
  protected const string AUTH0_DEFAULT_SCOPES = 'openid email profile';

  /**
   * The Drupal request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

  /**
   * Cached configuration data.
   *
   * @var array|null
   */
  private ?array $configCache = NULL;

  /**
   * Constructs a new ConfigurationService.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    RequestStack $request_stack,
    protected ConfigFactoryInterface $configFactory,
    protected KeyRepositoryInterface $keyRepository,
    protected LoggerInterface $logger,
  ) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function resolveDomain(): string {
    return $this->getCustomDomain() ?: $this->getDomain();
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
  public function getDomainTenantCdn(): string {
    $domain = $this->getDomain();

    if (preg_match('/\.([^.]+)\.auth0\.com$/', $domain, $matches)) {
      $region = $matches[1];
      return $region === 'us'
        ? 'https://cdn.auth0.com'
        : "https://cdn.$region.auth0.com";
    }

    return 'https://cdn.auth0.com';
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
    $key_id = $this->get('auth0_client_secret_key');

    if ($key_id && $key = $this->keyRepository->getKey($key_id)) {
      return $key->getKeyValue();
    }

    $direct_value = $this->get('auth0_client_secret', '');

    if ($direct_value) {
      $this->logger->warning(
        'Using client_secret from configuration. Consider using Key module for better security.'
      );
    }

    return $direct_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieSecret(): string {
    $key_id = $this->get('auth0_cookie_secret_key');

    if ($key_id && $key = $this->keyRepository->getKey($key_id)) {
      return $key->getKeyValue();
    }
    $direct_value = $this->get('auth0_cookie_secret', '');

    if ($direct_value) {
      $this->logger->warning(
        'Using cookie_secret from configuration. Consider using Key module for better security.'
      );
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
    return explode(' ', trim(static::AUTH0_DEFAULT_SCOPES));
  }

  /**
   * {@inheritdoc}
   */
  public function redirectUri(): string {
    return "{$this->request->getSchemeAndHttpHost()}/auth0/callback";
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
    return $this->get('auth0_jwt_signature_alg', static::DEFAULT_JWT_ALGORITHM);
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
    return $this->get('auth0_username_claim', static::DEFAULT_USERNAME_CLAIM);
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
  public function getLockExtraSettings(): string {
    return $this->get('auth0_lock_extra_settings', '{}');
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
    $config = $this->getAll();

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
  public function getRoleMappingRules(): array {
    $mapping = $this->get('auth0_role_mapping', '');

    // Parse pipe-delimited string format
    if (is_string($mapping) && !empty($mapping)) {
      $parsedRules = [];
      $lines = array_filter(explode("\n", trim($mapping)));

      foreach ($lines as $line) {
        $parts = explode('|', trim($line), 2);
        if (count($parts) === 2) {
          $auth0Role = trim($parts[0]);
          $drupalRole = trim($parts[1]);
          if (!empty($auth0Role) && !empty($drupalRole)) {
            $parsedRules[$auth0Role] = [$drupalRole];
          }
        }
      }

      return $parsedRules;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRole(): string {
    return $this->get('default_role', 'authenticated');
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
