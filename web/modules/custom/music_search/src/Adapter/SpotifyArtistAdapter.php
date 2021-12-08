<?php

namespace Drupal\music_search\Adapter;

use Drupal\music_search\Interface\IArtist;

/**
 *
 */
class SpotifyArtistAdapter implements IArtist {

  protected $spotifyArtist;

  public function __construct($spotifyArtist) {
    $this->spotifyArtist = $spotifyArtist;
  }

  public function getName(): string {
    return $this->spotifyArtist->name;
  }

  public function getDescription(): string {
    return "";
  }

  public function getImageURL(): string {
    if (count($this->spotifyArtist->images) === 0) {
      return "";
    }
    else {
      return $this->spotifyArtist->images[0]->url;
    }
  }

  public function getWebsiteLink(): string {
    return "";
  }

  public function getBirthDate(): ?\DateTime {
    return NULL;
  }

  public function getDeathDate(): ?\DateTime {
    return NULL;
  }

  public function getGenres(): array {
    return $this->spotifyArtist->genres;
  }

}
