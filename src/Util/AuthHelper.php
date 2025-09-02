<?php

namespace Drupal\auth0\Util;

/**
 * @file
 * Contains \Drupal\auth0\Util\AuthHelper.
 */

use Auth0\SDK\Utility\HttpTelemetry;

use Drupal\auth0\Contracts\ConfigurationServiceInterface;

/**
 * Controller routines for auth0 authentication.
 */
class AuthHelper {
  const AUTH0_DOMAIN = 'auth0_domain';
  const AUTH0_CUSTOM_DOMAIN = 'auth0_custom_domain';

  /**
   * The configuration service.
   *
   * @var \Drupal\auth0\Contracts\ConfigurationServiceInterface
   */
  protected ConfigurationServiceInterface $configurationService;

  /**
   * Auth0 domain.
   *
   * @var array|mixed|null
   */
  private $domain;

  /**
   * Auth0 custom domain.
   *
   * @var array|mixed|null
   */
  private $customDomain;

  /**
   * Initialize the Helper.
   *
   * @param \Drupal\auth0\Contracts\ConfigurationServiceInterface $configuration_service
   *   The configuration service.
   */
  public function __construct(ConfigurationServiceInterface $configuration_service) {
    $this->configurationService = $configuration_service;
    $this->domain = $this->configurationService->getDomain();
    $this->customDomain = $this->configurationService->getCustomDomain();

    self::setTelemetry();
  }

  /**
   * Extend Auth0 PHP SDK telemetry to report for Drupal.
   */
  public static function setTelemetry() {
    HttpTelemetry::setPackage('auth0-drupal', AUTH0_MODULE_VERSION);
  }

  /**
   * Return the custom domain, if one has been set.
   *
   * @return mixed
   *   A string with the domain name
   *   A empty string if the config is not set
   */
  public function getAuthDomain() {
    return !empty($this->customDomain) ? $this->customDomain : $this->domain;
  }

  /**
   * Get the tenant CDN base URL based on the Application domain.
   *
   * @param string $domain
   *   Tenant domain.
   *
   * @return string
   *   Tenant CDN base URL
   */
  public static function getTenantCdn($domain) {
    preg_match('/^[\w\d\-_0-9]+\.([\w\d\-_0-9]*)[\.]*auth0\.com$/', $domain, $matches);
    return 'https://cdn' .
      (empty($matches[1]) || $matches[1] == 'us' ? '' : '.' . $matches[1])
      . '.auth0.com';
  }

}
