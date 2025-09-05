<?php

declare(strict_types=1);

namespace Drupal\auth0\Store;

use Auth0\SDK\Contract\StoreInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Define a Drupal session storage for Auth0 SDK.
 */
class DrupalSessionStorage implements StoreInterface {

  /** @var string  */
  protected const string KEY_PREFIX = 'auth0_';

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function __construct(
    protected Request $request
  ) {}

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value): void {
    if ($session = $this->request?->getSession()) {
      $session->set($this->formatKey($key), $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key, mixed $default = NULL): mixed {
    if ($session = $this->request?->getSession()) {
      return $session->get($this->formatKey($key), $default);
    }
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): void {
    if ($session = $this->request?->getSession()) {
      $session->remove($this->formatKey($key));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function purge(): void {
    if ($session = $this->request?->getSession()) {
      foreach (array_keys($session->all()) as $key) {
        if (str_starts_with($key, static::KEY_PREFIX)) {
          $session->remove($key);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defer(bool $deferring): void {}

  /**
   * @param string $key
   *
   * @return string
   */
  protected function formatKey(string $key): string {
    $prefix = static::KEY_PREFIX;
    return "$prefix$key";
  }
}
