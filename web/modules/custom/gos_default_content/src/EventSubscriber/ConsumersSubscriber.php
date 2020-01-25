<?php

namespace Drupal\gos_default_content\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Constructs a new AgentsSubscriber object.
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
  public static function getSubscribedEvents() {
    $events[DefaultContentEvents::IMPORT][] = ['updateDefault', 1000];
    return $events;
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
  public function updateDefault(ImportEvent $event) {
    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $consumer = $this->entityTypeManager->getStorage('consumer')->load(1);

    // Add every image style to the consumer.
    foreach ($styles as $style) {
      $consumer->image_styles[] = ['target_id' => $style->id()];
    }
    $consumer->save();
  }

}
