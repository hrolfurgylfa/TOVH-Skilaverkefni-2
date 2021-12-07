<?php

namespace Drupal\music_search\Interface;

/**
 * An interface that holds the information we store about them.
 */
interface IArtist {
  public function getName(): string;
  public function getDescription(): string;
  public function getImageURL(): string;
  public function getWebsiteLink(): string;
  public function getBirthDate(): ?\DateTime;
  public function getDeathDate(): ?\DateTime;
  public function getGenres(): array;
}
