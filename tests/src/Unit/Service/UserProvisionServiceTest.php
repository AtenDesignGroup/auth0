<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\auth0\ValueObject\Auth0User;
use PHPUnit\Framework\Attributes\Group;
use Drupal\auth0\Service\ConfigurationService;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\auth0\Service\UserProvisionService;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @coversDefaultClass \Drupal\auth0\Service\UserProvisionService
 */
#[Group('auth0')]
class UserProvisionServiceTest extends UnitTestCase {

  private Authmap $authmap;

  private ExternalAuth $externalAuth;

  private ConfigurationService $configurationService;

  private LoggerChannelInterface $logger;

  private EntityTypeManagerInterface $entityTypeManager;

  private UserProvisionService $service;

  /**
   * Tests mapAuth0ProfileFields method with valid profile field mapping.
   */
  public function testMapAuth0ProfileFieldsWithValidMapping(): void {
    $userInfo = [
      'given_name' => 'John',
      'family_name' => 'Doe',
      'email' => 'john.doe@example.com',
      'company' => 'Acme Corp',
    ];

    $auth0User = Auth0User::make($userInfo);

    $mappingRules = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'email' => 'field_email_address',
    ];

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn($mappingRules);

    $expected = [
      'field_first_name' => 'John',
      'field_last_name' => 'Doe',
      'field_email_address' => 'john.doe@example.com',
    ];

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests mapAuth0ProfileFields method with empty mapping rules.
   */
  public function testMapAuth0ProfileFieldsWithEmptyMappingRules(): void {
    $userInfo = [
      'given_name' => 'John',
      'family_name' => 'Doe',
    ];

    $auth0User = Auth0User::make($userInfo);

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn([]);

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals([], $result);
  }

  /**
   * Tests mapAuth0ProfileFields method with missing claims.
   */
  public function testMapAuth0ProfileFieldsWithMissingClaims(): void {
    $userInfo = [
      'given_name' => 'John',
      // family_name is missing
      'email' => 'john.doe@example.com',
    ];

    $auth0User = Auth0User::make($userInfo);

    $mappingRules = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'email' => 'field_email_address',
    ];

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn($mappingRules);

    $expected = [
      'field_first_name' => 'John',
      'field_email_address' => 'john.doe@example.com',
      // field_last_name should not be present since family_name claim is missing
    ];

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests mapAuth0ProfileFields method with null claim values.
   */
  public function testMapAuth0ProfileFieldsWithNullClaimValues(): void {
    $userInfo = [
      'given_name' => 'John',
      'family_name' => NULL,
      'email' => 'john.doe@example.com',
    ];

    $auth0User = Auth0User::make($userInfo);

    $mappingRules = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'email' => 'field_email_address',
    ];

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn($mappingRules);

    $expected = [
      'field_first_name' => 'John',
      'field_email_address' => 'john.doe@example.com',
      // field_last_name should not be present since family_name claim is null
    ];

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests mapAuth0ProfileFields method with empty claim values.
   */
  public function testMapAuth0ProfileFieldsWithEmptyClaimValues(): void {
    $userInfo = [
      'given_name' => 'John',
      'family_name' => '',
      'email' => 'john.doe@example.com',
    ];

    $auth0User = Auth0User::make($userInfo);

    $mappingRules = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'email' => 'field_email_address',
    ];

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn($mappingRules);

