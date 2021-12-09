<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form to handle article autocomplete.
 */
class SaveTrackAutocomplete extends BaseSaveAutocomplete {

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
      array_push($all_autofill_data, $this->musicSearchService->getSpotifyTrack($spotify_id));
    }
    if ($discogs_id) {
      array_push($all_autofill_data, $this->musicSearchService->getDiscogsTrack($discogs_id));
    }

    // Track name.
    $names = $this->getAll(function ($item) {
      return $item->getName();
    }, $all_autofill_data);
    $this->radioWithOther($form, "name", [
      '#type' => "radios",
      '#title' => "Name",
      '#options' => array_combine($names, $names),
      "#required" => TRUE,
    ]);

    // Track length.
    $length = $this->getAll(function ($item) {
      return $item->getLength();
    }, $all_autofill_data);
    $this->radioWithOther($form, "length", [
      '#type' => "radios",
      '#title' => "Length",
      '#options' => array_combine($length, $length),
      "#required" => TRUE,
    ]);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the relevant parameters.
    $name = $this->getRadioWithOther($form_state, "name");

    $spotify_id = \Drupal::request()->query->get("spotify");
    $discogs_id = \Drupal::request()->query->get("discogs");



    // Create the content.
    $node = Node::create([
      "type" => "song",
      "title" => $name,
      "status" => Node::PUBLISHED,
      "field_discogs_id" => $discogs_id,
      "field_spotify_id" => $spotify_id,
    ]);
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_create_track_from_search";
  }

}
