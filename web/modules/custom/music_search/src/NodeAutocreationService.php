<?php

namespace Drupal\music_search;

use Drupal\duration_field\Service\DurationService;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Create nodes automatically from data that can be fetched from spotify and
 * discogs.
 */
class NodeAutocreationService {
  /**
   * Store the injected SpotifyLookupService.
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $musicSearchService;

  /**
   * Store the injected DurationService.
   *
   * @var \Druapl\duration_field\Service\DurationService
   */
  protected $durationService;

  /**
   * Construct the class with the dependencies injected by Drupal.
   */
  public function __construct(MusicSearchService $musicSearchService, DurationService $durationService) {
    $this->musicSearchService = $musicSearchService;
    $this->durationService = $durationService;
  }

  /**
   * Get the first item that is set or return the default if none of the items return a set value.
   */
  private function getFirstOrDefault(callable $get_func, array $data, mixed $default = []) {
    $result = $this->getAllOrDefault($get_func, $data, NULL);
    return $result === NULL ? $default : $result[0];
  }

  /**
   *
   */
  private function getAllOrDefault(callable $get_func, array $data, mixed $default = NULL) {
    $results = [];
    foreach ($data as $item) {
      $result = $get_func($item);
      if ($result !== NULL && $result !== "" && !(is_array($result) && count($result) === 0)) {
        $results[] = $result;
      }
    }

    return count($results) === 0 ? $default : $results;
  }

  /**
   * Get the name of an image from it's URL, even if it doesn't have a file extension.
   */
  private function getImageNameWithExtension(string $image_url): string {
    $image_name = end(explode("/", $image_url));
    $has_extension = str_contains($image_name, ".");
    return $has_extension ? $image_name : $image_name . ".jpeg";
  }

  /**
   * Save an image from a URL to the media library.
   */
  public function createImage(string $image_url, ?string $filename = NULL): Media {
    // Get the filename if there is none provided.
    if ($filename === NULL) {
      $filename = $this->getImageNameWithExtension($image_url);
    }

    // Place to be stored.
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . basename($filename);

    // Make image from link.
    $file_data = file_get_contents($image_url);
    $file = file_save_data($file_data, $destination);
    $media = Media::create([
      'bundle' => 'image',
      'uid' => \Drupal::currentUser()->id(),
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ]);

    // Get the name without extension.
    $filename_no_ext = explode(".", $filename)[0];

    // Set to published.
    $media->setName($filename_no_ext)
      ->setPublished(TRUE)
      ->save();

    return $media;
  }

  /**
   *
   */
  public function createImages(array $image_urls, array $filenames = NULL): array {
    $images = [];
    for ($i = 0; $i < count($image_urls); $i++) {
      $images[] = $this->createImage($image_urls[$i], $filenames === NULL ? NULL : $filenames[$i]);
    }
    return $images;
  }

  /**
   *
   */
  public function getOrCreateVocabularyTerms(array $term_names, string $vocabulary): array {
    // Get the existing terms.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vocabulary);
    $term_ids = $query->execute();
    $terms = Term::loadMultiple($term_ids);

    // Turn the existing terms into a associative array for better performance.
    $dict_terms = [];
    foreach ($terms as $term) {
      $dict_terms[strtolower($term->getName())] = $term;
    }

    // Get or create the terms.
    $return_terms = [];
    foreach ($term_names as $term_name) {
      $term = $dict_terms[strtolower($term_name)];

      // Create them if needed.
      if ($term === NULL) {
        $term = Term::create([
          "vid" => $vocabulary,
          "name" => $term_name,
        ]);
      }

      // Save the term to the return array.
      $return_terms[] = $term;
    }

    return $return_terms;
  }

  /**
   * Convert seconds to a balanced DateInterval object.
   */
  public function secondsToDateInterval(int $seconds): \DateInterval {
    $interval = new \DateInterval("PT" . $seconds . "S");
    $d1 = new \DateTimeImmutable();
    $d2 = $d1->add($interval);
    return $d2->diff($d1);
  }

