<?php

namespace Drupal\custom_anonymous_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Anonymous Redirect.
 *
 * @package Drupal\custom_anonymous_redirect\Form
 */
class CustomAnonymousRedirectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_anonymous_redirect_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'custom_anonymous_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('custom_anonymous_redirect.settings');

    $form['enable_custom_anonymous_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Anonymous Redirect'),
      '#description' => $this->t('turn on/off anonymous redirect'),
      '#default_value' => $config->get('enable_redirect'),
    ];
    $form['redirect_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect Base URL'),
      '#description' => $this->t("For internal URL's use <front> or '/path'. For external URL's user http:// and No trailing slash. For example, http://example.com or http://example.com/drupal."),
      '#maxlength' => 500,
      '#size' => 64,
      "#default_value" => $config->get('redirect_url'),
    ];

    $form['redirect_url_overrides'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Redirect URL Overrides'),
      '#description' => $this->t("A list of internal paths to ignore the redirect for. One path per line. (eg. '/path')"),
      '#rows' => 4,
      '#default_value' => $config->get('redirect_url_overrides'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->config('custom_anonymous_redirect.settings')
      ->set('enable_redirect', $form_state->getValue('enable_custom_anonymous_redirect'))
      ->set('redirect_url', $form_state->getValue('redirect_base_url'))
      ->set('redirect_url_overrides', $form_state->getValue('redirect_url_overrides'))
      ->save();

    // Forces a cache rebuild so that changes take effect at form save.
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
