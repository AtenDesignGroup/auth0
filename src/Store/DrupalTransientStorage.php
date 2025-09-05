<?php

declare(strict_types=1);

namespace Drupal\auth0\Store;

use Auth0\SDK\Contract\StoreInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Define a Drupal transient storage for Auth0 SDK.
 */
class DrupalTransientStorage implements StoreInterface {

  /** @var string  */
  protected const string KEY_PREFIX = 'auth0_';

  /** @var \Drupal\Core\TempStore\PrivateTempStore  */
  protected PrivateTempStore $store;

  /**
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->store = $tempStoreFactory->get('auth0_transient');
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value): void {
    $this->store->set($this->formatKey($key), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key, mixed $default = NULL): mixed {
    return $this->store->get($this->formatKey($key)) ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): void {
    $this->store->delete($this->formatKey($key));
  }

  /**
   * {@inheritdoc}
   */
  public function purge(): void {}

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
