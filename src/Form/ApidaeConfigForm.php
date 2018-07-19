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
      'apidae_tourisme.config',
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
    $config = $this->config('apidae_tourisme.config');
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
      '#required' => TRUE,
    ];
    $form['auth']['selectionIds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID des sélections, séparés par une virgule'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('selectionIds'),
      '#required' => TRUE,
    ];
    $form['auth']['languages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code langues, séparés par une virgule'),
      '#description' => $this->t('Ex : fr,es,en'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('languages'),
      '#required' => TRUE,
    ];
    $form['auth']['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('project_id'),
      '#required' => TRUE,
    ];
    $form['data'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data'),
    ];
    $form['data']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable sync'),
      '#default_value' => $config->get('enabled'),
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

    $this->config('apidae_tourisme.config')
      ->set('auth', $form_state->getValue('auth'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('selectionIds', $form_state->getValue('selectionIds'))
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('languages', $form_state->getValue('languages'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();

    if(!$form_state->getValue('enabled')) {
      $this->messenger()->addMessage('Please note that sync is not enabled, therefore no content will be synced', 'warning');
    }

  }

}
