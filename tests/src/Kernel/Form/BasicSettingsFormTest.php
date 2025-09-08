<?php

declare(strict_types=1);

namespace Drupal\Tests\auth0\Kernel\Form;

use PHPUnit\Framework\Attributes\Group;
use Drupal\auth0\Form\BasicSettingsForm;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests BasicSettingsForm functionality.
 */
#[Group('auth0')]
class BasicSettingsFormTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'externalauth', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  /**
   * Tests that the form can be built with expected fields.
   */
  public function testFormCanBeBuilt(): void {
    $form_object = BasicSettingsForm::create($this->container);
    $form_state = new FormState();

    $form = $form_object->buildForm([], $form_state);

    // Test that form contains expected Auth0 fields.
    $this->assertArrayHasKey('auth0_domain', $form);
    $this->assertArrayHasKey('auth0_client_id', $form);
    $this->assertArrayHasKey('auth0_client_secret', $form);
    $this->assertArrayHasKey('auth0_cookie_secret', $form);
    $this->assertArrayHasKey('auth0_client_secret_key', $form);
    $this->assertArrayHasKey('auth0_cookie_secret_key', $form);
  }

  /**
   * Tests form validation with invalid domain format.
   */
  public function testFormValidationWithInvalidDomain(): void {
    $form_object = BasicSettingsForm::create($this->container);
    $form_state = new FormState();

    // Test with invalid domain format.
    $form_state->setValues([
      'auth0_domain' => 'invalid-domain',
      'auth0_client_id' => 'test_client',
      'auth0_client_secret' => 'test_secret_with_enough_length_for_validation',
      'auth0_cookie_secret' => 'test_cookie_secret_with_enough_length',
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertArrayHasKey('auth0_domain', $errors);
  }

  /**
   * Tests form validation requires either key or direct secret values.
   */
  public function testFormValidationRequiresSecrets(): void {
    $form_object = BasicSettingsForm::create($this->container);
    $form_state = new FormState();

    // Test without providing either key or direct secret values.
    $form_state->setValues([
      'auth0_domain' => 'test.auth0.com',
      'auth0_client_id' => 'test_client',
      'auth0_client_secret_key' => '',
      'auth0_client_secret' => '',
      'auth0_cookie_secret_key' => '',
      'auth0_cookie_secret' => '',
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertArrayHasKey('auth0_client_secret', $errors);
    $this->assertArrayHasKey('auth0_cookie_secret', $errors);
  }

  /**
   * Tests form validation checks cookie secret length requirement.
   */
  public function testFormValidationChecksCookieSecretLength(): void {
    $form_object = BasicSettingsForm::create($this->container);
    $form_state = new FormState();

    // Test with short cookie secret.
    $form_state->setValues([
      'auth0_domain' => 'test.auth0.com',
      'auth0_client_id' => 'test_client',
      'auth0_client_secret' => 'valid_client_secret',
      'auth0_cookie_secret' => 'short',
    ]);

    $form = $form_object->buildForm([], $form_state);
    $form_object->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertArrayHasKey('auth0_cookie_secret', $errors);
  }

}
