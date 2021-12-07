<?php

namespace Drupal\spotify_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @file
 * Test.
 */

/**
 * Test.
 */
class SpotifyLookupService {

  protected $client;
  protected $configFactory;

  /**
   * Construct the service and add the HTTP client.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->client = \Drupal::httpClient();
    $this->configFactory = $configFactory;
  }

  /**
   * Get authorization for requests from spotify.
   */
  private function authorization() {
    $config = $this->configFactory->get("spotify_lookup.credentials");
    try {
      $authorization = $this->client->request('POST', 'https://accounts.spotify.com/api/token', [
        'form_params' => [
          'grant_type' => 'client_credentials',
          'client_id' => $config->get("spotify_client_id"),
          'client_secret' => $config->get("spotify_client_secret"),
        ],
      ]);

      return json_decode($authorization->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('spotify_client')->error($e);
    }

  }

  /**
   * Finding all information about an artist/album/track/.
   *
   * @param string $id
   *   The spotify ID.
   * @param string $category
   *   The category that the ID belongs to, either artists, albums or tracks.
   */
  public function idsearch($id, $category) {
    $auth = $this->authorization();

    try {
      $request = $this->client->request('GET', 'https://api.spotify.com/v1/' . $category . '/' . $id, [
        'headers' => [
          'Authorization' => $auth->token_type . ' ' . $auth->access_token,
        ],
      ]);

      $response = json_decode($request->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('spotify_client')->error($e);
    }

    return $response;

  }

  /**
   * Search in Spotify with a string.
   *
   * @param string $text
   *   The searched for text.
   * @param string $type
   *   The category being searched in, either artist, album or track.
   */
  private function searchByName(string $text, string $type) {
    if ($text === "") {
      return [];
    }

    $auth = $this->authorization();

    try {
      $request = $this->client->request('GET', 'https://api.spotify.com/v1/search?q=' . urlencode($text) . '&type=' . urlencode($type) . '&limit=10', [
        'headers' => [
          'Authorization' => $auth->token_type . ' ' . $auth->access_token,
        ],
      ]);

      $response = json_decode($request->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('spotify_client')->error($e);
    }

    // Get only the values inside $response->*->items.
    $results = [];
    foreach ($response as $key => &$value) {
      array_push($results, ...$value->items);
    }

    return $results;
  }

  /**
   * The the spotify ID of an artist/album/track.
   */
  public function getIdByName(String $name, String $type) {
    $results = $this->searchByName($name, $type);

    foreach ($results as &$result) {
      if ($name === $result->name) {
        return $result->id;
      }
    }

    return NULL;
  }

  /**
   * Search for a song on spotify.
   */
  public function search(String $text, String $type) {
    $items = $this->searchByName($text, $type);

    $name_list = [];

    foreach ($items as $item) {
      array_push($name_list, $item->name);
    }
    return $name_list;
  }

}
