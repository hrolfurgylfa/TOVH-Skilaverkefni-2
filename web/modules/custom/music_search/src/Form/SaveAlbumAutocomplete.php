<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * {@inheritdoc}
   */
  public function addFields(array &$form, FormStateInterface $form_state, array $autofill_data) {

    // Album name
    $names = $this->getAll(function ($item) {
      return $item->getName();
    }, $autofill_data);
    $this->radioWithOther($form, "name", [
      '#type' => "radios",
      '#title' => "Name of album",
      '#options' => array_combine($names, $names),
      "#required" => TRUE,
    ]);

    // Artist of album

    // Get the spotify and/or discogs ids
    $artists = $this->getAll(function ($item) {
      return $item->getArtistsId();
    }, $autofill_data);


    // Find the node id based on the spotify and discogs ids
    $nids = [];
    foreach ($artists as $artist) {
      $theid = [];
      $theid = \Drupal::entityQuery('node')->condition('type','artist')->condition('field_spotify_id', $artist)->execute();
      if (count($theid) === 0) {
        $theid = \Drupal::entityQuery('node')->condition('type','artist')->condition('field_discogs_id', $artist)->execute();
      }
      if (count($theid) !== 0) {
        array_push($nids, $theid);
      }
    }

    // If an artist has a spotify and discogs id, we will receive duplicate nids
    $flat_nids = array_merge(...$nids);
    $nids_found = [];
    foreach ($flat_nids as $nid) {
      if (in_array($nid, $nids_found)) {
        // do nothing
      } else {
        array_push($nids_found, $nid);
      }
    }

    //Get the nodes from the node ids
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids_found);

    //Find the name of the artist to display in the form
    $nodenames = [];
    foreach ($nodes as $node) {
      $info = $node->getTranslatableFields();
      $title = $info['title'];
      $list = $title->getValue();
      array_push($nodenames, $list[0]);
    }
    $nodenames = array_merge(...$nodenames);

    // Artist exists
    if ($nodes !== null) {
      $form["artist"] = [
        '#type' => "radios",
        '#title' => "Artist",
        '#options' => array_combine($nids_found, $nodenames),
        "#required" => TRUE,
      ];
    }
    // Artist does not exist - create him
    else {
      $node = Node::create([
        "type" => "artist",
        "title" => "",
        "status" => Node::PUBLISHED,
        "field_description" => "",
        "field_band_members" => [],
        "field_birth_date" => "",
        "field_death_date" => "",
        "field_images_media" => [],
        "field_website" => "website_link",
        "field_mus" => "",
        "field_discogs_id" => "",
        "field_spotify_id" => "",
      ]);
      $node->save();
      $nid = $node->get('nid')->getValue();
      $title = $info['title'];
      $list = $title->getValue();

      $form["artist"] = [
        '#type' => "radios",
        '#title' => "Artist",
        '#options' => array_combine($nid[0], $list),
        "#required" => TRUE,
      ];
    }

    // Album description.
    $descriptions = $this->getAll(function ($item) {
      return $item->getDescription();
    }, $autofill_data);
    $this->radioWithOther($form, "description", [
      '#type' => "radios",
      '#title' => "Description",
      '#options' => array_combine($descriptions, $descriptions),
      "#required" => TRUE,
    ]);

    // Album image.
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

    // Tracks
    $tracks = $this->getAll(function ($item) {
      return $item->getTracks();}, $autofill_data);
    $track_list = array_map(function ($item) {
      return $item;
    }, $tracks);
    $flat_track_list = array_merge(...$track_list);
    $neutral = array_map(function ($item) {
      return trim($item);
    }, $flat_track_list);
    $unique_array = array_unique($neutral, SORT_STRING);
    sort($unique_array);
    $this->radioWithOther($form, "tracks", [
      '#type' => "radios",
      '#title' => "Tracks",
      '#options' => array_combine($unique_array, $unique_array),
      "#required" => TRUE,
    ]);


    $this->radioWithOther($form, "images", [
      '#type' => "radios",
      '#title' => "Images",
      '#options' => array_combine($images, $image_html),
      "#required" => TRUE,
    ]);


    // Genres.
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
    $description = $this->getRadioWithOther($form_state, "description");
    $artist_nid = $this->getRadioWithOther($form_state, 'artist');
    $images = $this->getRadioWithOther($form_state, "images");
    $genres = $this->getRadioWithOther($form_state, "genres");


    // Create the media.
    $image_path = $images;
    $image_name = $this->getImageNameWithExtension($image_path);
    $media = $this->saveImage($image_path, $image_name);

    // Create the selected genres terms.
    $genres = [$genres];
    $genre_terms = $this->getOrCreateVocabularyTerms($genres, "music_genre");

    // Create the content.
    $node = Node::create([
      "type" => "album",
      "title" => $name,
      "status" => Node::PUBLISHED,
      "artist" => Node::load($artist_nid),
      "field_description" => $description,
      "field_images_media" => [$media],
      "field_mus" => $genre_terms,
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

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $website_link = $this->getRadioWithOther($form_state, "website_link");
    $images = $this->getRadioWithOther($form_state, "images");
    if (filter_var($website_link, FILTER_VALIDATE_URL) === FALSE || filter_var($images, FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('CreateAlbumUrl', $this->t('Website link and images must be valid urls to create album.'));
    }
  }

}
