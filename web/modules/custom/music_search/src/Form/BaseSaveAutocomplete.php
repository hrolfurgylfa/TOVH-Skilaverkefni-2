<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\music_search\NodeAutocreationService;

/**
 * Form to handle article autocomplete.
 */
abstract class BaseSaveAutocomplete extends FormBase {

  /**
   * The injected MusicSearchService.
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $musicSearchService;

  /**
   * The injected NodeAutocreationService.
   *
   * @var \Drupal\music_search\NodeAutocreationService
   */
  protected $nodeAutocreation;

  /**
   * The injected UUID service from Drupal core.
   *
   * @var Drupal\Component\Uuid\Uuid
   */
  protected $uuidService;

  /**
   * {@inheritdoc}
   */
  public function __construct(MusicSearchService $musicSearchService, NodeAutocreationService $nodeAutocreationService) {
    $this->musicSearchService = $musicSearchService;
    $this->nodeAutocreation = $nodeAutocreationService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search'),
      $container->get('node_autocreation')
    );
  }

  /**
   * Help create radio or checkbox buttons that have a other textfield for other information.
   */
  protected function radioWithOther(array &$form, string $id, array $field, array $other_textfield = NULL) {

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
    $other_textfield["#states"] = [
      "visible" => [':input[name="' . $id_string . '"]' => ["value" => "other"]],
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
  private function insertAfterKey(array &$original, mixed $key, mixed $new_key, mixed $new_value) {
    $index = 0;
    foreach (array_keys($original) as $og_key) {
      if ($og_key === $key) {
        break;
      }
      $index++;
    }
    $end = array_splice($original, $index, count($original) - $index);
    $original[$new_key] = $new_value;
    foreach ($end as $key => $val) {
      $original[$key] = $val;
    }
  }

  /**
   * Go through the passed in form and fix the checkbox fields.
   *
   * This function converts all "checkboxes" fields into multiple individual
   * "checkbox" fields as the "checkboxes" fields were crashing before
   * submitForm could be called.
   */
  protected function fixCheckboxes(array &$form) {
    // Find all the checkboxes in the form.
    foreach ($form as $form_key => &$form_value) {
      if ($form_value["#type"] === "checkboxes") {
        $options = &$form[$form_key]["#options"];

        // Find the other textfield if there is one os that this doesn't break
        // checkboxes with other fields.
        $other_textfield_key = NULL;
        $all_form_keys = array_keys($form);
        $estimated_radioWithOther_id = explode("_", $form_key)[0];
        for ($j = 0; $j < count($form); $j++) {
          if ($all_form_keys[$j] === "custom_" . $estimated_radioWithOther_id) {
            $other_textfield_key = $all_form_keys[$j];
          }
        }

        // Loop through each option to make all the options as their own fields.
        for ($i = 0; $i < count($options); $i++) {
          // Make the new field.
          $option_key = array_keys($options)[$i];
          $new_form_key = $form_key . "_checkbox_" . $i;
          $this->insertAfterKey($form, $form_key, $new_form_key, $form[$form_key]);
          // Make sure the value returned stays the same.
          $form[$new_form_key]["#value_callback"] = function ($element, $input, $form_state) use ($option_key) {
            return ($input === FALSE) ? NULL : $option_key;
          };

          // Set the checkbox fields values.
          $form[$new_form_key]["#type"] = "checkbox";
          $form[$new_form_key]["#required"] = FALSE;
          unset($form[$new_form_key]["#options"]);
          $form[$new_form_key]["#title"] = $options[$option_key];

          // Set the toggle button to target this option if it is "other" and
          // we're dealing with something from the radioWithOther function.
          if ($other_textfield_key !== NULL && $option_key === "other") {
            $form[$other_textfield_key]["#states"]["visible"] = [
              ':input[id="edit-' . str_replace("_", "-", $new_form_key) . '"]' => ["checked" => TRUE],
            ];
          }
        }

        unset($form[$form_key]);
      }
    }
  }

  /**
   * Get values from the form_state.
   *
   * This class has a custom method for this as it can get values from functions
   * like fixCheckboxes at this same time. This means that all subclasses and
   * methods in this class should call this method instead of calling
   * $form_state->getValue directly.
   */
  protected function getFormStateValue(FormStateInterface $form_state, string $id, $default = NULL) {
    $val = $form_state->getValue($id);

    // Get values stored by fixCheckboxes.
    if ($val === NULL && $form_state->getValue($id . "_checkbox_0") !== NULL) {
      $values = [];

      // Loop through all the values stored by fixCheckboxes until we encounter
      // a NULL.
      $i = 0;
      $next = $form_state->getValue($id . "_checkbox_" . $i);
      while ($next !== NULL) {
        $values[] = $next;

        $next = $form_state->getValue($id . "_checkbox_" . $i);
        $i++;
      }

      // Return the array of values or the default value if the array is empty.
      return count($values) === 0 ? $default : $values;
    }

    // We can give the returned or default value.
    else {
      return $val === NULL ? $default : $val;
    }
  }

  /**
   * Take a number from hexadecimal to base 64.
   */
  protected function hexToBase64(string $num): string {
    $return = '';
    foreach (str_split($num, 2) as $pair) {
      $return .= chr(hexdec($pair));
    }
    return base64_encode($return);
  }

  /**
   * Make a safe key that can later be changed back for the real key.
   *
   * This is done by returning a fairly short key that has no special characters
   * except "_" and "-" and can later be exchanged back into the real key by
   * calling the method getSafeKey.
   */
  protected function makeKeySafe(string $key): string {

    // Reproducibly make the safe key.
    $hashed_key = md5($key);
    $hashed_key_compact = $this->hexToBase64($hashed_key);
    $new_key = "safe_key_id_" . substr($hashed_key_compact, 0, 8);

    // Save the old key in the Drupal State API.
    $prefix = "music_service.makeKeySafe.";
    \Drupal::state()->set($prefix . $new_key, $key);

    // Return the new key to be used until it is safe to get the full value back.
    return $new_key;
  }

  /**
   * Make multiple keys safe with the makeKeySafe method.
   */
  protected function makeKeysSafe(array $keys): array {
    return array_map(function ($item) {
      return $this->makeKeySafe($item);
    }, $keys);
  }

  /**
   * Get back the real key from the safe key that was generated by makeKeySafe.
   */
  protected function getSafeKey(string $safe_key): string {
    $save_prefix = "music_service.makeKeySafe.";
    if (str_starts_with($safe_key, "safe_key_id_")) {
      return \Drupal::state()->get($save_prefix . $safe_key, $safe_key);
    }
    else {
      return $safe_key;
    }
  }

  /**
   *
   */
  protected function unShortenKey($key) {
    if (is_string($key) && str_starts_with($key, "form_option_shortened_")) {
      return \Drupal::state()->get("music_service." . $key);
    }
    else {
      return $key;
    }
  }

  /**
   *
   */
  protected function getRadioWithOther(FormStateInterface $form_state, string $id) {
    $radio_value = $this->getFormStateValue($form_state, $id . "_select");
    if ($radio_value !== "other") {
      return $radio_value;
    }
    else {
      return $this->getFormStateValue($form_state, "custom_" . $id);
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
   * Add the fields to insert the data about the thing being inserted.
   */
  abstract protected function addFields(array &$form, FormStateInterface $form_state, array $autofill_data);

  /**
   * Get the data from spotify.
   */
  abstract protected function getSpotifyData($spotify_id);

  /**
   * Get the data from discogs.
   */
  abstract protected function getDiscogsData($discogs_id);

  /**
   * Get all the autofill data for the form.
   */
  protected function getAutofillData() {
    $spotify_id = \Drupal::request()->query->get("spotify");
    $discogs_id = \Drupal::request()->query->get("discogs");
    if (!$spotify_id && !$discogs_id) {
      $res = new RedirectResponse(Url::fromRoute("music_search.search_form")->toString());
      $res->send();
    }

    $all_autofill_data = [];
    if ($spotify_id) {
      array_push($all_autofill_data, $this->getSpotifyData($spotify_id));
    }
    if ($discogs_id) {
      array_push($all_autofill_data, $this->getDiscogsData($discogs_id));
    }

    return $all_autofill_data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {

    $autofill_data = $this->getAutofillData();

    $this->addFields($form, $form_state, $autofill_data);

    // Add save button.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['actions']['backbutton'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go Back'),
      '#limit_validation_errors' => [],
      '#submit' => [[$this, 'goBack']],
    ];

    // Make sure all "checkboxes" fields get fixed.
    $this->fixCheckboxes($form);

    return $form;

  }

  /**
   *
   */
  public function goBack() {
    $url = "https://tonlistavefur-islands.ddev.site/music_search/search";
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * Save the data from the form.
   *
   * This is essentially the same as submitForm but provides the ids from the
   * services like spotify and discogs in a keyed array.
   */
  abstract protected function saveData(array &$form, FormStateInterface $form_state, $ids);

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ids = [
      "spotify" => \Drupal::request()->query->get("spotify"),
      "discogs" => \Drupal::request()->query->get("discogs"),
    ];
    $this->saveData($form, $form_state, $ids);
    $this->goBack();
    $messenger = \Drupal::messenger();
    $messenger->addMessage('CONTENT ADDED', $messenger::TYPE_STATUS);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getFormId();

}
