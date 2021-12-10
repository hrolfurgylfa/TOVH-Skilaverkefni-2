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
class ChooseFromOptions extends FormBase {

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
    $searched_name = \Drupal::request()->query->get("name");
    $type = \Drupal::routeMatch()->getParameter('type');
    $suggestions = $this->musicSearchService->search_name_img($searched_name, 'artist');

    $discogs = $suggestions["discogsnames"];
    $spotify = $suggestions["spotifynames"];

    $discogsnames = [];
    $spotifynames = [];

    foreach ($discogs as $artist) {
      array_push($discogsnames, $artist["name"]);
    }

    foreach ($spotify as $artist) {
      array_push($spotifynames, $artist["name"]);
    }


    $spotifynamesandimg = array_map(function ($item) {
      return '<img src="'. $item["img"] . '" width="100" height="auto">  ' . $item["name"];
    }, $spotify);

    $discogsnamesandimg = array_map(function ($item) {
      return '<img src="'. $item["img"] . '" width="100" height="auto">  ' . $item["name"];
    }, $discogs);


    $form['spotify_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pick From Spotify'),
      '#options' => array_combine($spotifynames, $spotifynamesandimg),
      '#required' => TRUE,
    ];

    $form['discogs_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pick From Discogs'),
      '#options' => array_combine($discogsnames, $discogsnamesandimg),
      '#required' => TRUE,
    ];

    $form['actions']['backToSearch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go Back To Search'),
      '#submit' => [[$this, 'goBackToSearch']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Content'),
    ];

    return $form;
  }



  /**
   *
   */
  public function goBackToSearch(array $form, FormStateInterface $form_state) {
    $url = "https://tonlistavefur-islands.ddev.site/music_search/search/";
    $response = new RedirectResponse($url);
    $response->send();
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $spotifyname = $form_state->getValue("spotify_select");
    $discogsname = $form_state->getValue("discogs_select");
    $type = "artist";
    $spoifyid = $this->musicSearchService->getIdsByName($spotifyname, $type)["spotify"];
    $discogsid = $this->musicSearchService->getIdsByName($discogsname, $type)["discogs"];

    $ids = ["spotify"=>$spoifyid, "discogs"=>$discogsid];

    $response = new RedirectResponse(Url::fromRoute("music_search.create." . $type)->toString() . "?" . http_build_query($ids));
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_choose_form";
  }

}
