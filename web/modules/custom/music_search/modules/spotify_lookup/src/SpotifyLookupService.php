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
   * Finding artist.
   *
   * @param string $id
   *   The spotify ID of the artist.
   */
  public function artist($id) {
    $auth = $this->authorization();

    try {
      $requestArtist = $this->client->request('GET', 'https://api.spotify.com/v1/artists/' . $id, [
        'headers' => [
          'Authorization' => $auth->token_type . ' ' . $auth->access_token,
        ],
      ]);

      $responseArtist = json_decode($requestArtist->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('spotify_client')->error($e);
    }

    return $responseArtist;

  }

  /**
   * Search for a song on spotify.
   */
  public function search(String $text, String $type) {
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

    $name_list = [];

    foreach ($response->artists->items as $item) {
      array_push($name_list, $item->name);
    }
    return $name_list;
  }

}
