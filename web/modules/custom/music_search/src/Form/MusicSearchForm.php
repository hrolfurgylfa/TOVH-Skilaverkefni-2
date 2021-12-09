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
    $type = \Drupal::routeMatch()->getParameter('autocomplete_type');

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['goToArtistSearch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Artist search'),
      '#submit' => [[$this, 'goToArtistSearch']],
    ];
    $form['actions']['goToTrackSearch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Track search'),
      '#submit' => [[$this, 'goToTrackSearch']],
    ];
    $form['actions']['goToAlbumSearch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Album search'),
      '#submit' => [[$this, 'goToAlbumSearch']],
    ];
    $form['article'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search for ' . $type),
      '#autocomplete_route_name' => 'music_search.search_form.autocomplete',
      '#autocomplete_route_parameters' => ['search_type' => $type],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next Step'),
    ];

    return $form;
  }

  /**
   *
   */
  public function goToArtistSearch(array $form, FormStateInterface $form_state) {
    $url = "https://tonlistavefur-islands.ddev.site/music_search/search/artist";
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   *
   */
  public function goToTrackSearch(array $form, FormStateInterface $form_state) {
    $url = "https://tonlistavefur-islands.ddev.site/music_search/search/track";
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   *
   */
  public function goToAlbumSearch(array $form, FormStateInterface $form_state) {
    $url = "https://tonlistavefur-islands.ddev.site/music_search/search/album";
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue("article");
    //$type = "artist";
    $type = \Drupal::routeMatch()->getParameter('autocomplete_type');
    $ids = $this->musicSearchService->getIdsByName($name, $type);

    $response = new RedirectResponse(Url::fromRoute("music_search.create." . $type)->toString() . "?" . http_build_query($ids));
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_search_form";
  }

}
