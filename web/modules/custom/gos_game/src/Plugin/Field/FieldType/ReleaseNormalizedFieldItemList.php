<?php

namespace Drupal\gos_game\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\gos_game\ReleasesCompiler;

/**
 * Represents the computed release values for an release entry.
 */
class ReleaseNormalizedFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress PropertyTypeCoercion
   * @psalm-suppress ArgumentTypeCoercion
   */
  protected function computeValue(): void {
    $entity = $this->getEntity();

    $normalized_releases = ReleasesCompiler::normalizeReleases($entity);
    $this->list = [];
    $offset = 0;

    foreach ($normalized_releases as $release) {
      $this->list[] = $this->createItem($offset, $release);
      ++$offset;
    }
  }

}
