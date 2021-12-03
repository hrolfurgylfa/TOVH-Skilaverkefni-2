<?php
namespace Drupal\music_search\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
/**
 * Controller for the salutation message.
 */
class MusicSearchController extends ControllerBase {
  /**
   * Hello World.
   *
   * @return array
   *   Our message.
   */
  public function testPage() {
    return [
      '#markup' => $this->t('Hello World'),
    ];
  }
  
  public function searchFormAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');

    # TODO: Call a module to search Spotify and discogs with $input

    array_push($results, "Yey");
    array_push($results, "Test");

    return new JsonResponse($results);
  }
}
