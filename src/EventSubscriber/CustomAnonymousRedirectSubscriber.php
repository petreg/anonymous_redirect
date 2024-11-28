<?php

namespace Drupal\custom_anonymous_redirect\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to kernel events and does the actual redirect.
 *
 * @package Drupal\custom_anonymous_redirect\EventSubscriber
 */
class CustomAnonymousRedirectSubscriber extends ControllerBase implements EventSubscriberInterface {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Custom AnonymousRedirect constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The patch matcher service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(AccountInterface $account, PathMatcherInterface $pathMatcher, LanguageManagerInterface $languageManager, ModuleHandlerInterface $module_handler,) {
    $this->account = $account;
    $this->pathMatcher = $pathMatcher;
    $this->languageManager = $languageManager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['redirectAnonymous', 100];
    return $events;
  }

  /**
   * Redirects anonymous users to the /user route.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The response event.
   */
  public function redirectAnonymous(RequestEvent $event): void {
    // Initialize the minimum amount of values since this runs on every request.
    $config = $this->config('custom_anonymous_redirect.settings');
    $redirectEnabled = $config->get('enable_redirect');

    // Fail as early as possible.
    if (!$redirectEnabled
      || $this->account->isAuthenticated()
      || $this->state()->get('system.maintenance_mode')
    ) {
      return;
    }

    // Now we know we need to redirect -- build all other needed variables.
    $redirectUrl = $config->get('redirect_url');
    $redirectUrlOverridesText = $config->get('redirect_url_overrides');

    $this->moduleHandler->alter('custom_anonymous_redirect_paths', $redirectUrlOverridesText);

    $redirectUrlOverrides = $redirectUrlOverridesText ? explode("\r\n", $redirectUrlOverridesText) : [];

    $currentPath = $event->getRequest()->getPathInfo();

    // Handle language prefix if present.
    $currentLanguagePrefix = $this->languageManager()
      ->getCurrentLanguage()
      ->getId();

    // Check if the language prefix is present after the leading slash.
    if (substr($currentPath, 1, strlen($currentLanguagePrefix)) == $currentLanguagePrefix) {
      $currentPath = substr($currentPath, strlen($currentLanguagePrefix) + 1);
    }

    // Do nothing if the url is in the list of overrides.
    if (
      in_array($currentPath, $redirectUrlOverrides)
      || $this->pathMatcher->matchPath($currentPath, $redirectUrlOverridesText)) {
      return;
    }

    // External URL must use TrustedRedirectResponse class.
    if (UrlHelper::isExternal($redirectUrl)) {
      $event->setResponse(new TrustedRedirectResponse($redirectUrl));
      return;
    }

    // Redirect the user to the front page.
    if ($this->isFrontPage($redirectUrl)
      && $currentPath !== Url::fromRoute("<front>")->toString()) {
      $event->setResponse(new RedirectResponse(Url::fromRoute("<front>")
        ->toString()));
    }

    // Redirect the user the configured route.
    if ($this->isFrontPage($redirectUrl) == FALSE && strpos($currentPath, $redirectUrl) === FALSE) {
      // If redirecting to the login page, redirect the visitor back to the
      // requested path after a successful login.
      if ($redirectUrl === Url::fromRoute('user.login')->toString()
        && $currentPath !== '/') {
        $options = [
          'query' => [
            'destination' => $currentPath,
          ],
        ];
        $redirectUrl = Url::fromUserInput($redirectUrl, $options)->toString();
      }

      $event->setResponse(
        new RedirectResponse(
          Url::fromUri('internal:' . $redirectUrl)->toString()
        )
      );
    }
  }

  /**
   * Returns true if the entered string matches the configured front page route.
   *
   * @param string $urlString
   *   The URL to test.
   *
   * @return bool
   *   Whether the entered string matches.
   */
  public function isFrontPage($urlString): bool {

    if ($urlString == "<front>") {
      return TRUE;
    }

    return FALSE;
  }

}
