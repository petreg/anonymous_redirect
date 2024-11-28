<?php

namespace Drupal\custom_anonymous_redirect\Tests\Kernel;

use Drupal\custom_anonymous_redirect\EventSubscriber\AnonymousRedirectSubscriber;
use Drupal\Core\Config\Config;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests the anonymous redirect subscriber.
 *
 * @group custom_anonymous_redirect
 */
class CustomAnonymousRedirectSubscriberTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Path matcher service.
   */
  protected PathMatcherInterface $pathMatcher;

  /**
   * Language manager service.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Kernel service.
   */
  protected DrupalKernelInterface $kernel;

  /**
   * State service.
   */
  protected StateInterface $state;

  /**
   * Redirect Test URL.
   */
  protected const REDIRECT_URL = '/test-redirect-url';

  /**
   * Anonymous redirect settings.
   */
  protected Config $anonymousRedirectSettings;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'custom_anonymous_redirect',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig('custom_anonymous_redirect');

    $this->pathMatcher = $this->createMock(PathMatcherInterface::class);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->languageManager->method('getCurrentLanguage')->willReturn($language);

    $config_factory = $this->container->get('config.factory');
    $this->anonymousRedirectSettings = $config_factory->getEditable('custom_anonymous_redirect.settings');

    $this->state = $this->container->get('state');
    $this->kernel = $this->createMock(DrupalKernelInterface::class);

    // Change configuration.
    $this->anonymousRedirectSettings
      ->set('redirect_url', self::REDIRECT_URL)
      ->set('enable_redirect', TRUE)
      ->save();

  }

  /**
   * Test anonymous redirect functionality with anonymous user.
   */
  public function testRedirectWithAnonymousUser(): void {
    $anonymous_user = $this->createUser([], NULL, FALSE, ['uid' => 0]);
    if ($anonymous_user === FALSE) {
      $this->fail('Unable to create anonymous user.');
    }
    $dispatcher = new EventDispatcher();
    $listener = new AnonymousRedirectSubscriber($anonymous_user, $this->pathMatcher, $this->languageManager);
    $dispatcher->addListener(KernelEvents::REQUEST, [
      $listener,
      'redirectAnonymous',
    ]);

    // Anonymous user is redirected.
    $request = Request::create('http://example.com/example');
    $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);
    $response = $event->getResponse();
    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals(self::REDIRECT_URL, $response->getTargetUrl());

    // Site is in maintenance mode.
    $this->state->set('system.maintenance_mode', TRUE);
    $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);
    $response = $event->getResponse();
    $this->assertNull($response);

    // Request URL is in the override redirect url list.
    $this->state->set('system.maintenance_mode', FALSE);
    $this->anonymousRedirectSettings->set('redirect_url_overrides', '/example2')
      ->save();
    $request = Request::create('http://example.com/example2');
    $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);
    $response = $event->getResponse();
    $this->assertNull($response);

    // Disabled anonymous redirect.
    $this->anonymousRedirectSettings->set('enable_redirect', FALSE)->save();
    $request = Request::create('http://example.com/example');
    $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);
    $response = $event->getResponse();
    $this->assertNull($response);

    // External redirect.
    $redirect_url = 'https://example.com/external-redirect';
    $this->anonymousRedirectSettings
      ->set('enable_redirect', TRUE)
      ->set('redirect_url', $redirect_url)
      ->save();
    $request = Request::create('http://example.com/example');
    $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);
    $response = $event->getResponse();
    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertEquals($redirect_url, $response->getTargetUrl());
  }

  /**
   * Test anonymous redirect functionality with anonymous user.
   */
  public function testRedirectWithAuthenticatedUser(): void {
    $authenticated_user = $this->createUser([], 'Test User', FALSE, ['uid' => 2]);
    if ($authenticated_user === FALSE) {
      $this->fail('Unable to create authenticated user.');
    }
    $dispatcher = new EventDispatcher();
    $listener = new AnonymousRedirectSubscriber($authenticated_user, $this->pathMatcher, $this->languageManager);
    $dispatcher->addListener(KernelEvents::REQUEST, [
      $listener,
      'redirectAnonymous',
    ]);
    $request = Request::create('http://example.com/example');
    $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    $dispatcher->dispatch($event, KernelEvents::REQUEST);
    $response = $event->getResponse();
    $this->assertNull($response);
  }

}
