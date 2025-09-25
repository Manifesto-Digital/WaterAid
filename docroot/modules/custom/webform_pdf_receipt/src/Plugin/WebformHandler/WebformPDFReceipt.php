<?php

namespace Drupal\webform_pdf_receipt\Plugin\WebformHandler;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\entity_print\PrintBuilderInterface;
use Drupal\file\Entity\File;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\Plugin\WebformHandler\WateraidScheduleEmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates a pdf receipt.
 *
 * @WebformHandler(
 *   id = "webform_pdf_receipt",
 *   label = @Translation("Webform PDF Receipt Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Generates a PDF receipt and email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformPDFReceipt extends WateraidScheduleEmailWebformHandler {

  /**
   * The print builder.
   */
  protected PrintBuilderInterface $printBuilder;

  /**
   * The entity print plugin manager.
   */
  protected EntityPrintPluginManagerInterface $entityPrintPluginManager;

  /**
   * The asset resolver service.
   */
  protected AssetResolverInterface $assetResolver;

  /**
   * The asset css collection renderer service.
   */
  protected AssetCollectionRendererInterface $assetCssCollectionRenderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setPrintBuilder($container->get('entity_print.print_builder'));
    $instance->setEntityPrintPluginManager($container->get('plugin.manager.entity_print.print_engine'));
    $instance->setAssetResolver($container->get('asset.resolver'));
    $instance->setAssetCssCollectionRenderer($container->get('asset.css.collection_renderer'));
    return $instance;
  }

  /**
   * Sets the print builder.
   *
   * @param \Drupal\entity_print\PrintBuilderInterface $print_builder
   *   The print builder.
   *
   * @return $this
   */
  public function setPrintBuilder(PrintBuilderInterface $print_builder): static {
    $this->printBuilder = $print_builder;
    return $this;
  }

  /**
   * Sets the entity print plugin manager.
   *
   * @param \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface $entity_print_plugin_manager
   *   The entity print plugin manager.
   *
   * @return $this
   */
  public function setEntityPrintPluginManager(EntityPrintPluginManagerInterface $entity_print_plugin_manager): static {
    $this->entityPrintPluginManager = $entity_print_plugin_manager;
    return $this;
  }

  /**
   * Sets the asset resolver.
   *
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   The asset resolver.
   *
   * @return $this
   */
  public function setAssetResolver(AssetResolverInterface $asset_resolver): static {
    $this->assetResolver = $asset_resolver;
    return $this;
  }

  /**
   * Sets the asset css collection renderer service.
   *
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $asset_collection_renderer
   *   The asset css collection renderer service.
   *
   * @return $this
   */
  public function setAssetCssCollectionRenderer(AssetCollectionRendererInterface $asset_collection_renderer): static {
    $this->assetCssCollectionRenderer = $asset_collection_renderer;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['attachments'] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $link = Link::createFromRoute(
      $this->t('third party settings'),
      'entity.webform.settings',
      ['webform' => $this->getWebform()->id()],
      ['attributes' => ['target' => '_blank']]
    );

    $form['help_text'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('PDF Contents'),
      '#markup' => $this->t('To edit the contents of the PDF, please visit the webforms @link.', ['@link' => $link->toString()]),
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();
    $form += parent::buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * Generate the pdf file, and return the file object.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Either the file object, or NULL if it failed to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function generatePdfFile(WebformSubmissionInterface $webform_submission): ?EntityInterface {
    $print_engine = $this->entityPrintPluginManager->createSelectedInstance('pdf');
    $webform = $webform_submission->getWebform();
    // Set up donation details summary in collapsable div.
    $pdf_content = $webform->getThirdPartySetting('webform_pdf_receipt', 'pdf_text')['value'];
    $format = $webform->getThirdPartySetting('webform_pdf_receipt', 'pdf_text')['format'];
    // Replace the receipt token with the receipt value.
    $pdf_content = str_replace('[webform_submission:receipt]', $webform_submission->get('receipt')->getValue()[0]['value'], $pdf_content);
    $build = [
      '#theme' => 'entity_print',
      '#content' => check_markup(\Drupal::token()->replace($pdf_content, ['webform_submission' => $webform_submission]), $format),
    ];
    $build['#attached']['library'][] = 'entity_print/default';

    $assets = AttachedAssets::createFromRenderArray($build);
    $css_assets = $this->assetResolver->getCssAssets($assets, FALSE);
    $build['#entity_print_css'] = $this->assetCssCollectionRenderer->render($css_assets);
    if ($uri = $this->printBuilder->savePrintFromRenderable($build, $print_engine, $this->getFileName($webform_submission), 'private')) {
      $file = File::create([
        'uri' => $uri,
        'uid' => $webform_submission->getOwnerId(),
      ]);
      $file->setPermanent();
      $file->save();
      return $file;
    }

    return NULL;
  }

  /**
   * Generate a unique filename.
   *
   * @todo Allow for "nicer" filenames to be set from the admin interface.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission object.
   *
   * @return string
   *   The unique filename.
   */
  public function getFileName(WebformSubmissionInterface $webform_submission): string {
    $directory = 'private://webform_pdf_receipts';
    if (\Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
      return 'webform_pdf_receipts/' . uniqid('receipt_') . '.pdf';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    $data = $webform_submission->getData();

    if ($this->configuration['payment_frequency'] && $this->configuration['payment_frequency'] != $data[DonationConstants::DONATION_PREFIX . 'frequency']) {
      return parent::sendMessage($webform_submission, $message);
    }
    // There is no need to hit postSave loop.
    // We can use resave instead of save.
    $receipt = $webform_submission->get('receipt')->getValue();
    if (empty($receipt)) {
      $connection = Database::getConnection();
      $result = $connection->insert('webform_pdf_receipt')
        ->fields([
          'sid' => $webform_submission->id(),
        ])->execute();

      $webform_submission->set('receipt', $result);
      $webform_submission->resave();
    }

    if (!$webform_submission->get('file_id')->getValue()) {
      \Drupal::logger('webform_pdf_receipt')->notice('start pdf generate for sid @sid at time @time',
        ['@time' => date("d-m-Y h:i:s"), '@sid' => $webform_submission->id()]);
      $file = $this->generatePdfFile($webform_submission);
      \Drupal::logger('webform_pdf_receipt')->notice('end pdf generate for sid @sid at time @time',
        ['@time' => date("d-m-Y h:i:s"), '@sid' => $webform_submission->id()]);
      $webform_submission->set('file_id', $file->id());
      $webform_submission->resave();
    }

    $file = File::load($webform_submission->get('file_id')->getValue()[0]['value']);

    // Update file usage table.
    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'webform_pdf_receipt', 'webform_submission', $webform_submission->id());

    // Update the message now that the receipt is added.
    $message = $this->getMessage($webform_submission);

    return parent::sendMessage($webform_submission, $message);
  }

}
