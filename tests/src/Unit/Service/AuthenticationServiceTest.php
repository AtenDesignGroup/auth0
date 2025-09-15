<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\auth0\Service\AuthenticationService;
use Drupal\auth0\Contracts\ClientServiceInterface;
use Drupal\auth0\Contracts\UserProvisionServiceInterface;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Drupal\auth0\Exception\AuthenticationLoginException;
use Drupal\auth0\ValueObject\Auth0User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\CoversClass;

// Include function mocks
require_once __DIR__ . '/DrupalFunctionMocks.php';

/**
 * Tests AuthenticationService functionality.
 */
#[Group('auth0')]
#[CoversClass(AuthenticationService::class)]
class AuthenticationServiceTest extends TestCase {

  private AuthenticationService $authenticationService;
  private ClientServiceInterface|MockObject $clientService;
  private UserProvisionServiceInterface|MockObject $userProvisionService;
  private ConfigurationServiceInterface|MockObject $configurationService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->clientService = $this->createMock(ClientServiceInterface::class);
    $this->userProvisionService = $this->createMock(UserProvisionServiceInterface::class);
    $this->configurationService = $this->createMock(ConfigurationServiceInterface::class);

    $this->authenticationService = new AuthenticationService(
      $this->clientService,
      $this->userProvisionService,
      $this->configurationService
    );
  }

  /**
   * Tests successful login handling.
   */
  public function testHandleLoginSuccess(): void {
    $request = new Request();
    $userInfo = ['sub' => 'auth0|123', 'email' => 'test@example.com'];
    $mockAuth0User = Auth0User::make($userInfo);

    $this->clientService
      ->expects($this->once())
      ->method('exchange')
      ->willReturn($mockAuth0User);

    $this->userProvisionService
      ->expects($this->once())
      ->method('login')
      ->with($mockAuth0User);

    $response = $this->authenticationService->handleLogin($request);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals('/user', $response->getTargetUrl());
  }

  /**
   * Tests login handling when Auth0 exchange returns null.
   */
  public function testHandleLoginWithNullExchange(): void {
    $request = new Request();

    $this->clientService
      ->expects($this->once())
      ->method('exchange')
      ->willReturn(null);

    $this->userProvisionService
      ->expects($this->never())
      ->method('login');

    $response = $this->authenticationService->handleLogin($request);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals('/user', $response->getTargetUrl());
  }

  /**
   * Tests login handling with error parameters.
   */
  public function testHandleLoginWithError(): void {
    $request = new Request(['error' => 'access_denied', 'error_description' => 'User denied access']);

    $this->expectException(AuthenticationLoginException::class);
    $this->expectExceptionMessage('User denied access');

    $this->authenticationService->handleLogin($request);
  }

  /**
   * Tests login handling with specific error codes.
   */
  public function testHandleLoginWithSpecificErrors(): void {
    $errorCodes = ['login_required', 'consent_required', 'interaction_required'];

    foreach ($errorCodes as $errorCode) {
      $request = new Request(['error' => $errorCode]);

      try {
        $this->authenticationService->handleLogin($request);
        $this->fail('Expected AuthenticationLoginException was not thrown for error: ' . $errorCode);
      } catch (AuthenticationLoginException $e) {
        $this->assertEquals($errorCode, $e->getMessage());
      }
    }
  }

  /**
   * Tests successful logout handling.
   */
  public function testHandleLogoutSuccess(): void {
    $request = new Request(['returnTo' => 'https://example.com/custom']);
    $expectedLogoutUrl = 'https://auth0.example.com/logout?returnTo=https://example.com/custom';

    $this->clientService
      ->expects($this->once())
      ->method('logoutUrl')
      ->with('https://example.com/custom')
      ->willReturn($expectedLogoutUrl);

    $response = $this->authenticationService->handleLogout($request);

    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertEquals($expectedLogoutUrl, $response->getTargetUrl());
  }

  /**
   * Tests logout handling with default return URL.
   */
  public function testHandleLogoutWithDefaultReturn(): void {
    $request = Request::create('https://example.com/logout');
    $expectedLogoutUrl = 'https://auth0.example.com/logout?returnTo=https://example.com';

    $this->clientService
      ->expects($this->once())
      ->method('logoutUrl')
      ->with('https://example.com')
      ->willReturn($expectedLogoutUrl);

    $response = $this->authenticationService->handleLogout($request);

    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertEquals($expectedLogoutUrl, $response->getTargetUrl());
  }

  /**
   * Tests logout handling with exception.
   */
  public function testHandleLogoutWithException(): void {
    $request = new Request();

    $this->clientService
      ->expects($this->once())
      ->method('logoutUrl')
      ->willThrowException(new \Exception('Auth0 error'));

    $response = $this->authenticationService->handleLogout($request);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals('/', $response->getTargetUrl());
  }

  /**
   * Tests successful login page handling.
   */
  public function testHandleLoginPageSuccess(): void {
    $request = new Request();
    $expectedLoginUrl = 'https://auth0.example.com/authorize?client_id=123';

    $this->clientService
      ->expects($this->once())
      ->method('loginUrl')
      ->willReturn($expectedLoginUrl);

    $response = $this->authenticationService->handleLoginPage($request);

    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertEquals($expectedLoginUrl, $response->getTargetUrl());
  }

  /**
   * Tests login page handling with exception.
   */
  public function testHandleLoginPageWithException(): void {
    $request = new Request();

    $this->clientService
      ->expects($this->once())
      ->method('loginUrl')
      ->willThrowException(new \Exception('Auth0 error'));

    $response = $this->authenticationService->handleLoginPage($request);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals('/', $response->getTargetUrl());
  }

  /**
   * Tests login with various JWT token scenarios.
   */
  public function testHandleLoginWithJwtTokens(): void {
    // Test with full profile data
    $fullUserInfo = [
      'sub' => 'auth0|507f1f77bcf86cd799439011',
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'email_verified' => true,
      'picture' => 'https://example.com/pic.jpg',
      'iss' => 'https://auth0.example.com/',
      'aud' => 'client_id_123',
      'iat' => time(),
      'exp' => time() + 3600,
    ];

    $request = new Request();
    $mockAuth0User = Auth0User::make($fullUserInfo);

    $this->clientService
      ->expects($this->once())
      ->method('exchange')
      ->willReturn($mockAuth0User);

    $this->userProvisionService
      ->expects($this->once())
      ->method('login')
      ->with($mockAuth0User);

    $response = $this->authenticationService->handleLogin($request);

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals('/user', $response->getTargetUrl());
  }

}