<?php

namespace Drupal\music_search;

use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\music_search\Adapter\DiscogsAlbumAdapter;
use Drupal\music_search\Adapter\DiscogsArtistAdapter;
use Drupal\music_search\Adapter\SpotifyAlbumAdapter;
use Drupal\music_search\Adapter\SpotifyArtistAdapter;
use Drupal\music_search\Adapter\SpotifyTrackAdapter;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\Validator\Constraints\IsFalse;

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
    return new SpotifyAlbumAdapter($this->spotifyLookup->idsearch($id, 'albums'));
  }

  /**
   * Get a track from spotify.
   */
  public function getSpotifyTrack(String $id) {
    return new SpotifyTrackAdapter($this->spotifyLookup->idsearch($id, 'tracks'));
  }

  /**
   * Get an artist from Discogs.
   */
  public function getDiscogsArtist(String $id) {
    return new DiscogsArtistAdapter($this->discogsLookup->idsearch($id, 'artists'));
  }

  /**
   * Get a release from Discogs.
   */
  public function getDiscogsRelease(String $id) {
    return new DiscogsAlbumAdapter($this->discogsLookup->idsearch($id, 'releases'));
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
    $discogstype = "";
    if ($type === 'albums' or $type === 'tracks') {
      $discogstype = 'releases';
    }

    return [
      "spotify" => $this->spotifyLookup->getIdByName($name, $type),
      "discogs" => $this->discogsLookup->getIdByName($name, $discogstype),
    ];
  }

  public function displaynames($spotifynames, $discogsnames) {
    $displaynames = [];
    foreach ($spotifynames as $name) {
      if (str_contains($name, ';')) {
        continue;
      } elseif (in_array($name, $displaynames)) {
        // Do nothing
      } else {
        array_push($displaynames, $name);
      }
    }
    foreach ($discogsnames as $name) {
      if (preg_match('/^.*\([0-9]{1,3}\)$/m', $name) === 1) {
        continue;
      } elseif (str_contains($name, ';')) {
        continue;
      } elseif (in_array($name, $displaynames)) {
        // Do nothing
      } else {
        array_push($displaynames, $name);
      }
    }
    return $displaynames;
  }

  /**
   * Search on spotify and discogs.
   */
  public function search(String $text, String $type) {
    $discogsnames = $this->discogsLookup->search($text, $type);
    $spotifynames = $this->spotifyLookup->search($text, $type);
    $displaynames = $this->displaynames($spotifynames, $discogsnames);
    return array_slice($displaynames, 0, 10);
  }

  public function search_name_img(String $text, String $type) {
    $spotifynames = $this->spotifyLookup->search_choose($text, $type);
    $discogsnames = $this->discogsLookup->search_choose($text, $type);

    return ["discogsnames"=>array_slice($discogsnames, 0, 10), "spotifynames"=>array_slice($spotifynames, 0, 10)];
  }

}
