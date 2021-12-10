<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\music_search\MusicSearchService;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to handle article autocomplete.
 */
abstract class BaseSaveAutocomplete extends FormBase {

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
  protected function radioWithOther(array &$form, string $id, array $field, array $other_textfield = NULL) {

    // Remove spaces from options field.
    /*
    $keys_to_clean = [];
    foreach ($field["#options"] as $key) {
    if (strstr($key, " ") !== FALSE) {
    $keys_to_clean[] = $key;
    }
    }
    foreach ($keys_to_clean as $key) {
    $value = $field["#options"][$key];
    unset($field["#options"][$key]);
    $new_key = implode("_", explode(" ", $key));
    $field["#options"][$new_key] = $value;
    }
     */

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
  protected function getRadioWithOther(FormStateInterface $form_state, string $id) {
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
  abstract public function submitForm(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  abstract public function getFormId();

}
