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
   * Search on spotify.
   */
  public function search(String $text) {
    return $this->spotifyLookup->search($text);
  }

}
