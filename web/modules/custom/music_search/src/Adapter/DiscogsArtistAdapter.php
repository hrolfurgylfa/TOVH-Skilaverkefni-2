<?php

namespace Drupal\music_search\Adapter;

use Drupal\music_search\Interface\IArtist;

/**
 *
 */
class DiscogsArtistAdapter implements IArtist {

  protected $discogsArtist;

  public function __construct($discogsArtist) {
    $this->discogsArtist = $discogsArtist;
  }

  public function getName(): string {
    return $this->discogsArtist->name;
  }

  public function getDescription(): string {
    return "";
  }

  public function getImageURL(): string {
    if (count($this->discogsArtist->images) === 0) {
      return "";
    }
    else {
      return $this->discogsArtist->images[0]->uri;
    }
  }

  public function getWebsiteLink(): string {
    if (count($this->discogsArtist->urls) === 0) {
      return "";
    }
    else {
      return $this->discogsArtist->urls[0];
    }
  }

  public function getBirthDate(): ?\DateTime {
    return NULL;
  }

  public function getDeathDate(): ?\DateTime {
    return NULL;
  }

  public function getGenres(): array {
    return [];
  }

}
