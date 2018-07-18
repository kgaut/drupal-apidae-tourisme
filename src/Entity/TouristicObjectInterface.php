<?php

namespace Drupal\apidae_tourisme\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Touristic object entities.
 *
 * @ingroup apidae_tourisme
 */
interface TouristicObjectInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Touristic object name.
   *
   * @return string
   *   Name of the Touristic object.
   */
  public function getName();

  /**
   * Sets the Touristic object name.
   *
   * @param string $name
   *   The Touristic object name.
   *
   * @return \Drupal\apidae_tourisme\Entity\TouristicObjectInterface
   *   The called Touristic object entity.
   */
  public function setName($name);

  /**
   * Gets the Touristic object creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Touristic object.
   */
  public function getCreatedTime();

  /**
   * Sets the Touristic object creation timestamp.
   *
   * @param int $timestamp
   *   The Touristic object creation timestamp.
   *
   * @return \Drupal\apidae_tourisme\Entity\TouristicObjectInterface
   *   The called Touristic object entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Touristic object published status indicator.
   *
   * Unpublished Touristic object are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Touristic object is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Touristic object.
   *
   * @param bool $published
   *   TRUE to set this Touristic object to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\apidae_tourisme\Entity\TouristicObjectInterface
   *   The called Touristic object entity.
   */
  public function setPublished($published);

}
