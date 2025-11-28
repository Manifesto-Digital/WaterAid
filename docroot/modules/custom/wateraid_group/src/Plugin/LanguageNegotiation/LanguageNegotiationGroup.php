<?php

namespace Drupal\wateraid_group\Plugin\LanguageNegotiation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\GroupInterface;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying the content translation language.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationGroup::METHOD_ID,
  name: new TranslatableMarkup('Group language'),
  weight: -10,
  description: new TranslatableMarkup('Determines the content language from a parent Group.')
)]
class LanguageNegotiationGroup extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method ID.
   */
  const METHOD_ID = 'language-group';

  /**
   * Constructs a new LanguageNegotiationGroup instance.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    protected readonly PathValidatorInterface $pathValidator,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('path.validator'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL): string|null {

    // Is this page in a group or a group itself?
    if (!$group = $this->getGroup($request)) {
      return NULL;
    }

    /** @var \Drupal\group\Entity\GroupInterface $group */
    if (!$langcode = ($group->hasField('field_group_language')) ? $group->get('field_group_language')->getString() : NULL) {
      return NULL;
    }

    return array_key_exists($langcode, $this->languageManager->getLanguages()) ? $langcode : NULL;
  }

  /**
   * Helper to load the group from the URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The current request.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The Group, or NULL on error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getGroup(?Request $request = NULL): ?GroupInterface {
    $return = NULL;

    if ($request) {
      $path = $request->getPathInfo();

      if ($url= $this->pathValidator->getUrlIfValid($path)) {
        $route_parameters = $url->getRouteParameters();

        if (isset($route_parameters['group'])) {

          /** @var \Drupal\group\Entity\GroupInterface $return */
          $return = $this->entityTypeManager->getStorage('group')->load($route_parameters['group']);
        }
      }
    }

    return $return;
  }

}
