<?php

namespace Drupal\apidae_tourisme;
use Drupal\Component\Serialization\Json;
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

  protected $objects;

  /** @var Client */
  protected $client;
  private static $url = "http://api.apidae-tourisme.com/api/v002/recherche/list-objets-touristiques";

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
    $this->objects = $config['objects'];
  }

  public function sync() {
    foreach ($this->objects as $key => $objet) {
      dd($objet);
      try {
        $query = [
          'criteresQuery' => 'type:' . $key,
          'projetId'=> $this->apidaeProjectId,
          'apiKey'=> $this->apidaeApiKey,
          'responseFields' => [
            'id',
            'nom',
            'illustrations',
            'multimedias',
            'informations',
            'presentation',
            'localisation',
            '@informationsObjetTouristique',
            'ouverture.periodeEnClair',
            'ouverture.periodesOuvertures',
            'descriptionTarif.tarifsEnClair.LibelleFr',
            'contacts',
          ],
        ];

        $url = self::$url . '?query=' . Json::encode($query);
        $response = $this->httpClient->get($url);
        $data = $response->getBody();
        $data = Json::decode($data);
      }
      catch (\Exception $e) {
        \Drupal::logger('apidae')->error(t('error @message<br />query : @query', [
          '@message' => $e->getMessage(),
          '@query' => print_r($query, TRUE),
        ]));
      }
    }
  }

}
