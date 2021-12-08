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
  private function radioWithOther(array &$form, string $id, string $title, array $options, int $maxsize = 255, string $placeholder = "", $type = "radios") {
    // Add the other option.
    $options["other"] = t("Other");

    // Radio buttons.
    $id_string = $id . "_select";
    $form[$id_string] = [
      '#type' => $type,
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
   * Get a specific parameter from all objects in a array.
   */
  protected function getAll(callable $get_data_func, array $data) {
    $results = [];

    foreach ($data as $item) {
      $result = $get_data_func($item);

      if ($result !== "" && $result !== NULL && (!is_array($result) || count($result) !== 0)) {
        array_push($results, $result);
      }
    }

    return $results;
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

    // Artist name.
    $names = $this->getAll(function ($item) {
      return $item->getName();
    }, $all_autofill_data);
    $this->radioWithOther($form, "name", "Name", array_combine($names, $names));

    // Artist description.
    $descriptions = $this->getAll(function ($item) {
      return $item->getDescription();
    }, $all_autofill_data);
    $this->radioWithOther($form, "description", "Description", array_combine($descriptions, $descriptions));

    // Artist image.
    $images = $this->getAll(function ($item) {
      return $item->getImageURL();
    }, $all_autofill_data);
    $this->radioWithOther($form, "images", "Images", array_combine($images, $images));

    // Birth date.
    $images = $this->getAll(function ($item) {
      return $item->getBirthDate();
    }, $all_autofill_data);
    $this->radioWithOther($form, "birth_date", "Birth Date", array_combine($images, $images));

    // Death date.
    $images = $this->getAll(function ($item) {
      return $item->getDeathDate();
    }, $all_autofill_data);
    $this->radioWithOther($form, "death_date", "Death Date", array_combine($images, $images));

    // Website link.
    $images = $this->getAll(function ($item) {
      return $item->getWebsiteLink();
    }, $all_autofill_data);
    $this->radioWithOther($form, "website_link", "Website Link", array_combine($images, $images));

    // Genres.
    $genres = $this->getAll(function ($item) {
      return $item->getGenres();
    }, $all_autofill_data);
    $genres_strings = array_map(function ($item) {
      return implode(", ", $item);
    }, $genres);
    $this->radioWithOther($form, "genres", "Genres", array_combine($genres_strings, $genres_strings));

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
    $name = $this->getRadioWithOther($form_state, "name");
    $description = $this->getRadioWithOther($form_state, "description");
    $images = $this->getRadioWithOther($form_state, "images");
    $birth_date = $this->getRadioWithOther($form_state, "birth_date");
    $death_date = $this->getRadioWithOther($form_state, "death_date");
    $website_link = $this->getRadioWithOther($form_state, "website_link");
    $genres = $this->getRadioWithOther($form_state, "genres");
    $a = "a";
    // @todo Save artist.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_create_artist_from_search";
  }

}
