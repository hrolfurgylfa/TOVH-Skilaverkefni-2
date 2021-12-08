<?php

namespace Drupal\music_search\Interface;

/**
 * An interface that holds the information we store about them.
 */
interface ITrack {
  public function getName(): string;
  public function getDuration(): ?int;
}
