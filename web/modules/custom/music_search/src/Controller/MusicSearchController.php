<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the salutation message.
 */
class MusicSearchController extends ControllerBase {

  /**
   * Store the injected MusicSearchService.
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $musicSearch;

  /**
   * Construct the class.
   */
  public function __construct(MusicSearchService $musicSearch) {
    $this->musicSearch = $musicSearch;
  }

  /**
   * Inject the dependencies.
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get("music_search")
    );
  }

  /**
   * Provide autocomplete for records.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request coming in.
   */
  public function searchFormAutocomplete(Request $request) {
    $input = $request->query->get('q');
    $searchType = $request->get('search_type');
    $results = $this->musicSearch->search($input, $searchType);

    return new JsonResponse($results);
  }

  /**
   * Determine what type of search results the autocomplete should yield (track, artist or album).
   */
  public function getSearchResultType() {

  }
}
