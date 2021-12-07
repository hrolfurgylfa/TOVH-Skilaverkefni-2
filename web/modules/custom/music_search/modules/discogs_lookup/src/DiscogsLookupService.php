<?php

namespace Drupal\discogs_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;


class DiscogsLookupService {

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
   * Get authorization for requests from Discogs.
   */

  // @todo Laga authorization
  private function authorization() {
    //$config = $this->configFactory->get("spotify_lookup.credentials");
    try {
      $authorization = $this->client->request('POST', 'https://api.discogs.com/oauth/access_token', [
        'form_params' => [
          'grant_type' => 'client_credentials',
          //'client_id' => $config->get("spotify_client_id"),
          //'client_secret' => $config->get("spotify_client_secret"),
          'client_id' => 'LpTpaWmlVDeUZRLzuUMp',
          'client_secret' => 'mazzaUZJQLAGLzjEIyRQGOfoImkfpdvc',
        ],
      ]);

      return json_decode($authorization->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('discogs_client')->error($e);
    }

  }

  /**
   * Finding all information about an artist/release.
   *
   * @param string $category
   *   The category that the ID belongs to, either artists or releases
   *
   * @param string $id
   *   The Discogs ID
   *
   * @todo held að sé reddy - eftir að testa
   *
   */
  public function idsearch($id, $category) {
    $auth = $this->authorization();

    if ($category == 'albums') {
      $category = 'releases';
    }

    try {
      $request = $this->client->request('GET', 'https://api.discogs.com/' . $category . '/' . $id, [
        'headers' => [
          'Authorization' => $auth->token_type . ' ' . $auth->access_token,
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
   * @param String $text
   *   The searched for text
   *
   * @param String $category
   *   The category to be searched. Discogs can only search for artists or albums.
   *
   */
  public function search(String $text, String $category) {
    $auth = $this->authorization();

    if ($category == 'album') {
      $category = 'release';
    }

    try {
      $request = $this->client->request('GET', 'https://api.discogs.com/database/search?q=' . urlencode($text) . '&type=' . urlencode($category), [
        'headers' => [
          'Authorization' => $auth->token_type . ' ' . $auth->access_token,
        ],
      ]);

      $response = json_decode($request->getBody());
    }
    catch (GuzzleException $e) {
      return \Drupal::logger('discogs_client')->error($e);
    }

    $name_list = [];

    foreach ($response->artists->items as $item) {
      array_push($name_list, $item->name);
    }
    return $name_list;
  }

}
