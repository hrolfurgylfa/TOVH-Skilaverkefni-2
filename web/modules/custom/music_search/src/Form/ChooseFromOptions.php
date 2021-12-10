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
    $url = \Drupal::routeMatch()->getRouteName();
    $urlarray = explode('.', $url);
    $type = end($urlarray);
    $suggestions = $this->musicSearchService->search_name_img($searched_name, $type);

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

    if ($type === 'track') {
      $spotifyninfo = array_map(function ($item) {
        return $item["name"] . ' - ' . $item["artist"];
      }, $spotify);
    } else {
      $spotifyninfo = array_map(function ($item) {
        return '<img src="' . $item["img"] . '" width="100" height="auto">  ' . $item["name"];
      }, $spotify);

      $discogsinfo = array_map(function ($item) {
        return '<img src="' . $item["img"] . '" width="100" height="auto">  ' . $item["name"];
      }, $discogs);
    }


    $form['spotify_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pick From Spotify'),
      '#options' => array_combine($spotifynames, $spotifyninfo),
    ];

    if ($type !== 'track') {
      $form['discogs_select'] = [
        '#type' => 'radios',
        '#title' => $this->t('Pick From Discogs'),
        '#options' => array_combine($discogsnames, $discogsinfo),
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Content'),
    ];

    $form['actions']['backToSearch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go Back To Search'),
      '#submit' => [[$this, 'goBackToSearch']],
      '#limit_validation_errors' => array(),
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
    $url = \Drupal::routeMatch()->getRouteName();
    $urlarray = explode('.', $url);
    $type = end($urlarray);

    if ($type === 'track') {
      $spoifyid = $this->musicSearchService->getIdsByName($spotifyname, $type)["spotify"];
      $ids = ["spotify"=>$spoifyid];
    } elseif ($spotifyname === null && $discogsname !== null) {
      $discogsid = $this->musicSearchService->getIdsByName($discogsname, $type)["discogs"];
      $ids = ["discogs"=>$discogsid];
    } elseif ($spotifyname !== null && $discogsname === null) {
      $spoifyid = $this->musicSearchService->getIdsByName($spotifyname, $type)["spotify"];
      $ids = ["spotify"=>$spoifyid];
    } else {
      $discogsid = $this->musicSearchService->getIdsByName($discogsname, $type)["discogs"];
      $spoifyid = $this->musicSearchService->getIdsByName($spotifyname, $type)["spotify"];
      $ids = ["spotify"=>$spoifyid, "discogs"=>$discogsid];
    }

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
