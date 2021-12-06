<?php

namespace Drupal\music_search;

use Drupal\spotify_lookup\SpotifyLookupService;

/**
 * Search functionality on spotify.
 */
class MusicSearchService {

  /**
   * Store the injected SpotifyLookupService.
   *
   * @var \Drupal\spotify_lookup\SpotifyLookupService
   */
  protected $spotifyLookup;

  /**
   * Construct the class with the dependencies injected by Drupal.
   */
  public function __construct(SpotifyLookupService $spotifyLookup) {
    $this->spotifyLookup = $spotifyLookup;
  }

  /**
   * Get an artist from spotify.
   */
  public function getSpotifyArtist(String $id) {
    return $this->spotifyLookup->artist($id);
  }

  /**
   * Search on spotify.
   */
  public function search(String $text) {
    $type = 'artist';
    $list = $this->spotifyLookup->search($text, $type);
    return $list;
  }

}
