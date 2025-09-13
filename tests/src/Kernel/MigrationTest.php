<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel;

use Drupal\auth0\Util\AuthHelper;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests migration of components to use ConfigurationService.
 */
#[Group('auth0')]
class MigrationTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'externalauth',
    'key',
    'auth0',
  ];

  /**
   * Tests that ConfigurationService provides all required migration methods.
   */
  public function testConfigurationServiceProvidesMigrationMethods(): void {
    $service = $this->container->get('auth0.configuration');

    // Test that all required methods exist
    $this->assertTrue(method_exists($service, 'getDomain'));
    $this->assertTrue(method_exists($service, 'getClientId'));
    $this->assertTrue(method_exists($service, 'getClientSecret'));
    $this->assertTrue(method_exists($service, 'getCookieSecret'));
    $this->assertTrue(method_exists($service, 'isRequiresVerifiedEmail'));
    $this->assertTrue(method_exists($service, 'isSyncRoleMapping'));
    $this->assertTrue(method_exists($service, 'isSyncClaimMapping'));
    $this->assertTrue(method_exists($service, 'getUsernameClaim'));
    $this->assertTrue(method_exists($service, 'getClaimMapping'));
    $this->assertTrue(method_exists($service, 'getRoleMapping'));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

}