  /**
   * Create an artist from their IDs automatically with no human interaction.
   */
  public function createArtist(array $artist_ids): Node {

    // Get all the data needed to create the artist.
    $all_data = [];
    $spotify_id = NULL;
    $discogs_id = NULL;
    foreach ($artist_ids as $id) {
      if (is_numeric($id)) {
        // This is a discogs ID.
        $all_data[] = $this->musicSearchService->getDiscogsArtist($id);
        $discogs_id = $id;
      }
      else {
        // This must be a spotify ID.
        $all_data[] = $this->musicSearchService->getSpotifyArtist($id);
        $spotify_id = $id;
      }
    }

    // Grab the info or default values.
    $name = $this->getFirstOrDefault(function ($item) {
      return $item->getName();
    }, $all_data, "");
    $description = $this->getFirstOrDefault(function ($item) {
      return $item->getDescription();
    }, $all_data, "");
    $images = $this->getAllOrDefault(function ($item) {
      return $item->getImageURL();
    }, $all_data, ["https://cdn.pixabay.com/photo/2015/04/18/11/03/profile-728591_960_720.jpg"]);
    $websiteLink = $this->getFirstOrDefault(function ($item) {
      return $item->getWebsiteLink();
    }, $all_data, "https://example.com");
    $birthDate = $this->getFirstOrDefault(function ($item) {
      return $item->getBirthDate();
    }, $all_data, \DateTime::createFromFormat("d/m/Y H:i:s", "1/1/1970 0:0:0"), \DateTimeZone::UTC);
    $deathDate = $this->getFirstOrDefault(function ($item) {
      return $item->getDeathDate();
    }, $all_data, NULL);
    $genres = $this->getAllOrDefault(function ($item) {
      return $item->getGenres();
    }, $all_data, []);

    // Create the images.
    $image_entities = $this->createImages($images);

    // Flatten the genres.
    $flat_genres = array_merge(...$genres);

    // Create and save the node.
    $node = Node::create([
      "type" => "artist",
      "title" => $name,
      "status" => Node::PUBLISHED,
      "field_description" => $description,
      "field_band_members" => [],
      "field_birth_date" => $birthDate,
      "field_death_date" => $deathDate,
      "field_images_media" => $image_entities,
      "field_website" => $websiteLink,
      "field_mus" => $this->getOrCreateVocabularyTerms($flat_genres, "music_genre"),
      "field_discogs_id" => $discogs_id,
      "field_spotify_id" => $spotify_id,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Create a track from their ID automatically with no human interaction.
   *
   * This track can take in multiple IDs but currently only spotify IDs are
   * supported as Discogs doesn't have track IDs.
   */
  public function createTrack(array $track_ids): Node {

    // Get all the data needed to create the artist.
    $all_data = [];
    $spotify_id = NULL;
    foreach ($track_ids as $id) {
      // This must be a spotify ID as Discogs doesn't have IDs.
      $all_data[] = $this->musicSearchService->getSpotifyTrack($id);
      $spotify_id = $id;
    }

    // Grab the values or defaults.
    $name = $this->getFirstOrDefault(function ($item) {
      return $item->getName();
    }, $all_data, "");
    $duration = $this->getFirstOrDefault(function ($item) {
      return $item->getDuration();
    }, $all_data, "");

    // Calculate the duration object.
    $duration_interval = $this->secondsToDateInterval($duration);
    // Get the duration string.
    $duration_str = $this->durationService->getDurationStringFromDateInterval($duration_interval);

    // Create the content.
    $node = Node::create([
      "type" => "song",
      "title" => $name,
      "field_length" => ["duration" => $duration_str],
      "status" => Node::PUBLISHED,
      "field_spotify_id" => $spotify_id,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Create multiple tracks at once with their IDs.
   */
  public function createTracks(array $track_ids): array {
    return array_map(function ($track_id) {
      return $this->createTrack([$track_id]);
    }, $track_ids);
  }

}
