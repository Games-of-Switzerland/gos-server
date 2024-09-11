<?php

namespace Drupal\gos_elasticsearch\Plugin\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\gos_elasticsearch\Plugin\Normalizer\Traits\NormalizerImagesDerivativesTrait;
use Drupal\node\NodeInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * Normalizes / denormalizes Drupal Game nodes into an array structure for ES.
 */
class GameNormalizer extends ContentEntityNormalizer {

  use NormalizerImagesDerivativesTrait;

  /**
   * Supported formats.
   *
   * @var string|string[]
   */
  protected $format = ['elasticsearch_helper'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string|array
   */
  protected $supportedInterfaceOrClass = ['Drupal\node\NodeInterface'];

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($entity_type_manager, $entity_type_repository, $entity_field_manager);
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress ParamNameMismatch
   */
  public function normalize($object, $format = NULL, array $context = []): null|array|\ArrayObject|bool|float|int|string {
    /** @var \Drupal\node\Entity\Node $object */

    // Will collect a list of image derivatives URLs, name, width & height.
    $medias = [];

    if ($object->hasField('field_images') && !$object->get('field_images')->isEmpty()) {
      $medias = array_merge($medias, $this->generateImagesDerivatives($object->get('field_images'), $this->imageStyles));
    }

    $data = [
      'uuid' => $object->get('uuid')->value,
      'medias' => $medias,
      'is_published' => $object->isPublished(),
      'title' => $object->getTitle(),
      'desc' => !$object->get('body')->isEmpty() ? strip_tags($object->body->value) : NULL,
      'bundle' => $object->bundle(),
      'path' => $object->toUrl('canonical')->toString(),
      'changed' => $object->getChangedTime(),
    ];

    // Handle number of players.
    $data['players'] = ['min' => NULL, 'max' => NULL];

    if (!$object->get('field_player')->isEmpty()) {
      $data['players']['min'] = (int) $object->get('field_player')->min;
      $data['players']['max'] = (int) $object->get('field_player')->maxax;
    }

    if (!$object->get('field_releases')->isEmpty()) {
      // Will contain every release by platform (sometimes more than once
      // the same year).
      $releases = [];

      // Will contain only the same year once for Histogram aggregations.
      $years = [];

      // Will contain only the same state once for State facets aggregations.
      $states = [];

      foreach ($object->field_releases as $release) {
        $releases[] = [
          'date' => ($release->date_value !== NULL && $release->date_value !== '') ? $release->date_value : NULL,
          'platform_name' => isset($release->entity) ? $release->entity->get('name')->value : NULL,
          'platform_slug' => isset($release->entity) ? $release->entity->get('field_slug')->value : NULL,
          'state' => $release->state ?? NULL,
        ];

        // Use the state as key to prevent having twice the same value.
        if (isset($release->state) && !empty($release->state)) {
          $states[$release->state] = $release->state;
        }

        if (!isset($release->date_value) || empty($release->date_value)) {
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

      // Transform the single states array into a structure for ES storage.
      $data['releases_states'] = array_map(static function ($state) {
        return ['state' => $state];
      }, array_keys($states));

      $data['releases'] = $releases;
    }

    $people = [];

    // Handle people fullnames in games (freelancers).
    if (!$object->get('field_members')->isEmpty()) {
      foreach ($object->field_members as $member) {
        if (!$member->entity) {
          continue;
        }

        $people[] = [
          'path' => $member->entity?->toUrl('canonical')->toString(),
          'fullname' => $member->entity?->title->value,
          'uuid' => $member->entity?->get('uuid')->value,
        ];
      }
    }

    // Handle studios names.
    if (!$object->get('field_studios')->isEmpty()) {
      $studios = [];

      foreach ($object->field_studios as $studio) {
        $studios[] = [
          'path' => $studio->entity?->toUrl('canonical')->toString(),
          'name' => $studio->entity?->title->value,
          'uuid' => $studio->entity?->get('uuid')->value,
        ];

        // Handle people on studio fullnames.
        if (!$studio->entity?->get('field_members')
          ->isEmpty()) {
          foreach ($object->field_members as $member) {
            $people[] = [
              'path' => $member->entity?->toUrl('canonical')->toString(),
              'fullname' => $member->entity?->title->value,
              'uuid' => $member->entity?->get('uuid')->value,
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
          'name' => $genre->entity?->get('name')->value,
          'slug' => $genre->entity?->get('field_slug')->value,
        ];
      }

      $data['genres'] = $genres;
    }

    // Handle stores.
    if (!$object->get('field_stores')->isEmpty()) {
      $stores = [];

      foreach ($object->field_stores as $store) {
        $stores[] = [
          'slug' => $store->store,
          'link' => $store->link,
        ];
      }

      $data['stores'] = $stores;
    }

    // Handle locations.
    if (!$object->get('field_locations')->isEmpty()) {
      $locations = [];

      foreach ($object->field_locations as $location) {
        $locations[] = [
          'name' => $location->entity?->get('name')->value,
          'slug' => $location->entity?->get('field_slug')->value,
        ];
      }

      $data['locations'] = $locations;
    }

    // Handle cantons.
    if (!$object->get('field_cantons')->isEmpty()) {
      $cantons = [];

      foreach ($object->field_cantons as $canton) {
        $cantons[] = [
          'name' => $canton->entity?->get('name')->value,
          'slug' => $canton->entity?->get('field_slug')->value,
        ];
      }

      $data['cantons'] = $cantons;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL, array $context = []): bool {
    if (!parent::supportsNormalization($data, $format, $context)) {
      return FALSE;
    }

    if ($data instanceof NodeInterface && $data->getType() === 'game') {
      return TRUE;
    }

    return FALSE;
  }

}
