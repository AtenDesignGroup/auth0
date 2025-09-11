<?php

namespace Drupal\auth0\Exception;

/**
 * Define the authentication login exception.
 */
class AuthenticationLoginException extends \Exception {

  /**
   * The authentication login exception constructor.
   *
   * @param string $message
   * @param string $redirectUrl
   * @param string|null $errorMessage
   */
  public function __construct(
    string $message,
    public string $redirectUrl = '/',
    public ?string $errorMessage = NULL,
  ) {
    parent::__construct($message);
  }

  /**
   * @return string|null
   */
  public function errorMessage(): ?string {
    return $this->errorMessage;
  }

  /**
   * @return string
   */
  public function getRedirectUrl(): string {
    return $this->redirectUrl;
  }

}
