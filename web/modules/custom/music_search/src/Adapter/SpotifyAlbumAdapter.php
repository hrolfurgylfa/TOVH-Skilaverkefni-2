<?php

namespace Drupal\music_search\Adapter;

use Drupal\music_search\Interface\IAlbum;

/**
 *
 */
class SpotifyAlbumAdapter implements IAlbum {

  protected $spotifyAlbum;

  public function __construct($spotifyAlbum) {
    $this->spotifyAlbum = $spotifyAlbum;
  }

  public function getName(): string {
    return $this->spotifyAlbum->name;
  }

  public function getArtistName(): string
  {
    // TODO: Implement getArtistName() method.
    return "";
  }

  public function getDescription(): string {
    return "";
  }

  public function getImageURL(): string {
    if (count($this->spotifyAlbum->images) === 0) {
      return "";
    }
    else {
      return $this->spotifyAlbum->images[0]->url;
    }
  }

  public function getTracks(): array
  {
    return [];
  }

  public function getGenres(): array {
    return $this->spotifyAlbum->genres;
  }

}
