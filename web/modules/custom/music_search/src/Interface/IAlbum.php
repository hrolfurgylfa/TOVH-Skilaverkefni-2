<?php

namespace Drupal\music_search\Interface;

/**
 * An interface that holds the information we store about them.
 */
interface IAlbum {
  public function getName(): string;
  public function getArtistName(): string;
  public function getDescription(): string;
  public function getImageURL(): string;
  public function getTracks(): array;
  public function getGenres(): array;

}
