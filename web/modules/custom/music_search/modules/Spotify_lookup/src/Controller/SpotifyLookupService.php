<?php
/**
 * @file
 * Contains \Drupal\Spotify_lookup\Controller\SpotifyController.
 */

namespace Drupal\Spotify_lookup\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\GuzzleException;


class SpotifyController extends ControllerBase
{

  protected $client;

  public function __construct()
  {
    $this->client = \Drupal::httpClient();
  }

  private function authorization()
  {

    try {
      $authorization = $this->client->request('POST', 'https://accounts.spotify.com/api/token', [
        'form_params' => [
          'grant_type' => 'client_credentials',
          'client_id' => 'be336bc6550f4ca09b1b867616f22ce3',
          'client_secret' => '3cfcd4f611d247ae8bcb07da3f8d1f6e'
        ]
      ]);

      return json_decode($authorization->getBody());
    } catch (GuzzleException $e) {
      return \Drupal::logger('spotify_client')->error($e);
    }

  }

  /**
   * Finding artist
   * @param $id
   */
  public function artist($id)
  {
    $auth = $this->authorization();

    try {
      $requestArtist = $this->client->request('GET', 'https://api.spotify.com/v1/artists/' . $id, [
        'headers' => [
          'Authorization' => $auth->token_type . ' ' . $auth->access_token
        ]
      ]);

      $responseArtist = json_decode($requestArtist->getBody());
    } catch (GuzzleException $e) {
      echo 'Error page 2';
      return \Drupal::logger('spotify_client')->error($e);
    }

    return $responseArtist;

  }

}


