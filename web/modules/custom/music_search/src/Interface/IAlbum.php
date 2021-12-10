<?php

namespace Drupal\music_search\Interface;

/**
 * An interface that holds the information we store about them.
 */
interface IAlbum {
  public function getName(): string;
  public function getArtistsId(): string;
  public function getDescription(): string;
  public function getImageURL(): string;
  public function getTracks(): array;
  public function getGenres(): array;
  public function getReleaseDate(): int;
  public function getLabel(): array;


}
