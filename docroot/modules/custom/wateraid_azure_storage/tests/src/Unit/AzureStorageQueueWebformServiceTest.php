<?php

namespace Drupal\Tests\wateraid_azure_storage\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\wateraid_azure_storage\AzureStorageQueueWebformService;
use Drupal\wateraid_azure_storage\AzureStorageQueueWebformServiceInterface;
use Drupal\wateraid_azure_storage\Exception\InvalidEnvironmentException;

/**
 * @coversDefaultClass \Drupal\wateraid_azure_storage\AzureStorageQueueWebformService
 * @group wateraid_azure_storage
 */
class AzureStorageQueueWebformServiceTest extends UnitTestCase {

  /**
   * Azure Storage Queue Webform service.
   */
  protected AzureStorageQueueWebformServiceInterface $azureStorageQueueWebformService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->azureStorageQueueWebformService = $this
      ->getMockBuilder(AzureStorageQueueWebformService::class)
      ->disableOriginalConstructor()
      ->setMethods(['getEnvMode'])
      ->getMock();
  }

  /**
   * Tests Queue Mode Identifier validation.
   *
   * @dataProvider providerQueueModeAndNames
   * @covers ::queueModeValidate
   */
  public function testQueueModeValidation($expected_error, $env_mode, $queue_name): void {
    // Mock queue mode extraction.
    $this->azureStorageQueueWebformService
      ->method('getEnvMode')
      ->willReturn($env_mode);

    // We're only testing failures.
    try {
      $this->azureStorageQueueWebformService->queueModeValidate($queue_name);
    }
    catch (InvalidEnvironmentException $e) {
      switch ($expected_error) {
        case 'live_error':
          $expected_result = 'You cannot use the "prod" or "live" queue mode identifier on a non-Production environment.';
          break;

        case 'test_error':
          $expected_result = 'You cannot use the "test" queue mode identifier on a Production environment.';
          break;

        default:
          $expected_result = NULL;
      }
      $this->assertSame($expected_result, $e->getMessage());
    }
  }

  /**
   * Data Provider for Queue Names.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerQueueModeAndNames(): array {
    return [
      ['live_error', 'test', 'prod'],
      ['live_error', 'test', 'prod-some-queue-name'],
      ['live_error', 'test', 'some-prod-queue-name'],
      ['live_error', 'test', 'some-queue-name-prod'],
      ['live_error', 'test', 'prod00441234567891'],
      ['live_error', 'test', '00441prod234567891'],
      ['live_error', 'test', '00441234567891prod'],
      ['live_error', 'test', 'live'],
      ['live_error', 'test', 'live-some-queue-name'],
      ['live_error', 'test', 'some-live-queue-name'],
      ['live_error', 'test', 'some-queue-name-live'],
      ['live_error', 'test', 'live00441234567891'],
      ['live_error', 'test', '00441live234567891'],
      ['live_error', 'test', '00441234567891live'],
      ['test_error', 'live', 'test'],
      ['test_error', 'live', 'test-some-queue-name'],
      ['test_error', 'live', 'some-test-queue-name'],
      ['test_error', 'live', 'some-queue-name-test'],
      ['test_error', 'live', 'test00441234567891'],
      ['test_error', 'live', '00441test234567891'],
      ['test_error', 'live', '00441234567891test'],
    ];
  }

}
