<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\externalauth\ExternalAuth;
use Drupal\auth0\ValueObject\Auth0User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\auth0\Service\ConfigurationService;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\auth0\Service\UserProvisionService;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Tests UserProvisionService functionality.
 */
#[Group('auth0')]
#[CoversClass(UserProvisionService::class)]
class UserProvisionServiceTest extends TestCase {

  private ExternalAuth|MockObject $externalAuth;
  private ConfigurationService|MockObject $configurationService;
  private LoggerChannelInterface|MockObject $logger;
  private EntityTypeManagerInterface|MockObject $entityTypeManager;
  private UserProvisionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->externalAuth = $this->createMock(ExternalAuth::class);
    $this->configurationService = $this->createMock(ConfigurationService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->service = new UserProvisionService(
      $this->externalAuth,
      $this->configurationService,
      $this->logger,
      $this->entityTypeManager
    );
  }

  /**
   * Tests findUser method successfully finds existing user.
   */
  public function testFindUserSuccess(): void {
    $expectedUser = $this->createMock(User::class);
    
    $this->externalAuth->expects($this->once())
      ->method('load')
      ->with('auth0|123456', 'auth0')
      ->willReturn($expectedUser);

    $result = $this->service->findUser('auth0|123456');
    
    $this->assertSame($expectedUser, $result);
  }

  /**
   * Tests findUser method returns null when user not found.
   */
  public function testFindUserNotFound(): void {
    $this->externalAuth->expects($this->once())
      ->method('load')
      ->with('auth0|nonexistent', 'auth0')
      ->willReturn(null);

    $result = $this->service->findUser('auth0|nonexistent');
    
    $this->assertNull($result);
  }

  /**
   * Tests login method with existing user - should sync roles and fields.
   */
  public function testLoginExistingUser(): void {
    $userInfo = [
      'sub' => 'auth0|123456',
      'user_id' => 'auth0|123456',
      'email' => 'john@example.com',
      'name' => 'John Doe',
      'given_name' => 'John',
      'roles' => ['admin'],
    ];
    
    $auth0User = Auth0User::make($userInfo);
    $existingUser = $this->createMock(UserInterface::class);

    // Mock existing user login
    $this->externalAuth->expects($this->once())
      ->method('login')
      ->with('auth0|123456', 'auth0')
      ->willReturn($existingUser);

    // Mock role sync configuration
    $this->configurationService->expects($this->once())
      ->method('getRoleMapping')
      ->willReturn('admin|administrator');

    $this->configurationService->expects($this->once())
      ->method('isSyncRoleMapping')
      ->willReturn(true);

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn(['admin' => ['administrator']]);

    // Mock profile field sync configuration
    $this->configurationService->expects($this->once())
      ->method('getClaimMapping')
      ->willReturn('given_name|field_first_name');

    $this->configurationService->expects($this->once())
      ->method('isSyncClaimMapping')
      ->willReturn(true);

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn(['given_name' => 'field_first_name']);

    // Expect user to be updated - role sync happens first
    $existingUser->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function($field, $value) {
        if ($field === 'roles') {
          $this->assertEquals(['administrator'], $value);
        } elseif ($field === 'field_first_name') {
          $this->assertEquals('John', $value);
        } else {
          $this->fail("Unexpected field: $field");
        }
      });

    $existingUser->expects($this->once())
      ->method('save');

    $result = $this->service->login($auth0User);
    
