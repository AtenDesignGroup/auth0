<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit\Service;

use Drupal\key\KeyInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Drupal\key\KeyRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\auth0\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests ConfigurationService functionality.
 */
#[Group('auth0')]
#[CoversClass(ConfigurationService::class)]
class ConfigurationServiceTest extends TestCase {

  private RequestStack|MockObject $requestStack;
  private ConfigFactoryInterface|MockObject $configFactory;
  private KeyRepositoryInterface|MockObject $keyRepository;
  private LoggerInterface|MockObject $logger;
  private ImmutableConfig|MockObject $config;
  private Config|MockObject $editableConfig;
  private Request|MockObject $request;
  private ConfigurationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->keyRepository = $this->createMock(KeyRepositoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->editableConfig = $this->createMock(Config::class);
    $this->request = $this->createMock(Request::class);

    // Setup basic mocks
    $this->requestStack->method('getCurrentRequest')
      ->willReturn($this->request);

    $this->configFactory->method('get')
      ->with('auth0.settings')
      ->willReturn($this->config);

    $this->configFactory->method('getEditable')
      ->with('auth0.settings')
      ->willReturn($this->editableConfig);

    $this->service = new ConfigurationService(
      $this->requestStack,
      $this->configFactory,
      $this->keyRepository,
      $this->logger
    );
  }

