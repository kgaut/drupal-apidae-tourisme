<?php

namespace Drupal\apidae_tourisme;
use Drupal\apidae_tourisme\Entity\TouristicObject;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

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

  protected $languages;

  protected $selectionIds;

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
    $this->languages = explode(',', $config['languages']);
    $this->selectionIds = explode(',', $config['selectionIds']);

    $this->lastUpdate = \Drupal::state()->get('apidae.last_sync', 0);

  }

  public function sync() {
    \Drupal::state()->set('apidae.last_sync', date('U'));
    $first = 0;
    $count = 20;
    $data['numFound'] = 1000;
    $results = [
      'created' => 0,
      'updated' => 0,
      'error' => 0,
    ];
    while($data['numFound'] > $first && $first < 5) {
      $data = $this->doQuery($first, $count);
      foreach ($data['objetsTouristiques'] as $objetTouristique) {
        $this->parseOject($objetTouristique, $results);
        unset($data['objetsTouristiques']);
      }
      $first += $data['query']['count'];
    }

    \Drupal::logger('apidae')->info(t('Apidae sync over, @created objects created, @updated objects updated, @error errors', [
      '@created' => $results['created'],
      '@updated' => $results['updated'],
      '@error' => $results['error'],
    ]));
  }

  protected function doQuery($first, $count) {
    $query = [
      'projetId'=> $this->apidaeProjectId,
      'apiKey'=> $this->apidaeApiKey,
      'selectionIds'=> $this->selectionIds,
      'locales'=> $this->languages,
      'first' => $first,
      'count' => $count,
      'responseFields' => [
        'id',
        'nom',
        'presentation',
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

  protected function parseOject($object, array &$results) {
    $locales = array_diff($this->languages, ['fr']);
    if(!$objet = TouristicObject::load($object['id'])) {
      $objet = TouristicObject::create([
        'id' => $object['id'],
        'status' => 1,
        'langcode' => 'fr',
        'default_langcode' => FALSE,
        'name' => $object['nom']['libelleFr'],
        'type' => $object['type'],
        'descriptif' => $object['presentation']['descriptifCourt']['libelleFr'],
      ]);
      $objet->save();
      foreach ($locales as $locale) {
        $data = $objet->toArray();
        $data['name'] = $object['nom']['libelle' . \ucwords($locale)];
        $data['status'] = TRUE;
        $data['descriptif'] = $object['presentation']['descriptifCourt']['libelle' . \ucwords($locale)];
        $objet->addTranslation($locale, $data);
      }
      if($objet->save()) {
        $results['created']++;
      }
      else {
        $results['error']++;
      }
    }
    else {
      $objet = $objet->getTranslation('fr');
      $objet->setName($object['nom']['libelleFr']);
      $objet->save();
      foreach ($locales as $locale) {
        if(!$objet->hasTranslation($locale)) {
          $data = $objet->toArray();
          $data['name'] = $object['nom']['libelle' . \ucwords($locale)];
          $data['status'] = TRUE;
          $data['descriptif'] = $object['presentation']['descriptifCourt']['libelle' . \ucwords($locale)];
          $objet->addTranslation($locale, $data);
        }
        else {
          $translated = $objet->getTranslation($locale);
          $translated->setName($object['nom']['libelle' . \ucwords($locale)]);
        }
      }
      if($objet->save()) {
        $results['created']++;
      }
      else {
        $results['error']++;
      }
    }
  }

}

