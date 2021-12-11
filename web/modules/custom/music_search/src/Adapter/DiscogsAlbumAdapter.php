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
    return $this->discogsAlbum->title;
  }

  public function getArtistsId(): string
  {
    if (count($this->discogsAlbum->artists) >= 1) {
      return $this->discogsAlbum->artists[0]->id;
    }
    return '';
  }

  public function getDescription(): string {
    if (property_exists($this->discogsAlbum, 'notes')) {
      return $this->discogsAlbum->notes;
    }
    else {
      return '';
    }
  }

  public function getImageURL(): string {
    if (property_exists($this->discogsAlbum, 'images')) {
      if (count($this->discogsAlbum->images) === 0) {
        return "";
      }
      else {
        return $this->discogsAlbum->images[0]->uri;
      }
    }
    else {
      return '';
    }
  }

  public function getTracks(): array
  {
    $returnlist = [];
    foreach ($this->discogsAlbum->tracklist as $track) {
      array_push($returnlist, $track->title);
    }

    return $returnlist;

  }

  public function getGenres(): array {
    return $this->discogsAlbum->genres;
  }

  public function getLabel(): array
  {
    $returnlist = [];
    foreach ($this->discogsAlbum->labels as $label) {
      array_push($returnlist, $label->name);
    }
    return $returnlist;
  }

  public function getReleaseDate(): int
  {
    $date = $this->discogsAlbum->released;
    $year = substr($date, 0, 4);
    $intyear = intval($year);
    return $intyear;
  }

}
