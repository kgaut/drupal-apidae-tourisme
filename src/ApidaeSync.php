<?php

namespace Drupal\apidae_tourisme;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\node\Entity\Node;
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

  private static $count = 20;

  private static $maxItemsPerBatch = 100;

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

  public function sync($forceUpdate = FALSE, $ids = [])  {
    \Drupal::state()->set('apidae.last_sync', date('U'));
    $first = 0;
    $data['numFound'] = 1000;
    $results = [
      'created' => 0,
      'updated' => 0,
      'error' => 0,
      'not_updated' => 0,
    ];
    while($data['numFound'] > $first && $first < self::$maxItemsPerBatch) {
      $data = $this->doQuery($first, self::$count, $ids);
      foreach ($data['objetsTouristiques'] as $objetTouristique) {
        $this->parseOject($objetTouristique, $results, $forceUpdate);
        unset($data['objetsTouristiques']);
      }
      $first += $data['query']['count'];
    }
    \Drupal::logger('apidae')->info(t('Sync over, @created created, @updated updated, @not_updated unchanged, @error errors, @num_results results', [
      '@created' => $results['created'],
      '@updated' => $results['updated'],
      '@error' => $results['error'],
      '@not_updated' => $results['not_updated'],
      '@num_results' => $data['numFound'],
    ]));
    if(\count($ids) > 0) {
      \Drupal::messenger()->addStatus(t('Sync over, @created created, @updated updated, @not_updated unchanged, @error errors, @num_results results', [
        '@created' => $results['created'],
        '@updated' => $results['updated'],
        '@error' => $results['error'],
        '@not_updated' => $results['not_updated'],
        '@num_results' => $data['numFound'],
      ]));
    }
    return TRUE;
  }

  protected function doQuery($first, $count, $ids = []) {
    $random = new Random();
    $query = [
      'projetId'=> $this->apidaeProjectId,
      'apiKey'=> $this->apidaeApiKey,
      'selectionIds'=> $this->selectionIds,
      'locales'=> $this->languages,
      'first' => $first,
      'count' => $count,
      'order' => 'RANDOM',
      'randomSeed' => $random->word('20'),
      'responseFields' => [
        'id',
        'nom',
        'localisation',
        'presentation',
        'descriptionTarif.tarifsEnClair',
        'informations.moyensCommunication',
        'illustrations',
        'gestion.dateModification',
      ],
    ];

    if (\count($ids) > 0) {
      $query['identifiants'] = $ids;
    }

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

  protected function parseOject($apidaeObject, array &$results, $forceUpdate = FALSE) {
    $modificationDate = \DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $apidaeObject['gestion']['dateModification']);
    $locales = array_diff($this->languages, ['fr']);
    if(!$objet = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'objet_touristique', 'field_id_ws' => $apidaeObject['id']])) {
      $objet = Node::create([
        'field_id_ws' => $apidaeObject['id'],
        'langcode' => 'fr',
        'default_langcode' => TRUE,
        'title' => $apidaeObject['nom']['libelleFr'],
        'type' => 'objet_touristique',
        'field_type' => $apidaeObject['type'],
        'field_description' => $this->getDescription($apidaeObject),
        'field_description_courte' =>  $this->getDescriptionCourte($apidaeObject),
        'field_phone' => $this->getPhoneFromObject($apidaeObject),
        'field_email' => $this->getMailFromObject($apidaeObject),
        'field_illustrations' => $this->getMedias($apidaeObject),
        'field_geolocation' => $this->getGeolocalisation($apidaeObject),
        'field_address' => $this->getAddress($apidaeObject),
        'promote' => 0,
      ]);
      $objet->save();
      foreach ($locales as $locale) {
        if(isset($apidaeObject['nom']['libelle' . \ucwords($locale)])) {
          $data = $objet->toArray();
          $data['title'] = $apidaeObject['nom']['libelle' . \ucwords($locale)];
          $data['default_langcode'] = FALSE;
          $data['field_description_courte'] = $this->getDescriptionCourte($apidaeObject, $locale);
          $data['field_description'] = $this->getDescription($apidaeObject, $locale);
          $objet->addTranslation($locale, $data);
        }
      }
      if($objet->save()) {
        $results['created']++;
      }
      else {
        $results['error']++;
      }
    }
    else {
      /** @var Node $objet */
      $objet = array_pop($objet);
      if(!$forceUpdate && $objet->getChangedTime() > $modificationDate->format('U')) {
        $results['not_updated']++;
        return;
      }
      $objet->set('title', $apidaeObject['nom']['libelleFr']);
      $objet->set('field_description_courte', $this->getDescriptionCourte($apidaeObject, 'fr'));
      $objet->set('field_description', $this->getDescription($apidaeObject, 'fr'));
      $objet->set('field_phone', $this->getPhoneFromObject($apidaeObject));
      $objet->set('field_illustrations', $this->getMedias($apidaeObject));
      $objet->set('field_geolocation', $this->getGeolocalisation($apidaeObject));
      $objet->set('field_email', $this->getMailFromObject($apidaeObject));
      $objet->set('field_website', $this->getWebsiteFromObject($apidaeObject));
      $objet->set('field_address', $this->getAddress($apidaeObject));
      $objet->set('promote', 0);
      $objet->save();
      foreach ($locales as $locale) {
        if(isset($apidaeObject['nom']['libelle' . \ucwords($locale)])) {
          if ($objet->hasTranslation($locale)) {
            $objet->removeTranslation($locale);
            $data = $objet->toArray();
            $data['title'][0]['value'] = $apidaeObject['nom']['libelle' . \ucwords($locale)];
            $data['field_description'][0]['value'] = $this->getDescription($apidaeObject, $locale);
            $data['field_description_courte'][0]['value'] = $this->getDescriptionCourte($apidaeObject, $locale);
            $objet->addTranslation($locale, $data);
          }
        }
      }
      if($objet->save()) {
        $results['updated']++;
      }
      else {
        $results['error']++;
      }
    }
  }

  private function getDescriptionCourte($object, $locale='fr') {
    if(isset($object['presentation']['descriptifCourt']['libelle' . \ucwords($locale)])) {
      return $object['presentation']['descriptifCourt']['libelle' . \ucwords($locale)];
    }
    return NULL;
  }

  private function getDescription($object, $locale='fr') {
    if(isset($object['presentation']['descriptifsThematises'][0]['description']['libelle' . \ucwords($locale)])) {
      return $object['presentation']['descriptifsThematises'][0]['description']['libelle' . \ucwords($locale)];
    }
    return NULL;
  }

  private function getPhoneFromObject($object, $locale='fr') {
    foreach ($object['informations']['moyensCommunication'] as $moyen) {
      if ($moyen['type']['id'] === 201 && isset($moyen['coordonnees'][$locale])) {
        return $moyen['coordonnees'][$locale];
      }
    }
    return NULL;
  }

  private function getMailFromObject($object, $locale='fr') {
    foreach ($object['informations']['moyensCommunication'] as $moyen) {
      if ($moyen['type']['id'] === 204 && isset($moyen['coordonnees'][$locale])) {
        return $moyen['coordonnees'][$locale];
      }
    }
    return NULL;
  }

  private function getWebsiteFromObject($object, $locale='fr') {
    foreach ($object['informations']['moyensCommunication'] as $moyen) {
      if ($moyen['type']['id'] === 205 && isset($moyen['coordonnees'][$locale])) {
        return $moyen['coordonnees'][$locale];
      }
    }
    return NULL;
  }

  private function getGeolocalisation($object) {
    if(isset($object['localisation']['geolocalisation']['geoJson']['coordinates'])) {
      return [
        'lat' => $object['localisation']['geolocalisation']['geoJson']['coordinates'][0],
        'lng' => $object['localisation']['geolocalisation']['geoJson']['coordinates'][1],
      ];
    }
    return NULL;
  }

  private function getAddress($object) {
    if(isset($object['localisation']['adresse'])) {
      return [
        'country_code' => 'FR',
        'address_line1' => isset($object['localisation']['adresse']['adresse1']) ? $object['localisation']['adresse']['adresse1'] : NULL,
        'address_line2' => isset($object['localisation']['adresse']['adresse2']) ? $object['localisation']['adresse']['adresse2'] : NULL,
        'locality' => $object['localisation']['adresse']['commune']['nom'],
        'postal_code' => $object['localisation']['adresse']['commune']['codePostal'],
      ];
    }
    return NULL;

  }

  private function getMedias($object) {
    $files = [];
    if(isset($object['illustrations']) && is_array($object['illustrations'])) {
      foreach ($object['illustrations'] as $illu) {
        $modificationDate = \DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $illu['traductionFichiers'][0]['lastModifiedDate']);
        $url = $illu['traductionFichiers'][0]['url'];
        $title = $illu['nom']['libelleFr'];
        $filename = basename($url);
        $folder = 'public://objets_touristiques/' . $modificationDate->format('Y-m') . '/';
        $destination = $folder . $filename;
        if (file_exists($destination) && $existingFiles = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $destination])) {
          $files[] = array_pop($existingFiles);
          continue;
        }
        if(!is_dir($folder)) {
          \Drupal::service('file_system')->mkdir($folder, NULL, TRUE);
        }
        if($data = file_get_contents($url)) {
          $file = file_save_data($data, $destination, FILE_EXISTS_REPLACE);
          $file->save();
          $files[] = [
            'target_id' => $file->id(),
            'alt' => $title,
            'title' => $title,
          ];
        }
        else {
          \Drupal::logger('apidae')->error(t('Problem getting @url file', [
            '@url' => $url,
          ]));
        }

      }
    }
    return $files;
  }

}


