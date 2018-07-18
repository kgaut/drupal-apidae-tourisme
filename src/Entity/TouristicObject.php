<?php

namespace Drupal\apidae_tourisme\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Touristic object entity.
 *
 * @ingroup apidae_tourisme
 *
 * @ContentEntityType(
 *   id = "touristic_object",
 *   label = @Translation("Touristic object"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\apidae_tourisme\Entity\TouristicObjectViewsData",
 *     "translation" = "Drupal\apidae_tourisme\TouristicObjectTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\apidae_tourisme\Form\TouristicObjectForm",
 *       "add" = "Drupal\apidae_tourisme\Form\TouristicObjectForm",
 *       "edit" = "Drupal\apidae_tourisme\Form\TouristicObjectForm",
 *       "delete" = "Drupal\apidae_tourisme\Form\TouristicObjectDeleteForm",
 *     },
 *     "access" = "Drupal\apidae_tourisme\TouristicObjectAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\apidae_tourisme\TouristicObjectHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "touristic_object",
 *   data_table = "touristic_object_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer touristic object entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/touristic_object/{touristic_object}",
 *     "add-form" = "/admin/content/touristic_object/add",
 *     "edit-form" = "/admin/content/touristic_object/{touristic_object}/edit",
 *     "delete-form" = "/admin/content/touristic_object/{touristic_object}/delete",
 *     "collection" = "/admin/content/touristic_object",
 *   },
 *   field_ui_base_route = "touristic_object.settings"
 * )
 */
class TouristicObject extends ContentEntityBase implements TouristicObjectInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'status' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Touristic object entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Touristic object is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
