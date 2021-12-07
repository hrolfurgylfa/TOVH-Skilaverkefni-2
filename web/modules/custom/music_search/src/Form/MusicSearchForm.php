<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form to handle article autocomplete.
 */
class MusicSearchForm extends FormBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The injected MusicSearchService.
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $musicSearchService;

  /**
   * {@inheritdoc}
   */
  public function __construct(MusicSearchService $musicSearchService) {
    $this->musicSearchService = $musicSearchService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['article'] = [
      '#type' => 'textfield',
      '#title' => $this->t('My Autocomplete'),
      '#autocomplete_route_name' => 'music_search.search_form.autocomplete',
      '#autocomplete_route_parameters' => array('search_type' => 'artist'),
    ];

    $form['search_type']['active'] = array(
      '#type' => 'radios',
      '#title' => $this
        ->t('Search results'),
      '#default_value' => 0,
      '#options' => array(
        0 => $this
          ->t('Track'),
        1 => $this
          ->t('Artist'),
        2 => $this
          ->t('Album'),
      ),
    );

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue("article");
    $type = "artist";
    $ids = $this->musicSearchService->getIdsByName($name, $type);

    $response = new RedirectResponse(Url::fromRoute("<front>")->toString() . "?spotify=" . urlencode($ids["spotify"]));
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_search_form";
  }

}
