<?php

namespace Drupal\wateraid_tmgmt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Service description.
 */
class TranslationReports {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a TranslationReports object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Generates the media translation report.
   *
   * @param int $limit
   *   Maximum number of nodes to check per iteration.
   * @param int $offset
   *   Offset used in conjunction with the limit.
   *
   * @return mixed[]
   *   An array of untranslated media entity IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function mediaTranslationReport(int $limit = 0, int $offset = 0): array {
    $media_refs = [];
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Get all published nodes.
    $node_query = $node_storage->getQuery()
      ->condition('status', 1);

    if ($limit !== 0) {
      $node_query->range($offset, $limit);
    }

    $nids = $node_query->execute();

    foreach ($nids as $nid) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $node_storage->load($nid);

      // Check media items are translated into the same languages as the node.
      $languages = $node->getTranslationLanguages();
      $media_refs = $this->getUntranslatedMediaEntity($node, $languages, $media_refs);
    }

    return $media_refs;
  }

  /**
   * Recursively check for untranslated media.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to interrogate.
   * @param mixed[] $required_languages
   *   Languages the item should be translated into (using langcode as the key).
   * @param mixed[] $media_refs
   *   The array of media item IDs identified as requiring translation so far.
   *
   * @return mixed[]
   *   IDs of media entities not translated into all required languages.
   */
  public function getUntranslatedMediaEntity(ContentEntityInterface $entity, array $required_languages = [], array &$media_refs = []): array {

    $referenced_entities = $entity->referencedEntities();
    foreach ($referenced_entities as $referenced_entity) {
      if ($referenced_entity instanceof MediaInterface) {
        // Check the media is translated into all the required languages.
        $media_translations = $referenced_entity->getTranslationLanguages();

        $diff = array_diff_key($required_languages, $media_translations);
        if (count($diff) > 0) {

          // When using hook_tmgmt_source_suggestions(), We need additional
          // information from the referenced entity.
          $media_refs[$referenced_entity->id()] = [
            'id' => $referenced_entity->id(),
            'entityTypeId' => $referenced_entity->getEntityTypeId(),
            'entityLabel' => $referenced_entity->label(),
          ];
        }
      }
      elseif ($referenced_entity instanceof ParagraphInterface) {
        // Recursively check for media within the paragraph fields.
        $this->getUntranslatedMediaEntity($referenced_entity, $required_languages, $media_refs);
      }
    }

    return $media_refs;
  }

}
