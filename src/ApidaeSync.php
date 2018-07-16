<?php

namespace Drupal\apidae_tourisme;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Sitra\ApiClient\Client;
use Sitra\ApiClient\Exception\SitraException;

/**
 * Class ApidaeSync.
 */
class ApidaeSync {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache backend
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  protected $apidaeProjectId;

  protected $apidaeApiKey;

  /** @var Client */
  protected $client;

  /**
   * Constructs a new ApidaeSync object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, CacheBackendInterface $cacheBackend) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cacheBackend;
    $config = $this->configFactory->get('apidae_tourisme.config')->get();

    $this->apidaeApiKey = $config['api_key'];
    $this->apidaeProjectId = $config['project_id'];

    $this->createClient();
  }

  private function createClient() {
    {
      try {
        $this->client = new Client([
          'apiKey' => $this->apidaeApiKey,
          'projectId' => $this->apidaeProjectId,
          'count' => 100,
        ]);
      } catch (\Exception $e) {
        \Drupal::logger('apidae')->error(t('there was an error with the connection, : @message', ['@message' => $e->getMessage()]));
      }
    }
  }

  public function test() {

  }

}
