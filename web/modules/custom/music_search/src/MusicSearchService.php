<?php

namespace Drupal\music_search;

use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\music_search\Adapter\SpotifyArtistAdapter;
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
   * Store the injected SpotifyLookupService.
   *
   * @var \Drupal\discogs_lookup\DiscogsLookupService
   */
  protected $discogsLookup;

  /**
   * Construct the class with the dependencies injected by Drupal.
   */
  public function __construct(SpotifyLookupService $spotifyLookup, DiscogsLookupService $discogsLookup) {
    $this->spotifyLookup = $spotifyLookup;
    $this->discogsLookup = $discogsLookup;
  }

  /**
   * Get an artist from spotify.
   */
  public function getSpotifyArtist(String $id) {
    return new SpotifyArtistAdapter($this->spotifyLookup->idsearch($id, 'artists'));
  }

  /**
   * Get an album from spotify.
   */
  public function getSpotifyAlbum(String $id) {
    return $this->spotifyLookup->idsearch($id, 'albums');
  }

  /**
   * Get a track from spotify.
   */
  public function getSpotifyTrack(String $id) {
    return $this->spotifyLookup->idsearch($id, 'tracks');
  }

  /**
   * Get the spotify and discogs IDs by the name of some artist/album/track.
   *
   * @param string $name
   *   The name of the artist/album/track.
   * @param string $type
   *   One of artist/album/track.
   *
   * @return array
   *   An array containing the Spotify ID under the key spotify and the Discogs
   *   ID under the key discogs
   */
  public function getIdsByName(string $name, string $type): array {
    return [
      "spotify" => $this->spotifyLookup->getIdByName($name, $type),
    ];
  }

  /**
   * Search on spotify.
   */
  public function search(String $text, String $type) {
    // @todo Look from the discogs API as well
    $discogsnames = $this->discogsLookup->search($text, $type);
    $spotifynames = $this->spotifyLookup->search($text, $type);
    return $discogsnames;
  }

}
