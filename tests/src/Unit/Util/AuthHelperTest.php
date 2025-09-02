<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit\Util;

use PHPUnit\Framework\Attributes\Group;
use Drupal\auth0\Util\AuthHelper;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\auth0\Util\AuthHelper
 */
#[Group('auth0')]
class AuthHelperTest extends UnitTestCase {

  private ConfigurationServiceInterface $configurationService;
  private AuthHelper $authHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Define the constant that AuthHelper expects
    if (!defined('AUTH0_MODULE_VERSION')) {
      define('AUTH0_MODULE_VERSION', '8.x-2.4');
    }

    $this->configurationService = $this->createMock(ConfigurationServiceInterface::class);
  }

  /**
   * Tests that AuthHelper uses ConfigurationService for domain.
   */
  public function testAuthHelperUsesConfigurationServiceForDomain(): void {
    $this->configurationService->expects($this->once())
      ->method('getDomain')
      ->willReturn('test.auth0.com');

    $this->configurationService->expects($this->once())
      ->method('getCustomDomain')
      ->willReturn(NULL);

    // Create a new instance to trigger constructor
    $auth_helper = new AuthHelper($this->configurationService);

    $this->assertEquals('test.auth0.com', $auth_helper->getAuthDomain());
  }

  /**
   * Tests that AuthHelper prefers custom domain over regular domain.
   */
  public function testAuthHelperPrefersCustomDomainOverRegularDomain(): void {
    $this->configurationService->expects($this->once())
      ->method('getDomain')
      ->willReturn('test.auth0.com');

    $this->configurationService->expects($this->once())
      ->method('getCustomDomain')
      ->willReturn('auth.example.com');

    // Create a new instance to trigger constructor
    $auth_helper = new AuthHelper($this->configurationService);

    $this->assertEquals('auth.example.com', $auth_helper->getAuthDomain());
  }

  /**
   * Tests that AuthHelper falls back to regular domain when custom domain is empty.
   */
  public function testAuthHelperFallsBackToRegularDomainWhenCustomDomainEmpty(): void {
    $this->configurationService->expects($this->once())
      ->method('getDomain')
      ->willReturn('test.auth0.com');

    $this->configurationService->expects($this->once())
      ->method('getCustomDomain')
      ->willReturn('');

    // Create a new instance to trigger constructor
    $auth_helper = new AuthHelper($this->configurationService);

    $this->assertEquals('test.auth0.com', $auth_helper->getAuthDomain());
  }

  /**
   * Tests getTenantCdn static method for different regions.
   */
  public function testGetTenantCdn(): void {
    // Test US region
    $this->assertEquals('https://cdn.auth0.com', AuthHelper::getTenantCdn('test.auth0.com'));
    $this->assertEquals('https://cdn.auth0.com', AuthHelper::getTenantCdn('test.us.auth0.com'));

    // Test EU region
    $this->assertEquals('https://cdn.eu.auth0.com', AuthHelper::getTenantCdn('test.eu.auth0.com'));

    // Test AU region
    $this->assertEquals('https://cdn.au.auth0.com', AuthHelper::getTenantCdn('test.au.auth0.com'));
  }

}
