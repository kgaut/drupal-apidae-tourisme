<?php

namespace Drupal\apidae_tourisme\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Touristic object entities.
 */
class TouristicObjectViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
