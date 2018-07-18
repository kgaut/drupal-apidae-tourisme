<?php

namespace Drupal\apidae_tourisme;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Touristic object entity.
 *
 * @see \Drupal\apidae_tourisme\Entity\TouristicObject.
 */
class TouristicObjectAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\apidae_tourisme\Entity\TouristicObjectInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished touristic object entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published touristic object entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit touristic object entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete touristic object entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add touristic object entities');
  }

}
