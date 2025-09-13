<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel\Form;

use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use Drupal\auth0\Form\BasicAdvancedForm;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests BasicAdvancedForm functionality.
 */
#[Group('auth0')]
class BasicAdvancedFormTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'externalauth', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  /**
   * Tests that the advanced form can be built with expected fields.
   */
  public function testFormCanBeBuilt(): void {
    $form_object = BasicAdvancedForm::create($this->container);
    $form_state = new FormState();

    $form = $form_object->buildForm([], $form_state);

    // Test that form contains expected advanced Auth0 fields.
    $this->assertArrayHasKey('auth0_requires_verified_email', $form);
    $this->assertArrayHasKey('auth0_user_mapping', $form);
    
    // Check nested fields under user_mapping (since #tree is FALSE, they should be accessible)
    $this->assertArrayHasKey('auth0_username_claim', $form['auth0_user_mapping']);
    $this->assertArrayHasKey('auth0_claim_mapping', $form['auth0_user_mapping']);
    $this->assertArrayHasKey('auth0_sync_claim_mapping', $form['auth0_user_mapping']);
    $this->assertArrayHasKey('auth0_role_mapping', $form['auth0_user_mapping']);
    $this->assertArrayHasKey('auth0_sync_role_mapping', $form['auth0_user_mapping']);
  }

  /**
   * Tests that obsolete configuration fields are not present in the form.
   */
  public function testObsoleteFieldsNotPresent(): void {
    $form_object = BasicAdvancedForm::create($this->container);
    $form_state = new FormState();

    $form = $form_object->buildForm([], $form_state);

    // Test that obsolete fields are NOT in the form.
    $this->assertArrayNotHasKey('auth0_form_title', $form);
    $this->assertArrayNotHasKey('auth0_redirect_for_sso', $form);
    $this->assertArrayNotHasKey('auth0_widget_cdn', $form);
    $this->assertArrayNotHasKey('auth0_login_css', $form);
    $this->assertArrayNotHasKey('auth0_lock_extra_settings', $form);
    $this->assertArrayNotHasKey('auth0_allow_offline_access', $form);
  }

  /**
   * Tests that form validation works correctly with valid values.
   */
  public function testFormValidationWorksCorrectly(): void {
    $form_object = BasicAdvancedForm::create($this->container);
    $form_state = new FormState();

    // Test form validation with valid values including required username_claim.
    $form_state->setValues([
      'auth0_username_claim' => 'email',
      'auth0_claim_mapping' => 'email|mail',
      'auth0_role_mapping' => 'admin|authenticated',  // Use 'authenticated' which should exist
      'auth0_requires_verified_email' => FALSE,
      'auth0_sync_claim_mapping' => FALSE,
      'auth0_sync_role_mapping' => FALSE,
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->validateForm($form, $form_state);

    // Should have no validation errors.
    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      // Debug: show what errors occurred
      $this->fail('Validation errors: ' . print_r($errors, TRUE));
    }
    $this->assertEmpty($errors);
  }

  /**
   * Tests that validation no longer processes obsolete lock_extra_settings field.
   */
  public function testObsoleteLockExtraSettingsValidationRemoved(): void {
    $form_object = BasicAdvancedForm::create($this->container);
    $form_state = new FormState();

    // Set values including obsolete fields that should no longer be validated.
    $form_state->setValues([
      'auth0_lock_extra_settings' => 'invalid_json_content',
      'auth0_username_claim' => 'email',
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->validateForm($form, $form_state);

    // Should have no validation errors for lock_extra_settings since it's removed.
    $errors = $form_state->getErrors();
    $this->assertEmpty($errors, 'Lock extra settings validation should be removed');
  }

  /**
   * Tests that obsolete fields are not saved in submitForm().
   */
  public function testObsoleteFieldsNotSaved(): void {
    $form_object = BasicAdvancedForm::create($this->container);
    $form_state = new FormState();

    // Mock configuration service to track what gets saved.
    $config_service = $this->createMock(ConfigurationServiceInterface::class);
    
    // Expect setMultiple to be called, but verify obsolete fields are not included.
    $config_service->expects($this->once())
      ->method('setMultiple')
      ->with($this->callback(function($data) {
        // Verify obsolete fields are NOT in the data being saved.
        $obsolete_fields = [
          'auth0_form_title',
          'auth0_redirect_for_sso', 
          'auth0_widget_cdn',
          'auth0_login_css',
          'auth0_lock_extra_settings',
          'auth0_allow_offline_access',
        ];
        
        foreach ($obsolete_fields as $field) {
          if (array_key_exists($field, $data)) {
            return false;
          }
        }
        return true;
      }));

    // Set the mocked configuration service.
    $reflection = new \ReflectionClass($form_object);
    $property = $reflection->getProperty('configurationService');
    $property->setAccessible(TRUE);
    $property->setValue($form_object, $config_service);

    // Set form values including both valid and obsolete fields.
    $form_state->setValues([
      'auth0_username_claim' => 'email',
      'auth0_requires_verified_email' => TRUE,
      // These obsolete fields should not be saved even if present.
      'auth0_form_title' => 'Should not be saved',
      'auth0_lock_extra_settings' => '{"test": "value"}',
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->submitForm($form, $form_state);
  }

  /**
   * Tests that form integrates properly with ConfigurationService.
   */
  public function testFormIntegratesWithConfigurationService(): void {
    $form_object = BasicAdvancedForm::create($this->container);

    // Verify the form has access to ConfigurationService.
    $reflection = new \ReflectionClass($form_object);
    $property = $reflection->getProperty('configurationService');
    $property->setAccessible(TRUE);
    $config_service = $property->getValue($form_object);

    $this->assertInstanceOf(ConfigurationServiceInterface::class, $config_service);
  }

}
