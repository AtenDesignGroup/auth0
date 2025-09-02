<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel;

use PHPUnit\Framework\Attributes\Group;
use Drupal\auth0\Util\AuthHelper;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests migration of components to use ConfigurationService.
 */
#[Group('auth0')]
class MigrationTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  /**
   * Tests that services can be instantiated with new ConfigurationService.
   */
  public function testServicesCanBeInstantiatedWithNewConfigurationService(): void {
    $auth_helper = $this->container->get('auth0.helper');
    $configuration_service = $this->container->get('auth0.configuration');

    $this->assertInstanceOf(AuthHelper::class, $auth_helper);
    $this->assertInstanceOf(ConfigurationServiceInterface::class, $configuration_service);
  }

  /**
   * Tests that AuthHelper integrates properly with ConfigurationService.
   */
  public function testAuthHelperIntegratesWithConfigurationService(): void {
    $auth_helper = $this->container->get('auth0.helper');

    $this->assertIsString(
      $auth_helper->getAuthDomain()
    );
  }

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
    $this->assertTrue(method_exists($service, 'isOfflineAccess'));
    $this->assertTrue(method_exists($service, 'isRedirectForSso'));
    $this->assertTrue(method_exists($service, 'getFormTitle'));
    $this->assertTrue(method_exists($service, 'isAllowSignup'));
    $this->assertTrue(method_exists($service, 'getUsernameClaim'));
    $this->assertTrue(method_exists($service, 'getClaimMapping'));
    $this->assertTrue(method_exists($service, 'getRoleMapping'));
  }

}
