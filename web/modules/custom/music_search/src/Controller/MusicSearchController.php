<?php
namespace Drupal\music_search\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
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
  
  public function searchFormAutocomplete() {
    $results = [];
    # $input = $request->query->get('q');

    // // Get the typed string from the URL, if it exists.
    // if (!$input) {
    //   return new JsonResponse($results);
    // }

    // $input = Xss::filter($input);

    // $query = $this->nodeStroage->getQuery()
    //   ->condition('type', 'article')
    //   ->condition('title', $input, 'CONTAINS')
    //   ->groupBy('nid')
    //   ->sort('created', 'DESC')
    //   ->range(0, 10);

    // $ids = $query->execute();
    // $nodes = $ids ? $this->nodeStroage->loadMultiple($ids) : [];

    // foreach ($nodes as $node) {
    //   switch ($node->isPublished()) {
    //     case TRUE:
    //       $availability = '✅';
    //       break;

    //     case FALSE:
    //     default:
    //       $availability = '🚫';
    //       break;
    //   }

    //   $label = [
    //     $node->getTitle(),
    //     '<small>(' . $node->id() . ')</small>',
    //     $availability,
    //   ];

    //   $results[] = [
    //     'value' => EntityAutocomplete::getEntityLabels([$node]),
    //     'label' => implode(' ', $label),
    //   ];
    // }

    array_push($results, "Yey");
    array_push($results, "Test");

    return new JsonResponse($results);
  }
}