    $this->assertSame($existingUser, $result);
  }

  /**
   * Tests login method creates new user when user doesn't exist.
   */
  public function testLoginNewUser(): void {
    $userInfo = [
      'sub' => 'auth0|123456',
      'user_id' => 'auth0|123456',
      'email' => 'john@example.com',
      'name' => 'John Doe',
      'given_name' => 'John',
      'roles' => ['user'],
    ];
    
    $auth0User = Auth0User::make($userInfo);
    $newUser = $this->createMock(UserInterface::class);

    // Mock no existing user found (login returns null, should create new)
    $this->externalAuth->expects($this->once())
      ->method('login')
      ->with('auth0|123456', 'auth0')
      ->willReturn(null);

    // Mock configuration for new user creation
    $this->configurationService->expects($this->once())
      ->method('getUsernameClaim')
      ->willReturn('name');

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn(['user' => ['authenticated']]);

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn(['given_name' => 'field_first_name']);

    // Expect new user registration
    $this->externalAuth->expects($this->once())
      ->method('register')
      ->with(
        'auth0|123456',
        'auth0',
        [
          'name' => 'John Doe',
          'email' => 'john@example.com',
          'roles' => ['authenticated'],
          'field_first_name' => 'John',
        ]
      )
      ->willReturn($newUser);

    // Mock userLoginFinalize call
    $this->externalAuth->expects($this->once())
      ->method('userLoginFinalize')
      ->with($newUser, 'auth0|123456', 'auth0')
      ->willReturn($newUser);

    $result = $this->service->login($auth0User);
    
    $this->assertSame($newUser, $result);
  }

  /**
   * Tests login method returns null when no user_id provided.
   */
  public function testLoginWithoutUserId(): void {
    $userInfo = [
      'sub' => 'auth0|123456',
      // Missing user_id
      'email' => 'john@example.com',
      'name' => 'John Doe',
    ];
    
    $auth0User = Auth0User::make($userInfo);

    $result = $this->service->login($auth0User);
    
    $this->assertNull($result);
  }

  /**
   * Tests mapAuth0ProfileFields method with valid mapping.
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
      'family_name' => null,
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
    ];

    $result = $this->service->mapAuth0ProfileFields($auth0User);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests role mapping functionality.
   */
  public function testRoleMapping(): void {
    $userInfo = [
      'roles' => ['admin', 'editor', 'viewer'],
    ];
    
    $auth0User = Auth0User::make($userInfo);

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn([
        'admin' => [0 => 'administrator'],
        'editor' => [1 => 'content_editor'],
        'nonexistent' => [2 => 'should_not_appear'],
      ]);

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('mapAuth0Roles');
    $method->setAccessible(true);

    $result = $method->invoke($this->service, $auth0User);
    
    // The += operator means: admin gets key 0, editor gets key 1
    $expected = [0 => 'administrator', 1 => 'content_editor'];
    $this->assertEquals($expected, array_values($result));
  }

  /**
   * Tests role mapping with no mapping rules.
   */
  public function testRoleMappingWithNoRules(): void {
    $userInfo = [
      'roles' => ['admin', 'editor'],
    ];
    
    $auth0User = Auth0User::make($userInfo);

    $this->configurationService->expects($this->once())
      ->method('getRoleMappingRules')
      ->willReturn([]);

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('mapAuth0Roles');
    $method->setAccessible(true);

    $result = $method->invoke($this->service, $auth0User);
    
    $this->assertEquals([], $result);
  }

  /**
   * Tests username generation from different claims.
   */
  public function testUsernameGeneration(): void {
    $userInfo = [
      'nickname' => 'johndoe',
      'email' => 'john@example.com',
      'name' => 'John Doe',
    ];
    
    $auth0User = Auth0User::make($userInfo);

    // Test with nickname claim
    $this->configurationService->expects($this->once())
      ->method('getUsernameClaim')
      ->willReturn('nickname');

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('generateUsername');
    $method->setAccessible(true);

    $result = $method->invoke($this->service, $auth0User);
    
    $this->assertEquals('johndoe', $result);
  }

  /**
   * Tests profile field sync restrictions.
   */
  public function testProfileFieldSyncRestrictions(): void {
    $userInfo = [
      'given_name' => 'John',
      'uid' => '123',  // This should be restricted
    ];
    
    $auth0User = Auth0User::make($userInfo);
    $user = $this->createMock(UserInterface::class);

    // Mock configuration to enable field syncing
    $this->configurationService->expects($this->once())
      ->method('getClaimMapping')
      ->willReturn('given_name|field_first_name');

    $this->configurationService->expects($this->once())
      ->method('isSyncClaimMapping')
      ->willReturn(true);

    $this->configurationService->expects($this->once())
      ->method('getProfileFieldMappingRules')
      ->willReturn([
        'given_name' => 'field_first_name',
        'uid' => 'uid', // This should be filtered out
      ]);

    // Should only set the non-restricted field
    $user->expects($this->once())
      ->method('set')
      ->with('field_first_name', 'John');

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('syncAccountProfileFields');
    $method->setAccessible(true);

    $result = $method->invoke($this->service, $user, $auth0User);
    
    $this->assertTrue($result);
  }

}