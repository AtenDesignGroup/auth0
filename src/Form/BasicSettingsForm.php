<?php

declare(strict_types=1);

namespace Drupal\auth0\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form handles the basic module configurations.
 */
class BasicSettingsForm extends ConfigFormBase {

  /**
   * The JWT signing algorithm configuration key.
   *
   * @var string
   */
  protected const string AUTH0_JWT_SIGNING_ALGORITHM = 'auth0_jwt_signature_alg';

  /**
   * Constructs a new BasicSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configurationService
   *   The Auth0 configuration service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected ConfigurationServiceInterface $configurationService,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('auth0.configuration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'auth0_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'auth0.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['auth0_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->configurationService->getDomain(),
      '#description' => $this->t('The Auth0 Domain for this Application, found
      in the Auth0 Dashboard.'),
      '#required' => TRUE,
    ];

    $form['auth0_custom_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Domain'),
      '#default_value' => $this->configurationService->getCustomDomain() ?? '',
      '#description' => $this->t('Your Auth0 custom domain, if in use.'),
      '#required' => FALSE,
    ];

    $form['auth0_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configurationService->getClientId(),
      '#description' => $this->t('Client ID from the Application settings page
      in your Auth0 dashboard.'),
      '#required' => TRUE,
    ];

    $form['auth0_client_secret_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Client Secret (Key)'),
      '#default_value' => $this->configurationService->getClientSecretKey(),
      '#description' => $this->t('Select a Key entity containing your Auth0
      Client Secret. <br/><strong>Recommended for security.</strong>'),
      '#required' => FALSE,
    ];

    $form['auth0_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret (Direct)'),
      '#default_value' => $this->configurationService->get('auth0_client_secret', ''),
      '#description' => $this->t('Client Secret from the Application settings
      page in your Auth0 dashboard.
      <br/><strong>Use the Key field above for better security.</strong>'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="auth0_client_secret_key"]' => ['value' => ''],
        ],
      ],
    ];

    $form['auth0_secret_base64_encoded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Client Secret is base64 Encoded'),
      '#default_value' => $this->configurationService->getSecretBase64Encoded(),
      '#description' => $this->t('This is stated below the Client Secret field
      on the Application settings page in your Auth0 dashboard.'),
    ];

    $form[static::AUTH0_JWT_SIGNING_ALGORITHM] = [
      '#type' => 'select',
      '#title' => $this->t('JWT Signature Algorithm'),
      '#options' => [
        'RS256' => $this->t('RS256'),
      ],
      '#default_value' => $this->configurationService->getJwtSigningAlgorithm(),
      '#description' => $this->t('The recommended signing algorithm for the
      JWT (JSON Web Token) used in the ID token is RS256. <br/> This setting must
      be configured in the advanced settings for this client under the OAuth tab.'),
      '#required' => TRUE,
    ];

    $form['auth0_cookie_secret_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Cookie Secret (Key)'),
      '#default_value' => $this->configurationService->getCookieSecretKey(),
      '#description' => $this->t('Select a Key entity containing your Auth0 Cookie Secret.
      <br/><strong>Recommended for security.</strong>'),
      '#required' => FALSE,
    ];

    $form['auth0_cookie_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Secret (Direct)'),
      '#default_value' => $this->configurationService->get('auth0_cookie_secret', ''),
      '#description' => $this->t('The secret is used to derive an encryption key
      for the user identity in a session cookie and to sign the transient cookies
      used by the login callback. <br/><strong>Use the Key field above for
      better security.</strong>'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="auth0_cookie_secret_key"]' => ['value' => ''],
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $domain = $form_state->getValue('auth0_domain');
    if (!empty($domain) && !preg_match('/^[a-zA-Z0-9\-_]+\.auth0\.com$/', $domain)) {
      $form_state->setErrorByName(
        'auth0_domain',
        $this->t('Please enter a valid Auth0 domain (e.g., your-tenant.auth0.com)')
      );
    }

    // Validate Custom Domain format (if provided)
    $custom_domain = $form_state->getValue('auth0_custom_domain');
    if (!empty($custom_domain) && !preg_match('/^[a-zA-Z0-9\-_]+\.[a-zA-Z]{2,}$/', $custom_domain)) {
      $form_state->setErrorByName(
        'auth0_custom_domain',
        $this->t('Please enter a valid custom domain')
      );
    }

    // Validate Client Secret - either Key or direct value required.
    $client_secret_key = $form_state->getValue('auth0_client_secret_key');
    $client_secret = $form_state->getValue('auth0_client_secret');
    if (empty($client_secret_key) && empty($client_secret)) {
      $form_state->setErrorByName(
        'auth0_client_secret',
        $this->t('Please provide either a Client Secret Key or direct Client Secret value')
      );
    }

    // Validate Cookie Secret - either Key or direct value required.
    $cookie_secret_key = $form_state->getValue('auth0_cookie_secret_key');
    $cookie_secret = $form_state->getValue('auth0_cookie_secret');
    if (empty($cookie_secret_key) && empty($cookie_secret)) {
      $form_state->setErrorByName(
        'auth0_cookie_secret',
        $this->t('Please provide either a Cookie Secret Key or direct Cookie Secret value')
      );
    }

    // Validate Cookie Secret length for security (only if using direct value)
    if (!empty($cookie_secret) && strlen($cookie_secret) < 32) {
      $form_state->setErrorByName(
        'auth0_cookie_secret',
        $this->t('Cookie secret should be at least 32 characters long for security')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Use ConfigurationService for centralized configuration management.
    $this->configurationService->setMultiple([
      'auth0_client_id' => $form_state->getValue('auth0_client_id'),
      'auth0_client_secret' => $form_state->getValue('auth0_client_secret'),
      'auth0_client_secret_key' => $form_state->getValue('auth0_client_secret_key'),
      'auth0_domain' => $form_state->getValue('auth0_domain'),
      'auth0_custom_domain' => $form_state->getValue('auth0_custom_domain'),
      static::AUTH0_JWT_SIGNING_ALGORITHM => $form_state->getValue(static::AUTH0_JWT_SIGNING_ALGORITHM),
      'auth0_secret_base64_encoded' => $form_state->getValue('auth0_secret_base64_encoded'),
      'auth0_cookie_secret' => $form_state->getValue('auth0_cookie_secret'),
      'auth0_cookie_secret_key' => $form_state->getValue('auth0_cookie_secret_key'),
    ]);

    $this->messenger()->addStatus(
      $this->t('The Auth0 settings have been saved.')
    );
  }

}
