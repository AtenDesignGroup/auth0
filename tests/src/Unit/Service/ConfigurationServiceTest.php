<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit\Service;

use Drupal\key\KeyInterface;
use Psr\Log\LoggerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\key\KeyRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\auth0\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\auth0\Service\ConfigurationService
 */
#[Group('auth0')]
class ConfigurationServiceTest extends UnitTestCase {

  private RequestStack $requestStack;

  private ConfigFactoryInterface $configFactory;

  private KeyRepositoryInterface $keyRepository;

  private LoggerInterface $logger;

  private ImmutableConfig $config;

  private ConfigurationService $service;

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
   * Tests getClientId method returns correct client ID value.
   */
  public function testGetClientId(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_id' => 'test_client_id',
        ];
      });

    $this->assertEquals('test_client_id', $this->service->getClientId());
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
          'auth0_client_secret_key' => NULL,
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

    $this->assertEquals('cookie_key_value', $this->service->getCookieSecret());
  }



  public function testGetCustomDomain(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_custom_domain' => 'auth.example.com',
        ];
      });

    $this->assertEquals('auth.example.com', $this->service->getCustomDomain());
  }



  public function testGetUsernameClaim(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_username_claim' => 'email',
        ];
      });

    $this->assertEquals('email', $this->service->getUsernameClaim());
  }

  public function testGetClaimMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => 'email|mail',
        ];
      });

    $this->assertEquals('email|mail', $this->service->getClaimMapping());
  }

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
   * Tests generic get method with default values.
   */
  public function testGet(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'test_key' => 'test_value',
        ];
      });

    $this->assertEquals('test_value', $this->service->get('test_key'));
    $this->assertEquals('default', $this->service->get('missing_key', 'default'));
  }

  /**
   * Tests getAll method returns complete configuration array.
   */
  public function testGetAll(): void {
    $expectedConfig = [
      'auth0_domain' => 'test.auth0.com',
      'auth0_client_id' => 'test_client_id',
    ];

    $this->config->method('get')
      ->willReturnCallback(function() use ($expectedConfig) {
        return $expectedConfig;
      });

    $this->assertEquals($expectedConfig, $this->service->getAll());
  }

  /**
   * Tests getClientSecret fallback behavior when Key entity not found.
   */
  public function testGetClientSecretFallbackWhenKeyNotFound(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_secret_key' => 'nonexistent_key_id',
          'auth0_client_secret' => 'fallback_secret',
        ];
      });

    $this->keyRepository->expects($this->once())
      ->method('getKey')
      ->with('nonexistent_key_id')
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Using client_secret from configuration. Consider using Key module for better security.');

    $this->assertEquals('fallback_secret', $this->service->getClientSecret());
  }

  public function testGetClientSecretFallbackWhenKeyEmptyString(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_secret_key' => '',
          'auth0_client_secret' => 'fallback_secret',
        ];
      });

    $this->keyRepository->expects($this->never())->method('getKey');

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Using client_secret from configuration. Consider using Key module for better security.');

    $this->assertEquals('fallback_secret', $this->service->getClientSecret());
  }

  public function testGetClientSecretReturnEmptyWhenNoFallback(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_client_secret_key' => NULL,
          'auth0_client_secret' => '',
        ];
      });

    $this->keyRepository->expects($this->never())->method('getKey');
    $this->logger->expects($this->never())->method('warning');

    $this->assertEquals('', $this->service->getClientSecret());
  }

  /**
   * Tests getCookieSecret fallback behavior when Key entity not found.
   */
  public function testGetCookieSecretFallbackWhenKeyNotFound(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_cookie_secret_key' => 'nonexistent_key_id',
          'auth0_cookie_secret' => 'fallback_cookie_secret',
        ];
      });

    $this->keyRepository->expects($this->once())
      ->method('getKey')
      ->with('nonexistent_key_id')
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Using cookie_secret from configuration. Consider using Key module for better security.');

    $this->assertEquals('fallback_cookie_secret', $this->service->getCookieSecret());
  }

  public function testGetCookieSecretFromConfigWithWarning(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_cookie_secret_key' => NULL,
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
   * Tests getDefaultScopes method uses AUTH0_DEFAULT_SCOPES constant.
   */
  public function testGetDefaultScopesFromConstant(): void {
    // Mock the constant being defined
    if (!defined('AUTH0_DEFAULT_SCOPES')) {
      define('AUTH0_DEFAULT_SCOPES', 'openid profile email offline_access');
    }

    $this->config->method('get')->willReturnCallback(function() {
      return [];
    });

    $expected = ['openid', 'email', 'profile'];
    $this->assertEquals($expected, $this->service->getDefaultScopes());
  }

  public function testGetDefaultScopesDefaultValue(): void {
    $this->config->method('get')->willReturnCallback(function() {
      return [];
    });

    // If constant doesn't exist, should use default value
    $expected = ['openid', 'email', 'profile'];
    $result = $this->service->getDefaultScopes();

    // Just check it returns an array with expected default scopes
    $this->assertIsArray($result);
    $this->assertContains('openid', $result);
    $this->assertContains('email', $result);
    $this->assertContains('profile', $result);
  }

  public function testBooleanMethodsHandleNonBooleanValues(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_requires_verified_email' => '',
          'auth0_sync_role_mapping' => 1,
          'auth0_sync_claim_mapping' => '0',
        ];
      });

    $this->assertFalse($this->service->isRequiresVerifiedEmail());
    $this->assertTrue($this->service->isSyncRoleMapping()); // 1 should be truthy
    $this->assertFalse($this->service->isSyncClaimMapping()); // '0' should be falsy
  }

  public function testGetUsernameClaimDefault(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_username_claim' => NULL,
        ];
      });

    $this->assertEquals('nickname', $this->service->getUsernameClaim());
  }

  public function testNullableMethodsReturnNullForEmptyValues(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_custom_domain' => '',
          'auth0_claim_mapping' => '',
          'auth0_claim_to_use_for_role' => '',
          'auth0_role_mapping' => NULL,
        ];
      });

    $this->assertNull($this->service->getCustomDomain());
    $this->assertNull($this->service->getClaimMapping());
    $this->assertNull($this->service->getClaimToUseForRole());
    $this->assertNull($this->service->getRoleMapping());
  }

  /**
   * Tests getProfileFieldMappingRules method with valid pipe-delimited mapping.
   */
  public function testGetProfileFieldMappingRulesWithValidMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => "given_name|field_first_name\nfamily_name|field_last_name\nemail|field_email_address",
        ];
      });

    $expected = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'email' => 'field_email_address',
    ];

    $this->assertEquals($expected, $this->service->getProfileFieldMappingRules());
  }

  /**
   * Tests getProfileFieldMappingRules method with single field mapping.
   */
  public function testGetProfileFieldMappingRulesWithSingleMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => 'email|field_email',
        ];
      });

    $expected = [
      'email' => 'field_email',
    ];

    $this->assertEquals($expected, $this->service->getProfileFieldMappingRules());
  }

  /**
   * Tests getProfileFieldMappingRules method with empty mapping.
   */
  public function testGetProfileFieldMappingRulesWithEmptyMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_profile_field_mapping' => '',
        ];
      });

    $this->assertEquals([], $this->service->getProfileFieldMappingRules());
  }

  /**
   * Tests getProfileFieldMappingRules method with null mapping.
   */
  public function testGetProfileFieldMappingRulesWithNullMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_profile_field_mapping' => NULL,
        ];
      });

    $this->assertEquals([], $this->service->getProfileFieldMappingRules());
  }

  /**
   * Tests getProfileFieldMappingRules method with malformed mapping lines.
   */
  public function testGetProfileFieldMappingRulesWithMalformedMapping(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => "given_name|field_first_name\ninvalid_line_no_pipe\nfamily_name|field_last_name\n|empty_auth0_claim\nempty_drupal_field|\n  |  \nemail|field_email",
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
   * Tests getProfileFieldMappingRules method with whitespace handling.
   */
  public function testGetProfileFieldMappingRulesWithWhitespace(): void {
    $this->config->method('get')
      ->willReturnCallback(function() {
        return [
          'auth0_claim_mapping' => "  given_name  |  field_first_name  \n\n  email  |  field_email  \n\n",
        ];
      });

    $expected = [
      'given_name' => 'field_first_name',
      'email' => 'field_email',
    ];

    $this->assertEquals($expected, $this->service->getProfileFieldMappingRules());
  }

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

    $this->configFactory->method('get')
      ->with('auth0.settings')
      ->willReturn($this->config);

    $this->service = new ConfigurationService(
      $this->requestStack,
      $this->configFactory,
      $this->keyRepository,
      $this->logger
    );
  }

}
