<?php

namespace Drupal\apidae_tourisme;
use Drupal\apidae_tourisme\Entity\TouristicObject;
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

  protected $lastUpdate = 0;

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

    $this->lastUpdate = \Drupal::state()->get('apidae.last_sync', 0);
  }

  public function sync() {
    \Drupal::state()->set('apidae.last_sync', date('U'));
    foreach ($this->objects as $key => $objet) {
      $first = 0;
      $count = 20;
      $data['numFound'] = 1000;
      while($data['numFound'] > $first) {
        $data = $this->doQuery($key, $first, $count);
        foreach ($data['objetsTouristiques'] as $objetTouristique) {
          $this->parseOject($objetTouristique);
          unset($data['objetsTouristiques']);
        }
        $first += $data['query']['count'];
      }
    }
  }

  protected function doQuery($type, $first, $count) {
    $query = [
      'criteresQuery' => 'type:' . $type,
      'projetId'=> $this->apidaeProjectId,
      'apiKey'=> $this->apidaeApiKey,
      'territoireIds'=> [82490,178196],
      'first' => $first,
      'count' => $count,
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

    try {

      $response = $this->httpClient->get($url);
      $data = $response->getBody();
      return Json::decode($data);
    } catch (\Exception $e) {
      \Drupal::logger('apidae')->error(t('error @message<br />query : @query', [
        '@message' => $e->getMessage(),
        '@query' => print_r($query, TRUE),
      ]));
      return FALSE;
    }
  }

  protected function parseOject($object) {
    if($objet = TouristicObject::load($object['id'])) {

    }
    else {
      $objet = TouristicObject::create([
        'id' => $object['id'],
        'name' => $object['nom']['libelleFr'],
      ]);
    }
    $objet->save();
  }
}

