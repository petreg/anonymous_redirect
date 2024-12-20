<?php

namespace Drupal\custom_anonymous_redirect\Tests\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests for the Anonymous Redirect config form.
 *
 * @group custom_anonymous_redirect
 */
class CustomAnonymousRedirectConfigTest extends BrowserTestBase {

  /**
   * Test user.
   */
  protected UserInterface $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['custom_anonymous_redirect', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Change the autogenerated stub.
    parent::setUp();

    $user = $this->drupalCreateUser([
      'access content',
      'administer site configuration',
    ]);

    if ($user === FALSE) {
      $this->fail('Unable to create user.');
    }

    $this->user = $user;
  }

  /**
   * Test if config form works as expected.
   */
  public function testConfigFormWorks(): void {
    // Make the user an administrator.
    $this->user->addRole('administrator');
    $this->user->removeRole('anonymous');

    // Test that config form exists at specified route.
    $this->drupalLogin($this->user);
    $this->drupalGet('/admin/config/system/anonymous-redirect');
    $this->assertSession()->statusCodeEquals(200);

    $config = $this->config('custom_anonymous_redirect.settings');

    // Check that enable_custom_anonymous_redirect field exists with an appropriate
    // value.
    $this->assertSession()->fieldValueEquals('enable_custom_anonymous_redirect', $config->get('enable_redirect'));

    // Check that redirect_base_url field exist with an appropriate value.
    $this->assertSession()->fieldValueEquals('redirect_base_url', $config->get('redirect_url'));

    // Check that redirect_url_overrides field exists with an appropriate value.
    $this->assertSession()->fieldValueEquals('redirect_url_overrides', $config->get('redirect_url_overrides'));
    $this->drupalGet('/admin/config/system/anonymous-redirect');

    // Check that the form saves correctly with appropriate values.
    $this->submitForm([
      'enable_custom_anonymous_redirect' => TRUE,
      'redirect_base_url' => '<front>',
      'redirect_url_overrides' => '/test',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('/admin/config/system/anonymous-redirect');
    $this->assertSession()->statusCodeEquals(200);

    // Check to see if the values were set correctly.
    $this->assertSession()->fieldValueEquals('enable_custom_anonymous_redirect', '1');
    $this->assertSession()->fieldValueEquals('redirect_base_url', '<front>');
    $this->assertSession()->fieldValueEquals('redirect_url_overrides', '/test');
  }

}
