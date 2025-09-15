<?php

/**
 * Mock Drupal functions for unit testing.
 */

namespace Drupal\auth0\Service {
  
  if (!function_exists('user_logout')) {
    function user_logout() {
      // Mock implementation for testing
      return TRUE;
    }
  }
  
  if (!function_exists('t')) {
    function t($string, $args = [], $options = []) {
      return $string;
    }
  }
}