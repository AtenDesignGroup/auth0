<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\auth0\Contracts\AuthenticationServiceInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests AuthenticationService functionality.
 */
#[Group('auth0')]
class AuthenticationServiceTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'externalauth', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  /**
   * Tests that AuthenticationService can be instantiated.
   */
  public function testAuthenticationServiceExists(): void {
    $auth_service = $this->container->get('auth0.authentication');
    $this->assertInstanceOf(AuthenticationServiceInterface::class, $auth_service);
  }

  /**
   * Tests that obsolete handleInlineLoginForm method no longer exists.
   */
  public function testObsoleteHandleInlineLoginFormMethodRemoved(): void {
    $auth_service = $this->container->get('auth0.authentication');
    
    // Verify the obsolete method is not accessible
    $this->assertFalse(
      method_exists($auth_service, 'handleInlineLoginForm'),
      'The obsolete handleInlineLoginForm method should be removed'
    );
  }

  /**
   * Tests that core authentication methods still exist after refactoring.
   */
  public function testCoreAuthenticationMethodsExist(): void {
    $auth_service = $this->container->get('auth0.authentication');
    
    // Verify core methods still exist
    $this->assertTrue(method_exists($auth_service, 'handleLogin'));
    $this->assertTrue(method_exists($auth_service, 'handleLogout'));
    $this->assertTrue(method_exists($auth_service, 'handleLoginPage'));
    $this->assertTrue(method_exists($auth_service, 'getState'));
    $this->assertTrue(method_exists($auth_service, 'getNonce'));
  }

}
