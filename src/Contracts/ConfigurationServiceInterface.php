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
   * Gets the Auth0 default domain.
   *
   * @return string
   *   The Auth0 domain (e.g., 'example.auth0.com').
   */
  public function getDomain(): string;

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
   * @param bool $as_string
   *    Determine if the scopes should be returned as a string.
   *
   * @return array|string
   *   Array of scope strings or a comma-separated string.
   */
  public function getDefaultScopes(bool $as_string = FALSE): array|string;

  /**
   * Checks if verified email is required.
   *
   * @return bool
   *   TRUE if verified email is required, FALSE otherwise.
   */
  public function isRequiresVerifiedEmail(): bool;

  /**
   * Gets the username claim.
   *
   * @return string
   *   The username claim with default fallback.
   */
  public function getUsernameClaim(): string;

  /**
   * Gets the claim to use for role mapping.
   *
   * @return string|null
   *   The claim to use for role mapping or NULL if not configured.
   */
  public function getClaimToUseForRole(): ?string;

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
   * Gets the role mapping.
   *
   * @return string|null
   *   The role mapping or NULL if not configured.
   */
  public function getRoleMapping(): ?string;

  /**
   * Checks if role mapping syncing is enabled.
   *
   * @return bool
   *   Return TRUE if role mapping syncing is enabled, FALSE otherwise.
   */
  public function isSyncRoleMapping(): bool;

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
   * Gets the claim mapping.
   *
   * @return string|null
   *   The claim mapping or NULL if not configured.
   */
  public function getClaimMapping(): ?string;

  /**
   * Checks if claim mapping syncing is enabled.
   *
   * @return bool
   *   Return TRUE if claim mapping syncing is enabled, FALSE otherwise.
   */
  public function isSyncClaimMapping(): bool;

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

}
