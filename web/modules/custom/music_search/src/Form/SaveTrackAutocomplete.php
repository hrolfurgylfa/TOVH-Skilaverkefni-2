<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\Adapter\NullTrackAdapter;
use Drupal\node\Entity\Node;

/**
 * Form to handle article autocomplete.
 */
class SaveTrackAutocomplete extends BaseSaveAutocomplete {

  /**
   * {@inheritDoc}
   */
  protected function getSpotifyData($spotify_id) {
    return $this->musicSearchService->getSpotifyTrack($spotify_id);
  }

  /**
   * {@inheritDoc}
   */
  protected function getDiscogsData($discogs_id) {
    return new NullTrackAdapter();
  }

  /**
   * {@inheritdoc}
   */
  protected function addFields(array &$form, FormStateInterface $form_state, $all_autofill_data) {

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
      return $item->getDuration();
    }, $all_autofill_data);
    $this->radioWithOther($form, "length", [
      '#type' => "radios",
      '#title' => "Length",
      '#options' => array_combine($length, $length),
      "#required" => TRUE,
    ], ["#type" => "duration"]);
  }

  /**
   * {@inheritdoc}
   */
  protected function saveData(array &$form, FormStateInterface $form_state, $ids) {
    // Get the relevant parameters.
    $name = $this->getRadioWithOther($form_state, "name");
    $length = $this->getRadioWithOther($form_state, "length");

    // Create the content.
    $node = Node::create([
      "type" => "song",
      "title" => $name,
      "field_length" => $length,
      "status" => Node::PUBLISHED,
      "field_spotify_id" => $ids->spotify,
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
