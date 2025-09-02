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

  protected static $modules = ['system', 'user', 'key', 'auth0'];

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
    $this->assertArrayHasKey('auth0_form_title', $form);
    $this->assertArrayHasKey('auth0_allow_signup', $form);
    $this->assertArrayHasKey('auth0_requires_verified_email', $form);
    $this->assertArrayHasKey('auth0_join_user_by_mail_enabled', $form);
  }

  /**
   * Tests that form validation works correctly with valid values.
   */
  public function testFormValidationWorksCorrectly(): void {
    $form_object = BasicAdvancedForm::create($this->container);
    $form_state = new FormState();

    // Test form validation with valid values.
    $form_state->setValues([
      'auth0_form_title' => 'Valid Title',
      'auth0_username_claim' => 'email',
      'auth0_claim_mapping' => 'email|mail',
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->validateForm($form, $form_state);

    // Should have no validation errors.
    $errors = $form_state->getErrors();
    $this->assertEmpty($errors);
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
