<?php

namespace Drupal\gos_default_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generate, update & alter the default consumers for Games of Switzerland.
 */
class ConsumersSubscriber implements EventSubscriberInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConsumersSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DefaultContentEvents::IMPORT => [
        ['updateDefault', 1000],
      ],
    ];
  }

  /**
   * Alter default Consumers to allow all Images Styles.
   *
   * @param \Drupal\default_content\Event\ImportEvent $event
   *   The Import event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateDefault(ImportEvent $event): void {
    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    /** @var \Drupal\consumers\Entity\Consumer|null $consumer */
    $consumer = $this->entityTypeManager->getStorage('consumer')->load(1);

    if (!$consumer) {
      return;
    }

    // Add every image style to the consumer.
    $image_styles = [];

    foreach ($styles as $style) {
      $image_styles[] = ['target_id' => $style->id()];
    }
    $consumer->set('image_styles', $image_styles);
    $consumer->save();
  }

}
