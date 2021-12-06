<?php

declare(strict_types=1);

namespace Drupal\music_search;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;

/**
 *
 */
class ServiceProvider extends ServiceProviderBase {

  /**
   *
   */
  public function register(ContainerBuilder $container) {
    $containerModules = $container->getParameter('container.modules');
    $finder = new Finder();

    $foldersWithServiceContainers = [];

    $foldersWithServiceContainers['Drupal\music_search\\'] = $finder->in(dirname($containerModules['music_search']['pathname']) . '/src/')->files()->name('*.php');

    $foldersWithServiceContainers['Drupal\music_search\Transformer\\'] = $finder->in(dirname($containerModules['music_search']['pathname']) . '/src/Transformer/')->files()->name('*.php');

    $foldersWithServiceContainers['Drupal\music_search\DomCrawler\\'] = $finder->in(dirname($containerModules['music_search']['pathname']) . '/src/DomCrawler/')->files()->name('*.php');
    $foldersWithServiceContainers['Drupal\music_search\MongoDBFetcher\\'] = $finder->in(dirname($containerModules['music_search']['pathname']) . '/src/MongoDBFetcher/')->files()->name('*.php');

    $foldersWithServiceContainers['Drupal\music_search\Importer\\'] = $finder->in(dirname($containerModules['music_search']['pathname']) . '/src/Importer/')->files()->name('*.php');
    $foldersWithServiceContainers['Drupal\music_search\Importer\MongoDB\\'] = $finder->in(dirname($containerModules['music_search']['pathname']) . '/src/Importer/MongoDB/')->files()->name('*.php');

    foreach ($foldersWithServiceContainers as $namespace => $files) {
      foreach ($files as $fileInfo) {
        // Remove .php extension from filename.
        $class = $namespace
          . substr($fileInfo->getFilename(), 0, -4);
        // don't override any existing service.
        if ($container->hasDefinition($class)) {
          continue;
        }
        $definition = new Definition($class);
        $definition->setAutowired(TRUE);
        $container->setDefinition($class, $definition);
      }
    }
  }

}
