<?php

namespace Drupal\apidae_tourisme\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Touristic object edit forms.
 *
 * @ingroup apidae_tourisme
 */
class TouristicObjectForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\apidae_tourisme\Entity\TouristicObject */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Touristic object.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Touristic object.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.touristic_object.canonical', ['touristic_object' => $entity->id()]);
  }

}
