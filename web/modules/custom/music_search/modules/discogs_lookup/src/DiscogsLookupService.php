<?php

namespace Drupal\discogs_lookup;

use GuzzleHttp\Exception\GuzzleException;

/**
 *
 */
class DiscogsLookupService {

  protected $client;
  protected $configFactory;

  /**
   * Construct the service and add the HTTP client.
   */
  public function __construct() {
    $this->client = \Drupal::httpClient();
  }

  /**
   * Get authorization for requests from Discogs.
   */
  private function authorization() {
    $config = $this->configFactory->get('discogs_lookup.credentials');
    $discogs_token = $config->get('token');

    return 'Discogs token='.$discogs_token;

    //return 'Discogs token=xKBaHLNYZNAXnqFvJJXCSgvEEjDChMlEbkbhsmAe';
  }

  /**
   * Finding all information about an artist/release.
   *
   * @param string $category
   *   The category that the ID belongs to, either artists or releases.
   *
   * @param string $id
   *   The Discogs ID.
   *
   * @todo held að sé reddy - eftir að testa
   */
  public function idsearch($id, $category) {
    $auth = $this->authorization();

    if ($category == 'albums') {
      $category = 'releases';
    }

    try {
      $request = $this->client->request('GET', 'https://api.discogs.com/' . $category . '/' . $id, [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);

      $response = json_decode($request->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('discogs_client')->error($e);
    }

    return $response;

  }

  /**
   * Search in Discogs with a string.
   *
   * @param string $text
   *   The searched for text.
   *
   * @param string $category
   *   The category to be searched. Discogs can only search for artists or albums.
   */
  public function search(String $text, String $category) {
    $auth = $this->authorization();

    if ($category == 'albums') {
      $category = 'releases';
    }

    try {
      $request = $this->client->request('GET', 'https://api.discogs.com/database/search?q=' . urlencode($text) . '&type=' . urlencode($category), [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);

      $response = json_decode($request->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('discogs_client')->error($e);
    }

    $name_list = [];

    foreach ($response->results as $item) {
      array_push($name_list, $item->title);
    }
    return $name_list;
  }

}
