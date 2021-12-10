<?php

namespace Drupal\music_search\Adapter;

use Drupal\music_search\Interface\ITrack;

/**
 * A track adapter that always returns the null values for its items.
 */
class NullTrackAdapter extends ITrack {

  public function getName(): string {
    return "";
  }
  
  public function getDuration(): ?int {
    return NULL;
  }

}
