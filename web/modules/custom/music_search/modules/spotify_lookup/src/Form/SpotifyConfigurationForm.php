<?php

namespace Drupal\spotify_lookup\Form;

/**
 * @file
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class SpotifyConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['spotify_lookup.credentials'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_lookup_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('spotify_lookup.credentials');
    $form['spotify_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#description' => $this->t('Please provide the Spotify Client ID you want to use.'),
      '#default_value' => $config->get('spotify_client_id'),
    ];
    $form['spotify_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client Secret'),
      '#description' => $this->t('Please provide the Spotify Client Secret you want to use.'),
      '#default_value' => $config->get('spotify_client_secret'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('spotify_lookup.credentials')
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->set('spotify_client_secret', $form_state->getValue('spotify_client_secret'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
