<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\music_search\MusicSearchService;
use Drupal\node\Entity\Node;
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
    $form[$id_string] = $field;

    // Other textfield.
    if ($other_textfield === NULL) {
      $other_textfield = ['#type' => 'textfield'];
    }
    $other_textfield["#attributes"] = ['id' => 'custom-' . $id];

    // Make other work with other options if checkboxes are used.
    $other_visible_condition = NULL;
    if ($field["#type"] == "checkboxes") {
      $other_visible_condition = [':input[id="edit-' . $id . '-select-other"]' => ["checked" => TRUE]];
    }
    else {
      $other_visible_condition = [':input[name="' . $id_string . '"]' => ["value" => "other"]];
    }
    $other_textfield["#states"] = [
      "visible" => $other_visible_condition,
    ];

    // Select other by default if radio buttons are used and other is the only one.
    if (count($field["#options"]) === 1 && $field["#type"] === "radios") {
      $form["#default_value"] = "other";
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
    $this->radioWithOther($form, "images", [
      '#type' => "radios",
      '#title' => "Images",
      '#options' => array_combine($images, $images),
      "#required" => TRUE,
    ]);

    // Birth date.
    $images = $this->getAll(function ($item) {
      return $item->getBirthDate()->format("Y - m - d");
    }, $all_autofill_data);
    $this->radioWithOther($form, "birth_date", [
      '#type' => "radios",
      '#title' => "Birth Date",
      '#options' => array_combine($images, $images),
      "#required" => TRUE,
    ], ["#type" => "date"]);

    // Death date.
    $images = $this->getAll(function ($item) {
      return $item->getDeathDate();
    }, $all_autofill_data);
    $this->radioWithOther($form, "death_date", [
      '#type' => "radios",
      '#title' => "Death Date",
      '#options' => array_combine($images, $images),
      "#required" => TRUE,
    ]);

    // Website link.
    $images = $this->getAll(function ($item) {
      return $item->getWebsiteLink();
    }, $all_autofill_data);
    $this->radioWithOther($form, "website_link", [
      '#type' => "radios",
      '#title' => "Website Link",
      '#options' => array_combine($images, $images),
      "#required" => TRUE,
    ], ["#type" => "link"]);

    // Genres.
    $genres = $this->getAll(function ($item) {
      return $item->getGenres();
    }, $all_autofill_data);
    $flat_genres = array_merge(...$genres);
    $this->radioWithOther($form, "genres", [
      '#type' => "checkboxes",
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

    // Create the media.
    $image_path = $images;
    $image_name = end(explode("/", $image_path));
    $has_extension = str_contains($image_name, ".");
    $with_extension = $has_extension ? $image_name : $image_name . ".jpeg";
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . basename($with_extension);

    $file_data = file_get_contents($images);
    $file = file_save_data($file_data, $destination);
    $media = Media::create([
      'bundle' => 'image',
      'uid' => \Drupal::currentUser()->id(),
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->setName("Temp")
      ->setPublished(TRUE)
      ->save();

    // Create the content.
    $node = Node::create([
      "type" => "artist",
      "title" => $name,
      "status" => Node::PUBLISHED,
      "field_description" => $description,
      "field_band_members" => [],
      "field_birth_date" => (new \DateTime())->format("Y-m-d"),
      "field_death_date" => (new \DateTime())->format("Y-m-d"),
      "field_images_media" => [$media],
      "field_website" => $website_link,
      "field_mus" => [],
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
