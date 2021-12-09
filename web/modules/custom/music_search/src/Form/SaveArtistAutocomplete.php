<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\music_search\MusicSearchService;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
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
   * Help create radio or checkbox buttons that have a other textfield for other information.
   */
  private function radioWithOther(array &$form, string $id, array $field, array $other_textfield = NULL) {

    // Setup the field.
    $id_string = $id . "_select";
    $field["#attributes"] = ['name' => $id_string];
    $field["#options"]["other"] = t("Other");
    if ($field["#options"]["anime rock"] !== NULL) {
      unset($field["#options"]["anime rock"]);
      $field["#options"]["anime-rock"] = "anime rock";
    }
    $form[$id_string] = $field;

    // Other textfield.
    if ($other_textfield === NULL) {
      $other_textfield = ['#type' => 'textfield'];
    }
    $other_textfield["#attributes"] = ['id' => 'custom-' . $id];

    // Make other work with other options if checkboxes are used.
    $other_visible_condition = NULL;
    // If ($field["#type"] == "checkboxes") {
    //   $other_visible_condition = [':input[id="edit-' . $id . '-select-other"]' => ["checked" => TRUE]];
    // }
    // else {.
    $other_visible_condition = [':input[name="' . $id_string . '"]' => ["value" => "other"]];
    // }
    $other_textfield["#states"] = [
      "visible" => $other_visible_condition,
    ];

    // Select other by default if radio buttons are used and other is the only one.
    if (count($field["#options"]) === 1 && $field["#type"] === "radios") {
      $form[$id_string]["#default_value"] = "other";
    }

    $form["custom_" . $id] = $other_textfield;
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
   * Get the name of an image from it's URL, even if it doesn't have a file extension.
   */
  protected function getImageNameWithExtension(string $image_url): string {
    $image_name = end(explode("/", $image_url));
    $has_extension = str_contains($image_name, ".");
    return $has_extension ? $image_name : $image_name . ".jpeg";
  }

  /**
   * Save an image from a URL to the media library.
   */
  protected function saveImage(string $image_url, ?string $filename) {
    // Get the filename if there is none provided.
    if ($filename === NULL) {
      $filename = end(explode("/", $image_url));
    }

    // Place to be stored.
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . basename($filename);

    // Make image from link.
    $file_data = file_get_contents($image_url);
    $file = file_save_data($file_data, $destination);
    $media = Media::create([
      'bundle' => 'image',
      'uid' => \Drupal::currentUser()->id(),
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ]);

    // Get the name without extension.
    $filename_no_ext = explode(".", $filename)[0];

    // Set to published.
    $media->setName($filename_no_ext)
      ->setPublished(TRUE)
      ->save();

    return $media;
  }

  /**
   *
   */
  protected function getOrCreateVocabularyTerms(array $term_names, string $vocabulary): array {
    // Get the existing terms.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vocabulary);
    $term_ids = $query->execute();
    $terms = Term::loadMultiple($term_ids);

    // Turn the existing terms into a associative array for better performance.
    $dict_terms = [];
    foreach ($terms as $term) {
      $dict_terms[strtolower($term->getName())] = $term;
    }

    // Get or create the terms.
    $return_terms = [];
    foreach ($term_names as $term_name) {
      $term = $dict_terms[strtolower($term_name)];

      // Create them if needed.
      if ($term === NULL) {
        $term = Term::create([
          "vid" => $vocabulary,
          "name" => $term_name,
        ]);
      }

      // Save the term to the return array.
      $return_terms[] = $term;
    }

    return $return_terms;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
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
    if ($discogs_id) {
      array_push($all_autofill_data, $this->musicSearchService->getDiscogsArtist($discogs_id));
    }

    // Artist name.
    $names = $this->getAll(function ($item) {
      return $item->getName();
    }, $all_autofill_data);
    $this->radioWithOther($form, "name", [
      '#type' => "radios",
      '#title' => "Name",
      '#options' => array_combine($names, $names),
      "#required" => TRUE,
    ]);

    // Artist description.
    $descriptions = $this->getAll(function ($item) {
      return $item->getDescription();
    }, $all_autofill_data);
    $this->radioWithOther($form, "description", [
      '#type' => "radios",
      '#title' => "Description",
      '#options' => array_combine($descriptions, $descriptions),
      "#required" => TRUE,
    ]);

    // Artist image.
    $images = $this->getAll(function ($item) {
      return $item->getImageURL();
    }, $all_autofill_data);
    $imagestuff = array_map(function ($item) {
      return '<img src="' . $item . '" width="100" height="auto">';
    }, $images);
    $this->radioWithOther($form, "images", [
      '#type' => "radios",
      '#title' => "Images",
      '#options' => array_combine($images, $imagestuff),
      "#required" => TRUE,
    ]);

    // Birth date.
    $birth_date = $this->getAll(function ($item) {
      return $item->getBirthDate();
    }, $all_autofill_data);
    $birth_date_str = array_map(function ($d) {
      return $d->format("Y-m-d");
    }, $birth_date);
    $this->radioWithOther($form, "birth_date", [
      '#type' => "radios",
      '#title' => "Birth Date",
      '#options' => array_combine($birth_date_str, $birth_date_str),
      "#required" => TRUE,
    ], ["#type" => "date"]);

    // Death date.
    $death_date = $this->getAll(function ($item) {
      return $item->getDeathDate();
    }, $all_autofill_data);
    $death_date_str = array_map(function ($d) {
      return $d->format("Y-m-d");
    }, $death_date);
    $this->radioWithOther($form, "death_date", [
      '#type' => "radios",
      '#title' => "Death Date",
      '#options' => array_combine($death_date_str, $death_date_str),
    ], ["#type" => "date"]);

    // Website link.
    $website_link = $this->getAll(function ($item) {
      return $item->getWebsiteLink();
    }, $all_autofill_data);
    $this->radioWithOther($form, "website_link", [
      '#type' => "radios",
      '#title' => "Website Link",
      '#options' => array_combine($website_link, $website_link),
      "#required" => TRUE,
    ]);

    // Genres.
    $genres = $this->getAll(function ($item) {
      return $item->getGenres();
    }, $all_autofill_data);
    $flat_genres = array_merge(...$genres);
    $this->radioWithOther($form, "genres", [
      '#type' => "radios",
      '#title' => "Genres",
      '#options' => array_combine($flat_genres, $flat_genres),
    ]);

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
    // Get the relevant parameters.
    $name = $this->getRadioWithOther($form_state, "name");
    $description = $this->getRadioWithOther($form_state, "description");
    $images = $this->getRadioWithOther($form_state, "images");
    $birth_date = $this->getRadioWithOther($form_state, "birth_date");
    $death_date = $this->getRadioWithOther($form_state, "death_date");
    $website_link = $this->getRadioWithOther($form_state, "website_link");
    $genres = $this->getRadioWithOther($form_state, "genres");
    $spotify_id = \Drupal::request()->query->get("spotify");
    $discogs_id = \Drupal::request()->query->get("discogs");

    // Make sure there aren't any bad types that will crash the
    // generated entity.
    if (filter_var($website_link, FILTER_VALIDATE_URL) === FALSE) {
      $res = new RedirectResponse(Url::fromRoute("music_search.search_form")->toString());
      $res->send();
      return;
    }

    // Create the media.
    $image_path = $images;
    $image_name = $this->getImageNameWithExtension($image_path);
    $media = $this->saveImage($image_path, $image_name);

    // Create the selected genres terms.
    $genres = [$genres];
    $genre_terms = $this->getOrCreateVocabularyTerms($genres, "music_genre");

    // Create the content.
    $node = Node::create([
      "type" => "artist",
      "title" => $name,
      "status" => Node::PUBLISHED,
      "field_description" => $description,
      "field_band_members" => [],
      "field_birth_date" => $birth_date,
      "field_death_date" => $death_date,
      "field_images_media" => [$media],
      "field_website" => $website_link,
      "field_mus" => $genre_terms,
      "field_discogs_id" => $discogs_id,
      "field_spotify_id" => $spotify_id,
      
    ]);
    $node->save();
    $c = "c";
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_create_artist_from_search";
  }

}
