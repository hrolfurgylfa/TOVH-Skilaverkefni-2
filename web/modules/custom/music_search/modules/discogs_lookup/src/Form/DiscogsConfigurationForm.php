<?php

namespace Drupal\discogs_lookup\Form;

/**
 * @file
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class DiscogsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['discogs_lookup.credentials'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'discogs_lookup_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('discogs_lookup.credentials');
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Token'),
      '#description' => $this->t('Please provide the Discogs Token you want to use.'),
      '#default_value' => $config->get('token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('discogs_lookup.credentials')
      ->set('token', $form_state->getValue('token'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
