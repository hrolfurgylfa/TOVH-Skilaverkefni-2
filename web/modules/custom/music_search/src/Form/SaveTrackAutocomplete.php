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
    $seconds = $this->getAll(function ($item) {
      return $item->getDuration() < 3600 * 24 ? $item->getDuration() : NULL;
    }, $all_autofill_data);
    $human_length = array_map(function ($item) {
      $duration = $this->secondsToDateInterval($item);
      $short = $duration->format("%i min %s sec");
      $hours = $duration->format("%h hours ");
      return $duration->h === 0 ? $short : $hours . $short;
    }, $seconds);
    $this->radioWithOther($form, "length", [
      '#type' => "radios",
      '#title' => "Length",
      '#options' => array_combine($seconds, $human_length),
      "#required" => TRUE,
    ], ["#type" => "duration", "#granularity" => "h:i:s"]);
  }

  /**
   * Convert seconds to a balanced DateInterval object.
   */
  protected function secondsToDateInterval(int $seconds): \DateInterval {
    $interval = new \DateInterval("PT" . $seconds . "S");
    $d1 = new \DateTimeImmutable();
    $d2 = $d1->add($interval);
    return $d2->diff($d1);
  }

  /**
   * {@inheritdoc}
   */
  protected function saveData(array &$form, FormStateInterface $form_state, $ids) {
    $duration_service = \Drupal::service('duration_field.service');

    // Get the relevant parameters.
    $name = $this->getRadioWithOther($form_state, "name");
    $length = $this->getRadioWithOther($form_state, "length");

    // Turn the $length into into a date interval if it came in as seconds.
    if (!is_a($length, "DateInterval")) {
      $length = $this->secondsToDateInterval($length);
    }

    // Make the string the Duration Field addon expects.
    $duration = $duration_service->getDurationStringFromDateInterval($length);

    // Create the content.
    $node = Node::create([
      "type" => "song",
      "title" => $name,
      "field_length" => ["duration" => $duration],
      "status" => Node::PUBLISHED,
      "field_spotify_id" => $ids["spotify"],
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
