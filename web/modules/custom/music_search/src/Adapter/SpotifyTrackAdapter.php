<?php

namespace Drupal\music_search\Adapter;

use Drupal\music_search\Interface\ITrack;

/**
 *
 */
class SpotifyTrackAdapter implements ITrack {

  protected $spotifyTrack;

  public function __construct($spotifyTrack) {
    $this->spotifyTrack = $spotifyTrack;
  }

  public function getName(): string {
    return $this->spotifyTrack->name;
  }

  public function getDuration(): ?int {
    $milliseconds = $this->spotifyTrack->duration_ms;
    return round($milliseconds/1000);
  }

}
