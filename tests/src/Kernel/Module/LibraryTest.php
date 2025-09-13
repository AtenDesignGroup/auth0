<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel\Module;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Auth0 module library functionality.
 */
#[Group('auth0')]
class LibraryTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'externalauth', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  /**
   * Tests that obsolete hook_library_info_build is removed.
   */
  public function testObsoleteLibraryHookRemoved(): void {
    // Verify the obsolete hook_library_info_build function no longer exists
    $this->assertFalse(
      function_exists('auth0_library_info_build'),
      'The obsolete auth0_library_info_build hook should be removed'
    );
  }

  /**
   * Tests that core auth0 theme hooks still work.
   */
  public function testCoreThemeHooksStillWork(): void {
    // Verify auth0_theme hook still exists for core functionality
    $this->assertTrue(
      function_exists('auth0_theme'),
      'The core auth0_theme hook should still exist'
    );

    // Test the theme hook returns expected structure
    $theme_info = auth0_theme();
    $this->assertArrayHasKey('auth0_login', $theme_info);
    $this->assertArrayHasKey('template', $theme_info['auth0_login']);
    $this->assertArrayHasKey('variables', $theme_info['auth0_login']);
  }

  /**
   * Tests that JavaScript functionality can work without widget CDN library.
   */
  public function testJavaScriptFunctionalityWithoutWidgetCdn(): void {
    $library_discovery = $this->container->get('library.discovery');
    
    // Verify the auth0.lock library exists (defined in auth0.libraries.yml)
    $lock_library = $library_discovery->getLibraryByName('auth0', 'auth0.lock');
    $this->assertNotEmpty($lock_library, 'The auth0.lock library should exist');
    
    // Verify core library functionality is intact
    $this->assertArrayHasKey('js', $lock_library);
  }

}