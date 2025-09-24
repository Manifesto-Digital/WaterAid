<?php

declare(strict_types=1);

namespace Drupal\group_webform\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm form to remove an entity from a group.
 */
final class RemoveConfirmForm extends ConfirmFormBase {

  /**
   * The entity's group relationship.
   *
   * @var \Drupal\group\Entity\GroupRelationshipInterface|null
   *   The group relationship. Empty until the form is built.
   */
  protected GroupRelationshipInterface|null $relationship = NULL;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   *  Empty until the form is built.
   */
  protected EntityInterface|null $entity;

  /**
   * The group owning the relationship.
   *
   * @var \Drupal\group\Entity\GroupInterface|null
   *   Empty until the form is built.
   */
  protected GroupInterface|null $group;

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'group_webform_remove_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface|null $node = NULL, WebformInterface|null $webform = NULL): array {

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface $group_relationship_storage */
    $group_relationship_storage = $this->entityTypeManager->getStorage('group_relationship');

    // At this point we MUST either have a node or a webform.
    $entity = $node ?? $webform;
    $group_relationships = $group_relationship_storage->loadByEntity($entity);

    // The access check has already confirmed this entity has exactly one group
    // relationship, so all we need to do is store it.
    $this->relationship = reset($group_relationships);

    // Store the entity for future use.
    $this->entity = $entity;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $this->group = $this->relationship->getGroup();

    return $this->t('This content is in the @group site: are you sure you want to remove it?', [
      '@group' => $this->group->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('The content itself will not be deleted but you will require a site admin to put it back into a site.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->relationship->delete();

    $this->messenger()->addStatus($this->t('@title has been removed from the @group site.', [
      '@title' => $this->entity->label(),
      '@group' => $this->group->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
