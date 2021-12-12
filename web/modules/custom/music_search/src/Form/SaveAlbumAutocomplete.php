<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Form to handle article autocomplete.
 */
class SaveAlbumAutocomplete extends BaseSaveAutocomplete {

  /**
   * {@inheritDoc}
   */
  protected function getSpotifyData($spotify_id) {
    return $this->musicSearchService->getSpotifyAlbum($spotify_id);
  }

  /**
   * {@inheritDoc}
   */
  protected function getDiscogsData($discogs_id) {
    return $this->musicSearchService->getDiscogsRelease($discogs_id);
  }

  /**
   *
   */
  private function getOrCreateArtist(array $artist_ids): Node {

    // Find the node id based on the spotify and discogs ids.
    $nids = [];
    foreach ($artist_ids as $artist_id) {
      $theid = [];
      $theid = \Drupal::entityQuery('node')->condition('type', 'artist')->condition('field_spotify_id', $artist_id)->execute();
      if (count($theid) === 0) {
        $theid = \Drupal::entityQuery('node')->condition('type', 'artist')->condition('field_discogs_id', $artist_id)->execute();
      }
      if (count($theid) !== 0) {
        array_push($nids, $theid);
      }
    }

    // If an artist has a spotify and discogs id, we will receive duplicate nids.
    $flat_nids = array_merge(...$nids);
    $flat_nids = array_unique($flat_nids, SORT_STRING);

    // Get the nodes from the node ids.
    $nodes = Node::loadMultiple($flat_nids);

    // Artist exists.
    if ($nodes !== NULL and count($nodes) > 0) {
      return array_values($nodes)[0];
    }
    // Artist does not exist - create him.
    else {
      return $this->nodeAutocreation->createArtist($artist_ids);
    }
  }

  /**
   *
   */
  private function getOrCreateTracks($track_ids) {

    // Find the node id based on the spotify and discogs ids.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'song')
      ->condition('field_spotify_id', $track_ids, "IN");

    $n_ids = $query->execute();

    // Load the nodes.
    $nodes = Node::loadMultiple($n_ids);

    // Store the nodes keyed with spotify id for better performance.
    $spotify_id_nodes = [];
    foreach ($nodes as $node) {
      $spotify_id = $node->get("field_spotify_id")->getValue()[0]["value"];
      $spotify_id_nodes[$spotify_id] = $node;
    }

    // Create the nodes that weren't found and return all the nodes.
    $return_nodes = [];
    foreach ($track_ids as $track_id) {
      if ($spotify_id_nodes[$track_id] === NULL) {
        // Create the node as it wasn't found.
        $return_nodes[] = $this->nodeAutocreation->createTrack([$track_id]);
      }
      else {
        // Add the node to the return array.
        $return_nodes[] = $spotify_id_nodes[$track_id];
      }
    }

    return $return_nodes;
  }

