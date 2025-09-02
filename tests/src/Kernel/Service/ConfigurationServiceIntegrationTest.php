<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel\Service;

use PHPUnit\Framework\Attributes\Group;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Drupal\KernelTests\KernelTestBase;

#[Group('auth0')]
class ConfigurationServiceIntegrationTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  /**
   * Tests that the ConfigurationService is properly registered in the DI container.
   */
  public function testServiceRegistration(): void {
    $service = $this->container->get('auth0.configuration');
    $this->assertInstanceOf(ConfigurationServiceInterface::class, $service);
  }

}
