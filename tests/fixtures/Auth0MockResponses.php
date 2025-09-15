<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Fixtures;

/**
 * Provides mock Auth0 API responses for testing.
 */
class Auth0MockResponses {

  /**
   * Mock successful user profile response.
   */
  public static function successfulUserProfile(): array {
    return [
      'sub' => 'auth0|507f1f77bcf86cd799439011',
      'name' => 'John Doe',
      'given_name' => 'John',
      'family_name' => 'Doe', 
      'middle_name' => '',
      'nickname' => 'johndoe',
      'preferred_username' => 'johndoe',
      'profile' => 'https://auth0.example.com/users/507f1f77bcf86cd799439011',
      'picture' => 'https://s.gravatar.com/avatar/123',
      'website' => 'https://johndoe.com',
      'email' => 'john.doe@example.com',
      'email_verified' => true,
      'gender' => 'male',
      'birthdate' => '1990-01-01',
      'zoneinfo' => 'America/New_York',
      'locale' => 'en-US',
      'phone_number' => '+1 555-123-4567',
      'phone_number_verified' => true,
      'address' => [
        'country' => 'US',
      ],
      'updated_at' => '2023-01-01T00:00:00.000Z',
      'custom_claims' => [
        'department' => 'Engineering',
        'role' => 'Senior Developer',
      ],
    ];
  }

  /**
   * Mock user profile with minimal required fields.
   */
  public static function minimalUserProfile(): array {
    return [
      'sub' => 'auth0|minimal123',
      'email' => 'minimal@example.com',
      'email_verified' => true,
    ];
  }

  /**
   * Mock user profile with unverified email.
   */
  public static function unverifiedEmailProfile(): array {
    return [
      'sub' => 'auth0|unverified123',
      'email' => 'unverified@example.com',
      'email_verified' => false,
      'name' => 'Unverified User',
    ];
  }

  /**
   * Mock JWT token payload.
   */
  public static function jwtTokenPayload(): array {
    return [
      'iss' => 'https://auth0.example.com/',
      'sub' => 'auth0|507f1f77bcf86cd799439011',
      'aud' => ['your_client_id'],
      'iat' => time(),
      'exp' => time() + 3600,
      'azp' => 'your_client_id',
      'scope' => 'openid profile email',
      'gty' => 'password',
    ];
  }

  /**
   * Mock expired JWT token payload.
   */
  public static function expiredJwtTokenPayload(): array {
    return [
      'iss' => 'https://auth0.example.com/',
      'sub' => 'auth0|507f1f77bcf86cd799439011',
      'aud' => ['your_client_id'],
      'iat' => time() - 7200,
      'exp' => time() - 3600, // Expired 1 hour ago
      'azp' => 'your_client_id',
      'scope' => 'openid profile email',
    ];
  }

  /**
   * Mock Auth0 Management API client configuration response.
   */
  public static function clientConfiguration(): array {
    return [
      'name' => 'Test Application',
      'client_id' => 'test_client_id_123',
      'client_secret' => 'test_client_secret_456',
      'domain' => 'auth0.example.com',
      'is_first_party' => true,
      'oidc_conformant' => true,
      'callbacks' => [
        'https://example.com/auth0/callback',
      ],
      'allowed_logout_urls' => [
        'https://example.com/',
      ],
      'allowed_origins' => [
        'https://example.com',
      ],
      'grant_types' => [
        'authorization_code',
        'refresh_token',
      ],
      'app_type' => 'regular_web',
    ];
  }

  /**
   * Mock successful token exchange response.
   */
  public static function tokenExchangeResponse(): array {
    return [
      'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IktpZEtleSJ9.access_token_payload',
      'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IktpZEtleSJ9.id_token_payload',
      'refresh_token' => 'refresh_token_value',
      'token_type' => 'Bearer',
      'expires_in' => 3600,
      'scope' => 'openid profile email',
    ];
  }

  /**
   * Mock Auth0 authorization URL.
   */
  public static function authorizationUrl(): string {
    return 'https://auth0.example.com/authorize?' . http_build_query([
      'client_id' => 'test_client_id_123',
      'response_type' => 'code',
      'redirect_uri' => 'https://example.com/auth0/callback',
      'scope' => 'openid profile email',
      'state' => 'random_state_value',
      'nonce' => 'random_nonce_value',
    ]);
  }

  /**
   * Mock Auth0 logout URL.
   */
  public static function logoutUrl(string $returnTo = 'https://example.com'): string {
    return 'https://auth0.example.com/v2/logout?' . http_build_query([
      'client_id' => 'test_client_id_123',
      'returnTo' => $returnTo,
    ]);
  }

  /**
   * Mock Auth0 error responses.
   */
  public static function errorResponses(): array {
    return [
      'invalid_request' => [
        'error' => 'invalid_request',
        'error_description' => 'The request is missing a required parameter.',
      ],
      'unauthorized_client' => [
        'error' => 'unauthorized_client',
        'error_description' => 'Client authentication failed.',
      ],
      'access_denied' => [
        'error' => 'access_denied',
        'error_description' => 'The user denied the request.',
      ],
      'unsupported_response_type' => [
        'error' => 'unsupported_response_type',
        'error_description' => 'The response type is not supported.',
      ],
      'invalid_scope' => [
        'error' => 'invalid_scope',
        'error_description' => 'The requested scope is invalid.',
      ],
      'server_error' => [
        'error' => 'server_error',
        'error_description' => 'The server encountered an unexpected condition.',
      ],
      'temporarily_unavailable' => [
        'error' => 'temporarily_unavailable',
        'error_description' => 'The service is temporarily unavailable.',
      ],
    ];
  }

  /**
   * Mock rate limit response.
   */
  public static function rateLimitResponse(): array {
    return [
      'statusCode' => 429,
      'error' => 'Too Many Requests',
      'message' => 'Rate limit exceeded',
      'errorCode' => 'rate_limit_exceeded',
    ];
  }

  /**
   * Mock transient store state and nonce values.
   */
  public static function transientStoreValues(): array {
    return [
      'state' => 'state_' . bin2hex(random_bytes(16)),
      'nonce' => 'nonce_' . bin2hex(random_bytes(16)),
      'code_verifier' => base64url_encode(random_bytes(32)),
      'code_challenge' => base64url_encode(hash('sha256', base64url_encode(random_bytes(32)), true)),
    ];
  }

}

/**
 * Helper function for base64url encoding.
 */
function base64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}