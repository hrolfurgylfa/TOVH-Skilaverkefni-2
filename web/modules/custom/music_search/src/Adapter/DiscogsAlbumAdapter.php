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
    return $this->discogsAlbum->notes;
  }

  public function getImageURL(): string {
    if (count($this->discogsAlbum->images) === 0) {
      return "";
    }
    else {
      return $this->discogsAlbum->images[0]->uri;
    }
  }

  public function getTracks(): array
  {
    $returnlist = [];
    foreach ($this->discogsAlbum->tracklist as $track) {
      array_push($returnlist, ["name"=>$track->title, "duration"=>$track->duration]);
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
