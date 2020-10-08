<?php

namespace Drupal\gos_elasticsearch\Plugin\Normalizer;

use Drupal\node\NodeInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * Normalizes / denormalizes Drupal Game nodes into an array structure for ES.
 */
class GameNormalizer extends ContentEntityNormalizer {

  /**
   * Supported formats.
   *
   * @var array
   */
  protected $format = ['elasticsearch_helper'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\node\NodeInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\node\Entity\Node $object */

    $data = [
      'uuid' => $object->get('uuid')->value,
      'is_published' => $object->isPublished(),
      'title' => $object->getTitle(),
      'desc' => !$object->get('body')->isEmpty() ? strip_tags($object->body->value) : NULL,
      'bundle' => $object->bundle(),
      'path' => $object->toUrl('canonical')->toString(),
      'changed' => $object->getChangedTime(),
    ];

    if (!$object->get('field_releases')->isEmpty()) {
      // Will contain every release by platform (sometimes more than once
      // the same year).
      $releases = [];

      // Will contain only the same year once for Histogram aggregations.
      $years = [];

      foreach ($object->field_releases as $release) {
        $releases[] = [
          'date' => $release->date_value ?? NULL,
          'platform_slug' => isset($release->entity) ? $release->entity->get('field_slug')->value : NULL,
          'state' => $release->state ?? NULL,
        ];

        if (!isset($release->date_value)) {
          continue;
        }

        // Use the year as key to prevent having twice the same value.
        $year = (new \DateTimeImmutable($release->date_value))->format('Y');
        $years[$year] = $year;
      }

      // Transform the single years array into a structure for ES storage.
      $data['releases_years'] = array_map(static function ($year) {
        return ['year' => $year];
      }, array_keys($years));

      $data['releases'] = $releases;
    }

    $people = [];

    // Handle people fullnames in games (freelancers).
    if (!$object->get('field_members')->isEmpty()) {
      foreach ($object->field_members as $member) {
        $people[] = [
          'path' => $member->entity->toUrl('canonical')->toString(),
          'fullname' => $member->entity->title->value,
          'uuid' => $member->entity->get('uuid')->value,
        ];
      }
    }

    // Handle studios names.
    if (!$object->get('field_studios')->isEmpty()) {
      $studios = [];

      foreach ($object->field_studios as $studio) {
        $studios[] = [
          'path' => $studio->entity->toUrl('canonical')->toString(),
          'name' => $studio->entity->title->value,
          'uuid' => $studio->entity->get('uuid')->value,
        ];

        // Handle people on studio fullnames.
        if (!$studio->entity->get('field_members')
          ->isEmpty()) {
          foreach ($object->field_members as $member) {
            $people[] = [
              'path' => $member->entity->toUrl('canonical')->toString(),
              'fullname' => $member->entity->title->value,
              'uuid' => $member->entity->get('uuid')->value,
            ];
          }
        }
      }

      $data['studios'] = $studios;
    }

    // People from Studio and Games.
    $data['people'] = $people;

    // Handle genres.
    if (!$object->get('field_genres')->isEmpty()) {
      $genres = [];

      foreach ($object->field_genres as $genre) {
        $genres[] = [
          'slug' => $genre->entity->get('field_slug')->value,
        ];
      }

      $data['genres'] = $genres;
    }

    // Handle stores.
    if (!$object->get('field_stores')->isEmpty()) {
      $genres = [];

      foreach ($object->field_stores as $store) {
        $genres[] = [
          'slug' => $store->store,
          'link' => $store->link,
        ];
      }

      $data['stores'] = $genres;
    }

    // Handle locations.
    if (!$object->get('field_locations')->isEmpty()) {
      $locations = [];

      foreach ($object->field_locations as $location) {
        $locations[] = [
          'slug' => $location->entity->get('field_slug')->value,
        ];
      }

      $data['locations'] = $locations;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if (!parent::supportsNormalization($data, $format)) {
      return FALSE;
    }

    if ($data instanceof NodeInterface && $data->getType() === 'game') {
      return TRUE;
    }

    return FALSE;
  }

}
