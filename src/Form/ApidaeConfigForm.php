<?php

namespace Drupal\apidae_tourisme\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApidaeConfigForm.
 */
class ApidaeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'apidae_tourisme.apidaeconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apidae_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('apidae_tourisme.apidaeconfig');
    $form['auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Auth'),
    ];
    $form['auth']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
    ];
    $form['auth']['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('project_id'),
    ];
    $form['data'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data'),
    ];
    $form['data']['objects'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('objects to syncronize'),
      '#options' => [
        'ACTIVITE' => $this->t('Activité'),
        'COMMERCE_ET_SERVICE' => $this->t('Commerce et service'),
        'DEGUSTATION' => $this->t('Producteur'),
        'DOMAINE_SKIABLE' => $this->t('Domaine skiable'),
        'EQUIPEMENT' => $this->t('Equipement'),
        'FETE_ET_MANIFESTATION' => $this->t('Fête et manifestation'),
        'HEBERGEMENT_COLLECTIF' => $this->t('Hébergement collectif'),
        'HEBERGEMENT_LOCATIF' => $this->t('Hébergement locatif'),
        'HOTELLERIE' => $this->t('Hôtellerie'),
        'HOTELLERIE_PLEIN_AIR' => $this->t('Hôtellerie de plein air'),
        'PATRIMOINE_CULTUREL' => $this->t('Patrimoine culturel'),
        'PATRIMOINE_NATUREL' => $this->t('Patrimoine naturel'),
        'RESTAURATION' => $this->t('Restauration'),
        'SEJOUR_PACKAGE' => $this->t('Séjour packagé'),
        'TERRITOIRE' => $this->t('Territoire'),
      ],
      '#default_value' => $config->get('objects'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('apidae_tourisme.apidaeconfig')
      ->set('auth', $form_state->getValue('auth'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('data', $form_state->getValue('data'))
      ->set('objects', $form_state->getValue('objects'))
      ->save();
  }

}
