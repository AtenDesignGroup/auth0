<?php

declare(strict_types=1);

namespace Drupal\auth0\ValueObject;

/**
 * Represents an Auth0 user with their profile information and tokens.
 *
 * This value object encapsulates user information received from Auth0,
 * providing convenient access to common user profile fields and JWT claims.
 * The class is readonly and immutable, ensuring data integrity.
 */
final readonly class Auth0User {

  /**
   * Constructs a new Auth0User instance.
   *
   * @param array $userInfo
   *   The user information array containing profile data and claims.
   * @param string|null $refreshToken
   *   The optional refresh token for the user session.
   */
  private function __construct(
    protected array $userInfo,
    protected ?string $refreshToken = NULL
  ) {}

  /**
   * Creates a new Auth0User instance.
   *
   * @param array $userInfo
   *   The user information array containing profile data and claims.
   * @param string|null $refreshToken
   *   The optional refresh token for the user session.
   *
   * @return self
   *   A new Auth0User instance.
   */
  public static function make(
    array $userInfo,
    ?string $refreshToken = NULL
  ): self {
    return new self($userInfo, $refreshToken);
  }

  /**
   * Gets the user's specific value using the key.
   *
   * @param string $key
   *   The key for the value within the user info.
   *
   * @return mixed
   */
  public function get(string $key): mixed {
    return $this->userInfo[$key] ?? NULL;
  }

  /**
   * Gets the user's display name.
   *
   * @return string|null
   *   The user's name or NULL if not available.
   */
  public function name(): ?string {
    return $this->userInfo['name'] ?? NULL;
  }

  /**
   * Gets the user's nickname.
   *
   * @return string|null
   *   The user's nickname or NULL if not available.
   */
  public function nickname(): ?string {
    return $this->userInfo['nickname'] ?? NULL;
  }

  /**
   * Gets the user's profile picture URL.
   *
   * @return string|null
   *   The user's picture URL or NULL if not available.
   */
  public function picture(): ?string {
    return $this->userInfo['picture'] ?? NULL;
  }

  /**
   * Gets the timestamp when the user was last updated.
   *
   * @return string|null
   *   The updated_at timestamp or NULL if not available.
   */
  public function updatedAt(): ?string {
    return $this->userInfo['updated_at'] ?? NULL;
  }

  /**
   * Gets the user's email address.
   *
   * @return string|null
   *   The user's email address or NULL if not available.
   */
  public function email(): ?string {
    return $this->userInfo['email'] ?? NULL;
  }

  /**
   * Gets whether the user's email address has been verified.
   *
   * @return bool|null
   *   TRUE if the email is verified, FALSE if not, or NULL if not available.
   */
  public function emailVerified(): ?bool {
    $verified = $this->userInfo['email_verified'] ?? NULL;
    return $verified !== NULL ? (bool) $verified : NULL;
  }

  /**
   * Gets the JWT issuer claim.
   *
   * @return string|null
   *   The issuer (iss) claim or NULL if not available.
   */
  public function iss(): ?string {
    return $this->userInfo['iss'] ?? NULL;
  }

  /**
   * Gets the JWT audience claim.
   *
   * @return string|null
   *   The audience (aud) claim or NULL if not available.
   */
  public function aud(): ?string {
    return $this->userInfo['aud'] ?? NULL;
  }

  /**
   * Gets the JWT subject claim.
   *
   * @return string|null
   *   The subject (sub) claim or NULL if not available.
   */
  public function sub(): ?string {
    return $this->userInfo['sub'] ?? NULL;
  }

  /**
   * Gets the JWT issued at timestamp.
   *
   * @return int|null
   *   The issued at (iat) timestamp or NULL if not available.
   */
  public function iat(): ?int {
    $iat = $this->userInfo['iat'] ?? NULL;
    return $iat !== NULL ? (int) $iat : NULL;
  }

  /**
   * Gets the JWT expiration timestamp.
   *
   * @return int|null
   *   The expiration (exp) timestamp or NULL if not available.
   */
  public function exp(): ?int {
    $exp = $this->userInfo['exp'] ?? NULL;
    return $exp !== NULL ? (int) $exp : NULL;
  }

  /**
   * Gets the JWT session ID claim.
   *
   * @return string|null
   *   The session ID (sid) claim or NULL if not available.
   */
  public function sid(): ?string {
    return $this->userInfo['sid'] ?? NULL;
  }

  /**
   * Gets the JWT nonce claim.
   *
   * @return string|null
   *   The nonce claim or NULL if not available.
   */
  public function nonce(): ?string {
    return $this->userInfo['nonce'] ?? NULL;
  }

  /**
   * Gets the user's unique identifier.
   *
   * @return string|null
   *   The user ID or NULL if not available.
   */
  public function userId(): ?string {
    return $this->userInfo['user_id'] ?? NULL;
  }

  /**
   * Gets the user's refresh token.
   *
   * @return string|null
   *   The refresh token or NULL if not set.
   */
  public function getRefreshToken(): ?string {
    return $this->refreshToken;
  }

}
