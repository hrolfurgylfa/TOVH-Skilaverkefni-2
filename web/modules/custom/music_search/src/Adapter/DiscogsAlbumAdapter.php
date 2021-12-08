<?php

namespace Drupal\music_search\Adapter;

use Drupal\music_search\Interface\IAlbum;

/**
 *
 */
class DiscogsAlbumAdapter implements IAlbum {

  protected $discogsAlbum;

  public function __construct($discogsAlbum) {
    $this->discogsAlbum = $discogsAlbum;
  }

  public function getName(): string {
    return $this->discogsAlbum->name;
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
    if (count($this->discogsAlbum->images) === 0) {
      return "";
    }
    else {
      return $this->discogsAlbum->images[0]->url;
    }
  }

  public function getTracks(): array
  {
    return [];
  }

  public function getGenres(): array {
    return $this->discogsAlbum->genres;
  }

}