    $expected = [
      'field_first_name' => 'John',
      'field_email_address' => 'john.doe@example.com',
      // field_last_name should not be present since family_name claim is empty
    ];

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests mapAuth0ProfileFields method with complex claim data.
   */
  public function testMapAuth0ProfileFieldsWithComplexClaimData(): void {
    $userInfo = [
      'given_name' => 'John',
      'family_name' => 'Doe',
      'profile_data' => [
        'department' => 'Engineering',
        'title' => 'Senior Developer',
      ],
      'permissions' => ['read', 'write', 'admin'],
    ];

    $auth0User = Auth0User::make($userInfo);

    $mappingRules = [
      'given_name' => 'field_first_name',
      'family_name' => 'field_last_name',
      'profile_data' => 'field_profile_data',
      'permissions' => 'field_permissions',
    ];

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn($mappingRules);

    $expected = [
      'field_first_name' => 'John',
      'field_last_name' => 'Doe',
      'field_profile_data' => [
        'department' => 'Engineering',
        'title' => 'Senior Developer',
      ],
      'field_permissions' => ['read', 'write', 'admin'],
    ];

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests login method includes profile field mapping in loginRegister call.
   */
  public function testLoginWithProfileFieldMapping(): void {
    $userInfo = [
      'user_id' => 'auth0|123456789',
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'given_name' => 'John',
      'family_name' => 'Doe',
      'roles' => ['admin'],
    ];

    $auth0User = Auth0User::make($userInfo);

    // Mock configuration service methods
    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn([
        'given_name' => 'field_first_name',
        'family_name' => 'field_last_name',
      ]);

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn([
        'admin' => ['administrator'],
      ]);

    $this->configurationService->expects($this->once())
      ->method('getUsernameClaim')
      ->willReturn('name');

    // Expect loginRegister to be called with profile fields merged
    $this->externalAuth->expects($this->once())
      ->method('loginRegister')
      ->with(
        'auth0|123456789',
        'auth0',
        [
          'name' => 'John Doe',
          'email' => 'john.doe@example.com',
          'roles' => ['administrator'],
          'field_first_name' => 'John',
          'field_last_name' => 'Doe',
        ]
      )
      ->willReturn($this->createMock(\Drupal\user\UserInterface::class));

    $result = $this->service->login($auth0User);
    $this->assertInstanceOf(\Drupal\user\UserInterface::class, $result);
  }

  /**
   * Tests login method with empty profile field mapping.
   */
  public function testLoginWithEmptyProfileFieldMapping(): void {
    $userInfo = [
      'user_id' => 'auth0|123456789',
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'roles' => ['admin'],
    ];

    $auth0User = Auth0User::make($userInfo);

    // Mock configuration service methods
    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn([]);

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn([
        'admin' => ['administrator'],
      ]);

    $this->configurationService->expects($this->once())
      ->method('getUsernameClaim')
      ->willReturn('name');

    // Expect loginRegister to be called without additional profile fields
    $this->externalAuth->expects($this->once())
      ->method('loginRegister')
      ->with(
        'auth0|123456789',
        'auth0',
        [
          'name' => 'John Doe',
          'email' => 'john.doe@example.com',
          'roles' => ['administrator'],
        ]
      )
      ->willReturn($this->createMock(\Drupal\user\UserInterface::class));

    $result = $this->service->login($auth0User);
    $this->assertInstanceOf(\Drupal\user\UserInterface::class, $result);
  }

  /**
   * Tests login method handles profile field mapping errors gracefully.
   */
  public function testLoginWithProfileFieldMappingError(): void {
    $userInfo = [
      'user_id' => 'auth0|123456789',
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'given_name' => 'John',
      'roles' => [],
    ];

    $auth0User = Auth0User::make($userInfo);

    // Mock configuration service methods
    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn([
        'given_name' => 'field_first_name',
        'missing_claim' => 'field_missing',
      ]);

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn([]);

    $this->configurationService->expects($this->once())
      ->method('getDefaultRole')
      ->willReturn('authenticated');

    $this->configurationService->expects($this->once())
      ->method('getUsernameClaim')
      ->willReturn('name');

    // Expect loginRegister to be called with only valid profile fields
    $this->externalAuth->expects($this->once())
      ->method('loginRegister')
      ->with(
        'auth0|123456789',
        'auth0',
        [
          'name' => 'John Doe',
          'email' => 'john.doe@example.com',
          'roles' => ['authenticated'],
          'field_first_name' => 'John',
          // field_missing should not be present
        ]
      )
      ->willReturn($this->createMock(\Drupal\user\UserInterface::class));

    $result = $this->service->login($auth0User);
    $this->assertInstanceOf(\Drupal\user\UserInterface::class, $result);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->authmap = $this->createMock(Authmap::class);
    $this->externalAuth = $this->createMock(ExternalAuth::class);
    $this->configurationService = $this->createMock(ConfigurationService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->service = new UserProvisionService(
      $this->authmap,
      $this->externalAuth,
      $this->configurationService,
      $this->logger,
      $this->entityTypeManager
    );
  }

}
