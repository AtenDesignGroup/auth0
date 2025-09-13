<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Unit;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests configuration schema validation for Auth0 module.
 */
#[Group('auth0')]
class ConfigurationSchemaTest extends UnitTestCase {

  /**
   * Tests that auth0.settings schema file exists and has correct structure.
   */
  public function testAuth0SettingsSchemaStructure(): void {
    $schemaFile = __DIR__ . '/../../../config/schema/auth0.schema.yml';
    
    $this->assertFileExists($schemaFile, 'auth0.schema.yml file should exist');
    
    $schemaContent = file_get_contents($schemaFile);
    $this->assertNotFalse($schemaContent, 'Schema file should be readable');
    
    $schema = Yaml::parse($schemaContent);
    $this->assertIsArray($schema, 'Schema should be valid YAML');
    $this->assertArrayHasKey('auth0.settings', $schema, 'Should have auth0.settings key');
    
    $settingsSchema = $schema['auth0.settings'];
    $this->assertEquals('config_object', $settingsSchema['type']);
    $this->assertEquals('Auth0 settings', $settingsSchema['label']);
    $this->assertArrayHasKey('mapping', $settingsSchema);
  }

  /**
   * Tests that obsolete fields have been removed from schema.
   */
  public function testObsoleteFieldsRemovedFromSchema(): void {
    $schemaFile = __DIR__ . '/../../../config/schema/auth0.schema.yml';
    $schemaContent = file_get_contents($schemaFile);
    $schema = Yaml::parse($schemaContent);
    
    $mapping = $schema['auth0.settings']['mapping'];
    
    // These obsolete fields should NOT be in the schema anymore
    $obsoleteFields = [
      'auth0_form_title',
      'auth0_redirect_for_sso',
      'auth0_widget_cdn',
      'auth0_login_css',
      'auth0_lock_extra_settings',
      'auth0_allow_offline_access',
    ];
    
    foreach ($obsoleteFields as $field) {
      $this->assertArrayNotHasKey($field, $mapping, "Obsolete field '$field' should be removed from schema");
    }
  }

  /**
   * Tests that required fields are still present in schema.
   */
  public function testRequiredFieldsRemainingInSchema(): void {
    $schemaFile = __DIR__ . '/../../../config/schema/auth0.schema.yml';
    $schemaContent = file_get_contents($schemaFile);
    $schema = Yaml::parse($schemaContent);
    
    $mapping = $schema['auth0.settings']['mapping'];
    
    // These fields should remain in the schema
    $requiredFields = [
      'auth0_username_claim' => 'string',
      'auth0_role_mapping' => 'string',
      'auth0_claim_mapping' => 'string',
      'auth0_sync_role_mapping' => 'boolean',
      'auth0_sync_claim_mapping' => 'boolean',
      'auth0_requires_verified_email' => 'boolean',
      'auth0_client_id' => 'string',
      'auth0_client_secret' => 'string',
      'auth0_client_secret_key' => 'string',
      'auth0_domain' => 'string',
      'auth0_custom_domain' => 'string',
      'auth0_cookie_secret' => 'string',
      'auth0_cookie_secret_key' => 'string',
    ];
    
    foreach ($requiredFields as $field => $expectedType) {
      $this->assertArrayHasKey($field, $mapping, "Required field '$field' should remain in schema");
      $this->assertEquals($expectedType, $mapping[$field]['type'], "Field '$field' should have type '$expectedType'");
      $this->assertArrayHasKey('label', $mapping[$field], "Field '$field' should have a label");
    }
  }

  /**
   * Tests that install configuration file exists and has correct structure.
   */
  public function testAuth0SettingsInstallFileStructure(): void {
    $installFile = __DIR__ . '/../../../config/install/auth0.settings.yml';
    
    $this->assertFileExists($installFile, 'auth0.settings.yml install file should exist');
    
    $installContent = file_get_contents($installFile);
    $this->assertNotFalse($installContent, 'Install file should be readable');
    
    $config = Yaml::parse($installContent);
    $this->assertIsArray($config, 'Install config should be valid YAML');
  }

  /**
   * Tests that obsolete fields have been removed from install configuration.
   */
  public function testObsoleteFieldsRemovedFromInstallConfig(): void {
    $installFile = __DIR__ . '/../../../config/install/auth0.settings.yml';
    $installContent = file_get_contents($installFile);
    $config = Yaml::parse($installContent);
    
    // These obsolete fields should NOT be in the install config anymore
    $obsoleteFields = [
      'auth0_form_title',
      'auth0_redirect_for_sso', 
      'auth0_widget_cdn',
      'auth0_login_css',
      'auth0_lock_extra_settings',
      'auth0_allow_offline_access',
    ];
    
    foreach ($obsoleteFields as $field) {
      $this->assertArrayNotHasKey($field, $config, "Obsolete field '$field' should be removed from install config");
    }
  }

  /**
   * Tests that required fields are still present in install configuration.
   */
  public function testRequiredFieldsRemainingInInstallConfig(): void {
    $installFile = __DIR__ . '/../../../config/install/auth0.settings.yml';
    $installContent = file_get_contents($installFile);
    $config = Yaml::parse($installContent);
    
    // These fields should remain in the install configuration
    $requiredFields = [
      'auth0_username_claim',
      'auth0_role_mapping',
      'auth0_claim_mapping',
      'auth0_sync_role_mapping',
      'auth0_sync_claim_mapping',
      'auth0_requires_verified_email',
      'auth0_client_id',
      'auth0_client_secret',
      'auth0_client_secret_key',
      'auth0_domain',
      'auth0_custom_domain',
      'auth0_cookie_secret',
      'auth0_cookie_secret_key',
    ];
    
    foreach ($requiredFields as $field) {
      $this->assertArrayHasKey($field, $config, "Required field '$field' should remain in install config");
    }
  }

  /**
   * Tests install configuration has sensible default values.
   */
  public function testInstallConfigurationDefaults(): void {
    $installFile = __DIR__ . '/../../../config/install/auth0.settings.yml';
    $installContent = file_get_contents($installFile);
    $config = Yaml::parse($installContent);
    
    // Check specific default values
    $this->assertEquals('nickname', $config['auth0_username_claim'], 'Username claim should default to nickname');
    $this->assertEquals('', $config['auth0_role_mapping'], 'Role mapping should default to empty string');
    $this->assertEquals('', $config['auth0_claim_mapping'], 'Claim mapping should default to empty string');
    $this->assertFalse($config['auth0_sync_role_mapping'], 'Sync role mapping should default to false');
    $this->assertFalse($config['auth0_sync_claim_mapping'], 'Sync claim mapping should default to false');
    $this->assertFalse($config['auth0_requires_verified_email'], 'Requires verified email should default to false');
    
    // Check that credential fields default to empty strings
    $credentialFields = [
      'auth0_client_id',
      'auth0_client_secret', 
      'auth0_client_secret_key',
      'auth0_domain',
      'auth0_custom_domain',
      'auth0_cookie_secret',
      'auth0_cookie_secret_key',
    ];
    
    foreach ($credentialFields as $field) {
      $this->assertEquals('', $config[$field], "Credential field '$field' should default to empty string");
    }
  }

}