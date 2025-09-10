<?php

namespace Drupal\wateraid_donation_landing_page\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Controller\NodeController;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\replicate\Replicator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Node routes.
 */
class NodeTemplateController extends NodeController {

  /**
   * The replicator service.
   */
  protected Replicator $replicator;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $repository
   *   The entity reposotory.
   * @param \Drupal\replicate\Replicator $replicator
   *   The replicator service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $repository, Replicator $replicator) {
    parent::__construct($date_formatter, $renderer, $repository);
    $this->replicator = $replicator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity_type.repository'),
      $container->get('replicate.replicator')
    );
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return mixed[]
   *   A node submission form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add(NodeTypeInterface $node_type): array {
    $node = $this->entityTypeManager()->getStorage('node')->create([
      'type' => $node_type->id(),
    ]);

    // Turn on the template flag.
    $node->get('field_wa_donation_template')->setValue(1);

    return $this->entityFormBuilder()->getForm($node, 'default', ['template' => TRUE]);
  }

  /**
   * Add the form template.
   *
   *  Provides the node from template submission form. Use the replication
   *  service to clone the given node to prepopulate the form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param \Drupal\node\Entity\Node $node
   *   The template node.
   *
   * @return mixed[]
   *   A node submission form.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function addFromTemplate(NodeTypeInterface $node_type, Node $node): array {
    // Replicate node without saving.
    $new_node = $this->replicator->cloneEntity($node);

    // Turn off the template flag & set the template reference field.
    $new_node->get('field_wa_donation_template')->setValue(0);
    $new_node->get('field_wa_donation_page_template')->setValue($node->id());
    $new_node->get('field_wa_admin_description')->setValue('');

    // Clear revision and moderation state.
    $new_node->get('moderation_state')->setValue('');
    $new_node->get('revision_log')->setValue('');

    return $this->entityFormBuilder()->getForm($new_node, 'default', ['from_template' => TRUE]);
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param \Drupal\node\Entity\Node $node
   *   The template node.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function addFromTemplatePageTitle(NodeTypeInterface $node_type, Node $node): TranslatableMarkup {
    return $this->t('Create @name from template @template',
      ['@name' => $node_type->label(), '@template' => $node->label()]);
  }

  /**
   * Page title callback for a node revision.
   *
   * @param int|\Drupal\node\NodeInterface $node_revision
   *   The node revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle(int|NodeInterface $node_revision): string {
    $node = (is_int($node_revision)) ? $this->entityTypeManager()->getStorage('node')->loadRevision($node_revision) : $node_revision;
    return $this->t('Revision of %title from %date',
      [
        '%title' => $node->label(),
        '%date' => $this->dateFormatter->format($node->getRevisionCreationTime()),
      ]
    );
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(NodeTypeInterface $node_type): string {
    return $this->t('Create @name template', ['@name' => $node_type->label()]);
  }

}
