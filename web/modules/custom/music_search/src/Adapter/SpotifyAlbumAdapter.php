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

  public function getArtistsId(): array
  {
    $returnlist = [];
    if (count($this->spotifyAlbum->artists) >= 1) {
      foreach ($this->spotifyAlbum->artists as $artist) {
        array_push($returnlist, $artist->id);
      }
    }
    return $returnlist;
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
    $returnlist = [];
    if (count($this->spotifyAlbum->items) >= 1) {
      foreach ($this->spotifyAlbum->items as $track) {
        array_push($returnlist, $track->id);
      }
    }

    return $returnlist;

  }

  public function getGenres(): array {
    return $this->spotifyAlbum->genres;
  }

  public function getLabel(): array
  {
    $labelarray = explode(' / ', $this->spotifyAlbum->label);
    return $labelarray;
  }

  public function getReleaseDate(): int
  {
    $date = $this->spotifyAlbum->release_date;
    $year = substr($date, 0, 4);
    $intyear = intval($year);
    return $intyear;
  }

}