  /**
   * Tests getDomain method returns correct domain value.
   */
  public function testGetDomain(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_domain' => 'test.auth0.com',
        ];
      });

    $this->assertEquals('test.auth0.com', $this->service->getDomain());
  }

  /**
   * Tests getDomain method returns empty string when not configured.
   */
  public function testGetDomainEmpty(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [];
      });

    $this->assertEquals('', $this->service->getDomain());
  }

  /**
   * Tests getClientId method returns correct client ID value.
   */
  public function testGetClientId(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_id' => 'test_client_id_123',
        ];
      });

    $this->assertEquals('test_client_id_123', $this->service->getClientId());
  }

  /**
   * Tests getClientSecret method retrieves value from Key module.
   */
  public function testGetClientSecretFromKey(): void {
    $key = $this->createMock(KeyInterface::class);
    $key->method('getKeyValue')->willReturn('key_secret_value');

    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_secret_key' => 'test_key_id',
          'auth0_client_secret' => 'fallback_value',
        ];
      });

    $this->keyRepository->expects($this->once())
      ->method('getKey')
      ->with('test_key_id')
      ->willReturn($key);

    $this->logger->expects($this->never())->method('warning');

    $this->assertEquals('key_secret_value', $this->service->getClientSecret());
  }

  /**
   * Tests getClientSecret method falls back to config with warning.
   */
  public function testGetClientSecretFromConfigWithWarning(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_secret_key' => null,
          'auth0_client_secret' => 'direct_secret',
        ];
      });

    $this->keyRepository->expects($this->never())->method('getKey');

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Using client_secret from configuration. Consider using Key module for better security.');

    $this->assertEquals('direct_secret', $this->service->getClientSecret());
  }

  /**
   * Tests getCookieSecret method retrieves value from Key module.
   */
  public function testGetCookieSecretFromKey(): void {
    $key = $this->createMock(KeyInterface::class);
    $key->method('getKeyValue')->willReturn('cookie_key_value');

    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_cookie_secret_key' => 'cookie_key_id',
          'auth0_cookie_secret' => '',
        ];
      });

    $this->keyRepository->expects($this->once())
      ->method('getKey')
      ->with('cookie_key_id')
      ->willReturn($key);

    $this->logger->expects($this->never())->method('warning');

    $this->assertEquals('cookie_key_value', $this->service->getCookieSecret());
  }

  /**
   * Tests getCookieSecret method falls back to config with warning.
   */
  public function testGetCookieSecretFromConfigWithWarning(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_cookie_secret_key' => null,
          'auth0_cookie_secret' => 'direct_cookie_secret',
        ];
      });

    $this->keyRepository->expects($this->never())->method('getKey');

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Using cookie_secret from configuration. Consider using Key module for better security.');

    $this->assertEquals('direct_cookie_secret', $this->service->getCookieSecret());
  }

  /**
   * Tests getDefaultScopes method returns scopes as array.
   */
  public function testGetDefaultScopes(): void {
    $expected = ['openid', 'email', 'profile'];
    $result = $this->service->getDefaultScopes();
    
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getDefaultScopes method returns scopes as string.
   */
  public function testGetDefaultScopesAsString(): void {
    $expected = 'openid email profile';
    $result = $this->service->getDefaultScopes(true);
    
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests redirectUri method generates correct URI.
   */
  public function testRedirectUri(): void {
    $this->request->method('getSchemeAndHttpHost')
      ->willReturn('https://example.com');

    $this->assertEquals('https://example.com/auth0/callback', $this->service->redirectUri());
  }

  /**
   * Tests isRequiresVerifiedEmail method.
   */
  public function testIsRequiresVerifiedEmail(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_requires_verified_email' => true,
        ];
      });

    $this->assertTrue($this->service->isRequiresVerifiedEmail());
  }

  /**
   * Tests getUsernameClaim method with custom value.
   */
  public function testGetUsernameClaim(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_username_claim' => 'email',
        ];
      });

    $this->assertEquals('email', $this->service->getUsernameClaim());
  }

  /**
   * Tests getUsernameClaim method with default value.
   */
  public function testGetUsernameClaimDefault(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [];
      });

    $this->assertEquals('nickname', $this->service->getUsernameClaim());
  }

  /**
   * Tests getClaimMapping method.
   */
  public function testGetClaimMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => 'email|field_email_address',
        ];
      });

    $this->assertEquals('email|field_email_address', $this->service->getClaimMapping());
  }

  /**
   * Tests getClaimToUseForRole method.
   */
  public function testGetClaimToUseForRole(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_to_use_for_role' => 'user_roles',
        ];
      });

    $this->assertEquals('user_roles', $this->service->getClaimToUseForRole());
  }

  /**
   * Tests getRoleMapping method.
   */
  public function testGetRoleMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_role_mapping' => 'admin|administrator',
        ];
      });

    $this->assertEquals('admin|administrator', $this->service->getRoleMapping());
  }

  /**
   * Tests getRoleMappingRules method with pipe-delimited format.
   */
  public function testGetRoleMappingRules(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_role_mapping' => "admin|administrator\neditor|content_editor\nuser|authenticated",
        ];
      });

    $expected = [
      'admin' => ['administrator'],
      'editor' => ['content_editor'],
      'user' => ['authenticated'],
    ];

    $this->assertEquals($expected, $this->service->getRoleMappingRules());
  }

  /**
   * Tests getProfileFieldMappingRules method.
   */
  public function testGetProfileFieldMappingRules(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => "given_name|field_first_name\nfamily_name|field_last_name\nemail|field_email",
        ];
      });

    $expected = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'email' => 'field_email',
    ];

    $this->assertEquals($expected, $this->service->getProfileFieldMappingRules());
  }

  /**
   * Tests isSyncRoleMapping method.
   */
  public function testIsSyncRoleMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_sync_role_mapping' => true,
        ];
      });

    $this->assertTrue($this->service->isSyncRoleMapping());
  }

  /**
   * Tests isSyncClaimMapping method.
   */
  public function testIsSyncClaimMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_sync_claim_mapping' => true,
        ];
      });

    $this->assertTrue($this->service->isSyncClaimMapping());
  }

  /**
   * Tests generic get method.
   */
  public function testGet(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'test_key' => 'test_value',
        ];
      });

    $this->assertEquals('test_value', $this->service->get('test_key'));
    $this->assertEquals('default_value', $this->service->get('missing_key', 'default_value'));
  }

  /**
   * Tests getAll method returns complete configuration.
   */
  public function testGetAll(): void {
    $expectedConfig = [
      'auth0_domain' => 'test.auth0.com',
      'auth0_client_id' => 'test_client_id',
      'auth0_client_secret' => 'test_secret',
    ];

    $this->config->method('get')
      ->willReturnCallback(function() use ($expectedConfig) {
        return $expectedConfig;
      });

    $this->assertEquals($expectedConfig, $this->service->getAll());
  }

  /**
   * Tests set method updates configuration.
   */
  public function testSet(): void {
    $this->editableConfig->expects($this->once())
      ->method('set')
      ->with('test_key', 'test_value')
      ->willReturnSelf();

    $this->editableConfig->expects($this->once())
      ->method('save');

    $result = $this->service->set('test_key', 'test_value');
    
    $this->assertInstanceOf(ConfigurationService::class, $result);
  }

  /**
   * Tests setMultiple method updates multiple configuration values.
   */
  public function testSetMultiple(): void {
    $values = [
      'auth0_domain' => 'new.auth0.com',
      'auth0_client_id' => 'new_client_id',
    ];

    $this->editableConfig->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function($key, $value) use ($values) {
        $this->assertArrayHasKey($key, $values);
        $this->assertEquals($values[$key], $value);
        return $this->editableConfig;
      });

    $this->editableConfig->expects($this->once())
      ->method('save');

    $result = $this->service->setMultiple($values);
    
    $this->assertInstanceOf(ConfigurationService::class, $result);
  }

  /**
   * Tests configuration caching behavior.
   */
  public function testConfigurationCaching(): void {
    $configData = ['auth0_domain' => 'test.auth0.com'];
    
    // Should only call get() once due to caching
    $this->config->expects($this->once())
      ->method('get')
      ->willReturn($configData);

    // First call
    $result1 = $this->service->getAll();
    
    // Second call should use cache
    $result2 = $this->service->getAll();
    
    $this->assertEquals($configData, $result1);
    $this->assertEquals($configData, $result2);
  }

  /**
   * Tests cache clearing after configuration update.
   */
  public function testCacheClearingAfterUpdate(): void {
    $initialData = ['auth0_domain' => 'old.auth0.com'];
    $updatedData = ['auth0_domain' => 'new.auth0.com'];

    $this->config->expects($this->exactly(2))
      ->method('get')
      ->willReturnOnConsecutiveCalls($initialData, $updatedData);

    $this->editableConfig->method('set')->willReturnSelf();
    $this->editableConfig->method('save');

    // First call
    $result1 = $this->service->getAll();
    $this->assertEquals($initialData, $result1);

    // Update configuration (should clear cache)
    $this->service->set('auth0_domain', 'new.auth0.com');

    // Second call should fetch fresh data
    $result2 = $this->service->getAll();
    $this->assertEquals($updatedData, $result2);
  }

}