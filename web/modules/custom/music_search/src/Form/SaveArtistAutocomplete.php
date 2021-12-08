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
class SaveArtistAutocomplete extends FormBase {

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
   *
   */
  private function radioWithOther(array &$form, string $id, string $title, array $options, int $maxsize = 255, string $placeholder = "") {
    // Add the other option.
    $options["other"] = t("Other");

    // Radio buttons.
    $id_string = $id . "_select";
    $form[$id_string] = [
      '#type' => 'radios',
      '#title' => $title,
      '#options' => $options,
      '#attributes' => [
        'name' => $id_string,
      ],
    ];

    // Other textfield.
    $other_textfield = [
      '#type' => 'textfield',
      '#attributes' => [
        'id' => 'custom-' . $id,
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $id_string . '"]' => ['value' => 'other'],
        ],
      ],
    ];

    // Optional function arguments.
    if ($other_textfield) {
      $other_textfield["#placeholder"] = $placeholder;
    }
    if ($maxsize) {
      $other_textfield["#size"] = (string) $maxsize;
    }

    $form['custom_' . $id] = $other_textfield;
  }

  /**
   *
   */
  private function getRadioWithOther(FormStateInterface $form_state, string $id): string {
    $radio_value = $form_state->getValue($id . "_select");
    if ($radio_value !== "other") {
      return $radio_value;
    }
    else {
      return $form_state->getValue("custom_" . $id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,) : array {
    $spotify_id = \Drupal::request()->query->get("spotify");
    $discogs_id = \Drupal::request()->query->get("discogs");

    if (!$spotify_id && !$discogs_id) {
      $res = new RedirectResponse(Url::fromRoute("music_search.search_form")->toString());
      $res->send();
    }

    $all_autofill_data = [];

    if ($spotify_id) {
      array_push($all_autofill_data, $this->musicSearchService->getSpotifyArtist($spotify_id));
    }

    // Name.
    $this->radioWithOther($form, "name", "Name", ["KIRA" => "KIRA", "LIRA" => "LIRA"]);

    $form['actions'] = [
      '#type' => 'actions',
    ];

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
    $radio_value = $form_state->getValue("test_field");
    $name = $this->getRadioWithOther($form_state, "name");
    $a = "a";
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_create_artist_from_search";
  }

}
