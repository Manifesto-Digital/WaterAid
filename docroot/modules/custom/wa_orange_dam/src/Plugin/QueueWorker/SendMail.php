<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'wa_orange_dam_send_mail' queue worker.
 *
 * @QueueWorker(
 *   id = "wa_orange_dam_send_mail",
 *   title = @Translation("Send Mail"),
 *   cron = {"time" = 60},
 * )
 */
final class SendMail extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new SendMail instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly Connection $connection,
    private readonly MailManagerInterface $pluginManagerMail,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RendererInterface $renderer,
    private readonly LoggerChannel $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('logger.channel.wa_orange_dam'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (isset($data['uid']) && isset($data['type']) && $data['uid'] > 0) {

      $user_storage = $this->entityTypeManager->getStorage('user');
      $media_storage = $this->entityTypeManager->getStorage('media');
      $node_storage = $this->entityTypeManager->getStorage('node');

      /** @var \Drupal\user\UserInterface $user */
      if ($user = $user_storage->load($data['uid'])) {
        $params = [];

        if ($data['type'] == 'systems_admin') {
          $results = $this->connection->query('SELECT uid, mid, nid, FROM_UNIXTIME(expiry_date, :format) AS :label FROM wa_orange_dam', [
            ':format' => '%D %M %Y',
            ':label' => 'expiry',
          ])->fetchAll();
        }
        else {
          $results = $this->connection->query('SELECT uid, mid, nid, FROM_UNIXTIME(expiry_date, :format) AS :label FROM wa_orange_dam WHERE uid = :uid', [
            ':format' => '%D %M %Y',
            ':label' => 'expiry',
            ':uid' => $data['uid'],
          ])->fetchAll();
        }

        if ($results) {
          $render = [
            'intro' => [
              '#markup' => $this->t('<p>Hello :user,</p><p>A number of pages on the WaterAid website that you are responsible for are using Orange DAM images that are due to expire.</p><p>Below is a list of the items:</p>', [
                ':user' => $user->getDisplayName(),
              ]),
            ],
            'table' => [
              '#type' => 'table',
              '#header' => [$this->t('Owner'), $this->t('Media'), $this->t('Content'), $this->t('Expiry')],
              '#rows' => [],
            ],
          ];

          foreach ($results as $result) {
            /** @var \Drupal\user\UserInterface $owner */
            $owner = $user_storage->load($result->uid);

            /** @var \Drupal\media\MediaInterface $media */
            $media = $media_storage->load($result->mid);

            /** @var \Drupal\node\NodeInterface $node */
            $node = $node_storage->load($result->nid);

            if ($owner && $media && $node) {
              $render['table']['#rows'][] = [
                'owner' => $owner->toLink(NULL, NULL, ['absolute' => TRUE])->toString(),
                'media' => $media->toLink(NULL, 'edit-form', ['absolute' => TRUE])->toString(),
                'node' => $node->toLink(NULL, 'edit-form', ['absolute' => TRUE])->toString(),
                'expiry' => $this->t(':expiry', [
                  ':expiry' => $result->expiry,
                ]),
              ];
            }
          }

          $render['outro'] = [
            '#markup' => $this->t('<p>Please update the content to use new images as soon as possible.</p><p>Yours,<br />WaterAid</p>'),
          ];

          $params['message'] = $this->renderer->renderInIsolation($render);
          $params['subject'] = $this->t('Orange DAM media expiring soon.');

          if (!$this->pluginManagerMail->mail('wa_orange_dam', $data['type'], $user->getEmail(), $user->getPreferredLangcode(), $params)) {
            $this->logger->error($this->t('Error sending DAM expiry mail to :email (user :uid)', [
              ':email' => $user->getEmail(),
              ':uid' => $user->id(),
            ]));
          }
        }
      }
    }
  }
}