  /**
   * {@inheritdoc}
   */
  public function addFields(array &$form, FormStateInterface $form_state, array $autofill_data) {

    // Album name.
    $names = $this->getAll(function ($item) {
      return $item->getName();
    }, $autofill_data);
    $this->radioWithOther($form, "name", [
      '#type' => "radios",
      '#title' => "Name of album",
      '#options' => array_combine($names, $names),
      "#required" => TRUE,
    ]);

    // Date released.
    $year = $this->getAll(function ($item) {
      return $item->getReleaseDate();
    }, $autofill_data);
    $year_list = array_map(function ($item) {
      return $item;
    }, $year);
    $this->radioWithOther($form, "year", [
      '#type' => "radios",
      '#title' => "Year Released",
      '#options' => array_combine($year_list, $year_list),
      "#required" => TRUE,
    ]);

    // Artist.
    $artist_ids = $this->getAll(function ($item) {
      return $item->getArtistsId();
    }, $autofill_data);

    $artist_node = $this->getOrCreateArtist($artist_ids);

    $form["artist"] = [
      '#type' => "radios",
      '#title' => "Artist",
      '#options' => [$artist_node->id() => $artist_node->getTitle()],
      "#required" => TRUE,
      '#default_value' => $artist_node->id(),
    ];

    /**
   * Album description.
   */
    $descriptions = $this->getAll(function ($item) {
      return $item->getDescription();
    }, $autofill_data);
    $description_keys = $this->makeKeysSafe($descriptions);
    $this->radioWithOther($form, "description", [
      '#type' => "radios",
      '#title' => "Description",
      '#options' => array_combine($description_keys, $descriptions),
      "#required" => TRUE,
    ]);

    /**
   * Album image.
   */
    $images = $this->getAll(function ($item) {
      return $item->getImageURL();
    }, $autofill_data);
    $image_html = array_map(function ($item) {
      return '<img src="' . $item . '" width="100" height="auto">';
    }, $images);
    $this->radioWithOther($form, "images", [
      '#type' => "radios",
      '#title' => "Images",
      '#options' => array_combine($images, $image_html),
      "#required" => TRUE,
    ]);

    // Tracks.
    $tracks = $this->getAll(function ($item) {
      return $item->getTracks();
    }, $autofill_data);
    $flat_track_list = array_merge(...$tracks);
    $neutral = array_map(function ($item) {
      return [$item["id"] => trim($item["name"])];
    }, $flat_track_list);
    $extra_flat = array_merge(...$neutral);
    $unique_array = array_unique($extra_flat, SORT_STRING);
    asort($unique_array);
    $this->radioWithOther($form, "tracks", [
      '#type' => "radios",
      '#title' => "Tracks",
      '#options' => $unique_array,
      "#required" => TRUE,
    ]);

    $this->radioWithOther($form, "images", [
      '#type' => "radios",
      '#title' => "Images",
      '#options' => array_combine($images, $image_html),
      "#required" => TRUE,
    ]);

    /**
   * Genres.
   */
    $genres = $this->getAll(function ($item) {
      return $item->getGenres();
    }, $autofill_data);
    $flat_genres = array_merge(...$genres);
    $this->radioWithOther($form, "genres", [
      '#type' => "radios",
      '#title' => "Genres",
      '#options' => array_combine($flat_genres, $flat_genres),
    ]);

  }

  /**
   * {@inheritdoc}
   */
  protected function saveData(array &$form, FormStateInterface $form_state, $ids) {
    // Get the relevant parameters.
    $name = $this->getRadioWithOther($form_state, "name");
    $year = $this->getRadioWithOther($form_state, "year");
    $description = $this->getRadioWithOther($form_state, "description");
    $description = $this->getSafeKey($description);
    $artist_nid = $this->getFormStateValue($form_state, 'artist');
    $images = $this->getRadioWithOther($form_state, "images");
    $genres = $this->getRadioWithOther($form_state, "genres");
    $songs = $this->getRadioWithOther($form_state, "tracks");
    $songs = [$songs];

    // Create the media.
    $image_path = $images;
    $media = $this->nodeAutocreation->createImage($image_path);

    // Create the selected tracks.
    $track_nodes = $this->getOrCreateTracks($songs);

    // Create the selected genres terms.
    $genres = [$genres];
    $genre_terms = $this->nodeAutocreation->getOrCreateVocabularyTerms($genres, "music_genre");

    // Create the content.
    $node = Node::create([
      "type" => "record",
      "title" => $name,
      "status" => Node::PUBLISHED,
      "field_performer" => Node::load($artist_nid),
      "field_song" => $track_nodes,
      "field_release_year" => $year,
      "field_description" => $description,
      "field_cover_image" => $media,
      "field_music_genre" => $genre_terms,
      "field_discogs_id" => $ids["discogs"],
      "field_spotify_id" => $ids["spotify"],
    ]);
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "music_search_create_album_from_search";
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $images = $this->getRadioWithOther($form_state, "images");
    if (filter_var($images, FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('CreateAlbumUrl', $this->t('Cover image must be a valid url to create the album.'));
    }
  }

}
