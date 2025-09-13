<?php

declare(strict_types=1);

namespace Drupal\auth0\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\auth0\Contracts\ConfigurationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the basic advanced settings form.
 */
class BasicAdvancedForm extends ConfigFormBase {

  /**
   * Constructs a new BasicAdvancedForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configurationService
   *   The Auth0 configuration service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected ConfigurationServiceInterface $configurationService,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('auth0.configuration'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'auth0_basic_advanced_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['auth0_requires_verified_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Requires verified email'),
      '#default_value' => $this->configurationService->isRequiresVerifiedEmail(),
      '#description' => $this->t('If checked, the user must have a verified email to log in.'),
    ];
    $form['auth0_user_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('User Mapping'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];
    $form['auth0_user_mapping']['auth0_username_claim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Auth0 claims to Drupal username.'),
      '#default_value' => $this->configurationService->getUsernameClaim(),
      '#description' => $this->t('Define the Auth0 claim to use for the Drupal username.'),
      '#required' => TRUE,
    ];
    $form['auth0_user_mapping']['auth0_claim_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping of Claims to Profile Fields (one per line):'),
      '#cols' => 50,
      '#rows' => 5,
      '#default_value' => $this->configurationService->getClaimMapping(),
      '#description' => $this->t('Input claim mappings here in the format
        [claim_name]|[profile_field_name] (one per line), e.g:
        <br/>given_name|field_first_name
        <br/>family_name|field_last_name'),
    ];
    $form['auth0_user_mapping']['auth0_sync_claim_mapping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync claim mapping on login'),
      '#description' => $this->t('If checked, the claim mapping will be synced on every login.'),
      '#default_value' => $this->configurationService->isSyncClaimMapping(),
      '#states' => [
        'visible' => [
          ':input[name="auth0_claim_mapping"]' => ['!value' => ''],
        ],
      ],
    ];
    $form['auth0_user_mapping']['auth0_role_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping of Claim Role Values to Drupal Roles (one per line)'),
      '#default_value' => $this->configurationService->getRoleMapping(),
      '#description' => $this->t('Input role mappings here in the format
        [Auth0 claim value]|[Drupal role name] (one per line), e.g.:
        <br/>admin|administrator
        <br/>poweruser|power users'),
    ];
    $form['auth0_user_mapping']['auth0_sync_role_mapping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync role mapping on login'),
      '#description' => $this->t('If checked, the role mapping will be synced on every login.'),
      '#default_value' => $this->configurationService->isSyncRoleMapping(),
      '#states' => [
        'visible' => [
          ':input[name="auth0_role_mapping"]' => ['!value' => ''],
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
  public function validateForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    // Validate role mapping rules format
    $auth0_role_mapping = $form_state->getValue('auth0_role_mapping');
    if (!empty($auth0_role_mapping)) {
      $lines = array_filter(explode("\n", trim($auth0_role_mapping)));
      foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
          continue;
        }

        // Check for a valid pipe separator
        if (!str_contains($line, '|')) {
          $form_state->setErrorByName('auth0_role_mapping',
            $this->t('Invalid format. Use "auth0_role|drupal_role".')
          );
          continue;
        }

        $parts = explode('|', $line, 2);
        if (count($parts) !== 2) {
          continue;
        }

        $auth0_role = trim($parts[0]);
        $drupal_role = trim($parts[1]);

        // Validate both parts are not empty
        if (empty($auth0_role) || empty($drupal_role)) {
          $form_state->setErrorByName('auth0_role_mapping',
            $this->t('Auth0 role and Drupal role cannot be empty.')
          );
          continue;
        }

        // Check if a Drupal role exists (except for built-in roles)
        if (!in_array($drupal_role, ['authenticated', 'anonymous'])) {
          $role_storage = $this->entityTypeManager->getStorage('user_role');
          if (!$role_storage->load($drupal_role)) {
            $form_state->setErrorByName('auth0_role_mapping',
              $this->t('The Drupal role "@role" does not exist.', [
                '@role' => $drupal_role,
              ])
            );
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    $this->configurationService->setMultiple([
      'auth0_username_claim' => $form_state->getValue('auth0_username_claim'),
      'auth0_role_mapping' => $form_state->getValue('auth0_role_mapping'),
      'auth0_claim_mapping' => $form_state->getValue('auth0_claim_mapping'),
      'auth0_sync_role_mapping' => $form_state->getValue('auth0_sync_role_mapping'),
      'auth0_sync_claim_mapping' => $form_state->getValue('auth0_sync_claim_mapping'),
      'auth0_requires_verified_email' => $form_state->getValue('auth0_requires_verified_email'),
    ]);

    $this->messenger()->addStatus(
      $this->t('The Auth0 advanced settings have been saved.')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'auth0.settings',
    ];
  }

}
