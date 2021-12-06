<?php

namespace Drupal\spotify_lookup\Form;

/**
 * @file
 */

use Drupal\Core\Form\ConfigFormBase;

/**
 *
 */
class SpotifyConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['spotify_lookup.spotify_client_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'salutation_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('spotify_lookup.spotify_client_id');
    $form['salutation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#description' => $this->t('Please provide the Spotify Client ID you want to use.'),
      '#default_value' => $config->get('spotify_client_id'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('spotify_lookup.spotify_client_id')
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
