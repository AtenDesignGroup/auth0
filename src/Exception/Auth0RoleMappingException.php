<?php

declare(strict_types=1);

namespace Drupal\auth0\Exception;

/**
 * Define the Auth0 role mapping exception.
 *
 * This exception is thrown when role mapping operations fail due to
 * configuration issues, validation errors, or system failures during
 * the mapping and assignment of Auth0 roles to Drupal roles.
 */
class Auth0RoleMappingException extends \Exception {

  /**
   * Constructs a new Auth0RoleMappingException.
   *
   * @param string $message
   *   The exception message describing the error.
   * @param string|null $auth0UserId
   *   The optional Auth0 user ID associated with the error.
   * @param array $context
   *   Additional context information about the error.
   * @param \Throwable|null $previous
   *   The previous exception for exception chaining.
   */
  public function __construct(
    string $message,
    public readonly ?string $auth0UserId = NULL,
    public readonly array $context = [],
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, 0, $previous);
  }

  /**
   * Gets the Auth0 user ID associated with this exception.
   *
   * @return string|null
   *   The Auth0 user ID or NULL if not available.
   */
  public function getAuth0UserId(): ?string {
    return $this->auth0UserId;
  }

  /**
   * Gets the context information associated with this exception.
   *
   * @return array
   *   An associative array of context information.
   */
  public function getContext(): array {
    return $this->context;
  }

}