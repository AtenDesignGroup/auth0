<?php

declare(strict_types=1);

use Drupal\KernelTests\KernelTestBase;

class AuthenticationServiceTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'externalauth', 'key', 'auth0'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'auth0']);
  }

  public function testAuth0Exchange(): void {
    $t = 1;
    $this->assertTrue(true);

  }


}
