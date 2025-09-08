<?php

declare(strict_types=1);

namespace Drupal\auth0\Contracts;

/**
 * Interface for Auth0 configuration service.
 */
interface ConfigurationServiceInterface {

  /**
   * Get the Auth0 redirect URI.
   *
   * @return string
   */
  public function redirectUri(): string;

  /**
   * Resolve the Auth0 custom or default domain.
   *
   * @return string
   */
  public function resolveDomain(): string;

  /**
   * Gets the Auth0 default domain.
   *
   * @return string
   *   The Auth0 domain (e.g., 'example.auth0.com').
   */
  public function getDomain(): string;

  /**
   * Gets the Auth0 custom domain.
   *
   * @return string|null
   *   The Auth0 custom domain or NULL if not configured.
   */
  public function getCustomDomain(): ?string;

  /**
   * Gets the Auth0 domain tenant CDN.
   *
   * @return string
   */
  public function getDomainTenantCdn(): string;

  /**
   * Gets the Auth0 client ID.
   *
   * @return string
   *   The Auth0 client ID.
   */
  public function getClientId(): string;

  /**
   * Gets the Auth0 client secret.
   *
   * Attempts to load from Key module first, falls back to direct config.
   *
   * @return string
   *   The Auth0 client secret.
   */
  public function getClientSecret(): string;

  /**
   * Gets the Auth0 client secret Key ID.
   *
   * @return string|null
   *   The client secret Key ID or NULL if not configured.
   */
  public function getClientSecretKey(): ?string;

  /**
   * Gets the Auth0 cookie secret.
   *
   * Attempts to load from Key module first, falls back to direct config.
   *
   * @return string
   *   The Auth0 cookie secret.
   */
  public function getCookieSecret(): string;

  /**
   * Gets the Auth0 cookie secret Key ID.
   *
   * @return string|null
   *   The cookie secret Key ID or NULL if not configured.
   */
  public function getCookieSecretKey(): ?string;

  /**
   * Gets the default Auth0 scopes as an array.
   *
   * @return array
   *   Array of scope strings.
   */
  public function getDefaultScopes(): array;

  /**
   * Gets the logout return URL.
   *
   * @return string|null
   *   The logout returns URL or NULL if not configured.
   */
  public function getLogoutReturnUrl(): ?string;

  /**
   * Checks if offline access is enabled.
   *
   * @return bool
   *   TRUE if offline access is enabled, FALSE otherwise.
   */
  public function isOfflineAccess(): bool;

  /**
   * Checks if the secret is base64 encoded.
   *
   * @return bool
   *   TRUE if the secret is base64 encoded, FALSE otherwise.
   */
  public function getSecretBase64Encoded(): bool;

  /**
   * Checks if redirect for SSO is enabled.
   *
   * @return bool
   *   TRUE if redirect for SSO is enabled, FALSE otherwise.
   */
  public function isRedirectForSso(): bool;

  /**
   * Gets the JWT signing algorithm.
   *
   * @return string
   *   The JWT signing algorithm with default fallback.
   */
  public function getJwtSigningAlgorithm(): string;

  /**
   * Gets the form title.
   *
   * @return string
   *   The Auth0 form title.
   */
  public function getFormTitle(): string;

  /**
   * Checks if user signup is allowed.
   *
   * @return bool
   *   TRUE if a user signup is allowed, FALSE otherwise.
   */
  public function isAllowSignup(): bool;

  /**
   * Checks if verified email is required.
   *
   * @return bool
   *   TRUE if verified email is required, FALSE otherwise.
   */
  public function isRequiresVerifiedEmail(): bool;

  /**
   * Checks if joining users by email is enabled.
   *
   * @return bool
   *   TRUE if joining users by email is enabled, FALSE otherwise.
   */
  public function isJoinUserByMailEnabled(): bool;

  /**
   * Gets the username claim.
   *
   * @return string
   *   The username claim with default fallback.
   */
  public function getUsernameClaim(): string;

  /**
   * Gets the login CSS.
   *
   * @return string|null
   *   The login CSS or NULL if not configured.
   */
  public function getLoginCss(): ?string;

  /**
   * Gets the Lock extra settings.
   *
   * @return string|null
   *   The Lock extra settings or NULL if not configured.
   */
  public function getLockExtraSettings(): ?string;

  /**
   * Checks if auto register is enabled.
   *
   * @return bool
   *   TRUE if auto register is enabled, FALSE otherwise.
   */
  public function isAutoRegister(): bool;

  /**
   * Gets the claim mapping.
   *
   * @return string|null
   *   The claim mapping or NULL if not configured.
   */
  public function getClaimMapping(): ?string;

  /**
   * Gets the claim to use for role mapping.
   *
   * @return string|null
   *   The claim to use for role mapping or NULL if not configured.
   */
  public function getClaimToUseForRole(): ?string;

  /**
   * Gets the role mapping.
   *
   * @return string|null
   *   The role mapping or NULL if not configured.
   */
  public function getRoleMapping(): ?string;

  /**
   * Gets the widget CDN URL.
   *
   * @return string|null
   *   The widget CDN URL or NULL if not configured.
   */
  public function getWidgetCdn(): ?string;

  /**
   * Gets a configuration value by key.
   *
   * @param string $key
   *   The configuration key.
   * @param mixed $default
   *   The default value to return if the key is not found.
   *
   * @return mixed
   *   The configuration value or the default.
   */
  public function get(string $key, mixed $default = NULL): mixed;

  /**
   * Gets all Auth0 configuration values.
   *
   * @return array
   *   Array of all configuration values.
   */
  public function getAll(): array;

  /**
   * Sets a configuration value.
   *
   * @param string $key
   *   The configuration key.
   * @param mixed $value
   *   The configuration value.
   *
   * @return $this
   *   The configuration service instance for method chaining.
   */
  public function set(string $key, mixed $value): self;

  /**
   * Sets multiple configuration values.
   *
   * @param array $values
   *   Array of key-value pairs to set.
   *
   * @return $this
   *   The configuration service instance for method chaining.
   */
  public function setMultiple(array $values): self;

  /**
   * Gets the role mapping rules configuration.
   *
   * Returns an array of mapping rules that define how Auth0 claims and roles
   * are mapped to Drupal roles.
   *
   * @return array
   *   An associative array where keys are Auth0 role/claim identifiers
   *   and values are arrays of Drupal role machine names.
   */
  public function getRoleMappingRules(): array;

  /**
   * Gets the profile field mapping rules from configuration.
   *
   * Parses the 'auth0_profile_field_mapping' configuration value which should
   * contain pipe-delimited mappings in the format:
   * "auth0_claim|drupal_field_name" (one per line).
   *
   * @return array<string, array<string>>
   *   An associative array where keys are Auth0 claim names
   *   and values are arrays of Drupal field machine names.
   */
  public function getProfileFieldMappingRules(): array;

  /**
   * Gets the default role to assign when no mapping rules match.
   *
   * @return string
   *   The default Drupal role machine name.
   */
  public function getDefaultRole(): string;

}
