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
    return 'auth0_basic_advanced_form';
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
    $form['auth0_form_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form title'),
      '#default_value' => $this->configurationService->getFormTitle() ?: $this->t('Sign In'),
      '#description' => $this->t('This is the title for the login widget.'),
    ];

    $form['auth0_allow_signup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow user signup'),
      '#default_value' => $this->configurationService->isAllowSignup(),
      '#description' => $this->t('If you have a database connection, you can allow users to sign up in the widget.'),
    ];

    $form['auth0_allow_offline_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send a Refresh Token in the Sign-in Event for offline access'),
      '#default_value' => $this->configurationService->isOfflineAccess(),
      '#description' => $this->t('If you need a refresh token for refreshing an expired session, set this to true, and then a refresh token will be sent in the Sign-in Event.'),
    ];

    $form['auth0_redirect_for_sso'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Universal Login Page'),
      '#default_value' => $this->configurationService->isRedirectForSso(),
      '#description' => $this->t('If you are supporting SSO for your customers for other apps, including this application, click this to redirect to your Auth0 Universal Login Page for authentication.'),
    ];

    $form['auth0_widget_cdn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lock JS CDN URL'),
      '#default_value' => $this->configurationService->getWidgetCdn(),
      '#description' => $this->t('Point this to the latest Lock JS version available in the CDN.') . ' ' .
      sprintf(
        '<a href="https://github.com/auth0/lock/releases" target="_blank">%s</a>',
        $this->t('Available Lock JS versions.')
      ),
    ];

    $form['auth0_requires_verified_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Requires verified email'),
      '#default_value' => $this->configurationService->isRequiresVerifiedEmail(),
      '#description' => $this->t('Mark this if you require the user to have a verified email to login.'),
    ];

    $form['auth0_join_user_by_mail_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link Auth0 logins to Drupal users by email address'),
      '#default_value' => $this->configurationService->isJoinUserByMailEnabled(),
      '#description' => $this->t('If enabled, when a user logs into Drupal for the first time, the system will use the email address of the Auth0 user to search for a Drupal user with the same email address and setup a link to that Drupal user account. <br/>If not enabled, then a new Drupal user will be created even if a Drupal user with the same email address already exists.'),
    ];

    $form['auth0_username_claim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Auth0 claims to Drupal username.'),
      '#default_value' => $this->configurationService->getUsernameClaim(),
      '#description' => $this->t('Maps the given claim field as the Drupal username field. The default is the nickname claim.'),
      '#required' => TRUE,
    ];

    $form['auth0_login_css'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login widget CSS'),
      '#default_value' => $this->configurationService->getLoginCss(),
      '#description' => $this->t('CSS to control the Auth0 login form appearance.'),
    ];

    $form['auth0_lock_extra_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Lock extra settings'),
      '#default_value' => $this->configurationService->getLockExtraSettings(),
      '#description' => $this->t('Valid JSON to pass to the Lock options parameter. Options passed here will override Drupal admin settings. <a href="@link" target="_blank">More information and examples.</a>', ['@link' => 'https://auth0.com/docs/libraries/lock/v11/configuration']),
    ];

    $form['auth0_auto_register'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Register Auth0 users (ignore site registration settings)'),
      '#default_value' => $this->configurationService->isAutoRegister(),
      '#description' => $this->t('Enable this option if you want new Auth0 users to automatically be activated within Drupal regardless of the global site visitor registration settings (e.g. requiring admin approval).'),
    ];

    $form['auth0_claim_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping of Claims to Profile Fields (one per line):'),
      '#cols' => 50,
      '#rows' => 5,
      '#default_value' => $this->configurationService->getClaimMapping(),
      '#description' => $this->t('Enter claim mappings here in the format &lt;claim_name>|&lt;profile_field_name> (one per line), e.g:
        <br/>given_name|field_first_name
        <br/>family_name|field_last_name
        <br/>
        <br/>NOTE: the following Drupal fields are handled automatically and will be ignored if specified above:
        <br/>    uid, name, mail, init, is_new, status, pass
        <br/>&nbsp;
        '),
    ];

    $form['auth0_claim_to_use_for_role'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Claim for Role Mapping:'),
      '#default_value' => $this->configurationService->getClaimToUseForRole(),
      '#description' => $this->t('Name of the claim to use to map to Drupal roles, e.g. roles.  If the claim contains a list of values, all values will be used in the mappings below.'),
    ];

    $form['auth0_role_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping of Claim Role Values to Drupal Roles (one per line)'),
      '#default_value' => $this->configurationService->getRoleMapping(),
      '#description' => $this->t('Enter role mappings here in the format &lt;Auth0 claim value>|&lt;Drupal role name> (one per line), e.g.:
        <br/>admin|administrator
        <br/>poweruser|power users
        <br/>
        <br/>NOTE: for any Drupal role in the mapping, if a user is not mapped to the role, the role will be removed from their profile.
        Drupal roles not listed above will not be changed by this module.
        <br/>&nbsp;
        '),
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
    $lock_extra = $form_state->getValue('auth0_lock_extra_settings');
    if (!empty($lock_extra) && !json_validate($lock_extra)) {
      $form_state->setErrorByName('auth0_lock_extra_settings',
        $this->t('Lock extra settings must be a valid JSON format')
      );
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
      'auth0_form_title' => $form_state->getValue('auth0_form_title'),
      'auth0_allow_signup' => $form_state->getValue('auth0_allow_signup'),
      'auth0_allow_offline_access' => $form_state->getValue('auth0_allow_offline_access'),
      'auth0_redirect_for_sso' => $form_state->getValue('auth0_redirect_for_sso'),
      'auth0_widget_cdn' => $form_state->getValue('auth0_widget_cdn'),
      'auth0_requires_verified_email' => $form_state->getValue('auth0_requires_verified_email'),
      'auth0_join_user_by_mail_enabled' => $form_state->getValue('auth0_join_user_by_mail_enabled'),
      'auth0_username_claim' => $form_state->getValue('auth0_username_claim'),
      'auth0_login_css' => $form_state->getValue('auth0_login_css'),
      'auth0_auto_register' => $form_state->getValue('auth0_auto_register'),
      'auth0_lock_extra_settings' => $form_state->getValue('auth0_lock_extra_settings'),
      'auth0_claim_mapping' => $form_state->getValue('auth0_claim_mapping'),
      'auth0_claim_to_use_for_role' => $form_state->getValue('auth0_claim_to_use_for_role'),
      'auth0_role_mapping' => $form_state->getValue('auth0_role_mapping'),
    ]);

    $this->messenger()->addStatus(
      $this->t('The Auth0 advanced settings have been saved.')
    );
  }

}
