<?php

/**
 * @file
 * Contains \Drupal\cleantaxonomy\Form\CleanTaxonomyReplaceForm.
 */
namespace Drupal\cleantaxonomy\Form;

use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an CleanTaxonomyReplaceForm form.
 */
class CleanTaxonomyReplaceForm extends FormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'cleantaxonomy_replace';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tid = NULL) {
    $currentUrl = Url::fromRoute('<current>');
    $path = $currentUrl->getInternalPath();
    $argument = explode('/', $path);
    $id=$argument[2];
    $taxonomy_term_object = taxonomy_term_load($id);
    $taxonomy_term_name = $taxonomy_term_object->get('name')->value;
    drupal_set_message(t('It will replace the nodes attached to the previous taxonomy term.'), 'warning');
   
    $form['cleantaxonomy']['Term1'] = [
      '#type' => 'textfield',
      '#title' => t('Taxonomy Term'),
      '#default_value' =>$taxonomy_term_name,
      'type' => 'entity_reference',
      'settings' => [ 'target_type' => 'taxonomy_term',],
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['cleantaxonomy']['Term2'] = [
      '#type' => 'entity_autocomplete',
      '#title'=> t('New Taxonomy Term'),
      '#target_type' => 'taxonomy_term',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Replace Term'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $id2=$form_state->getValue('Term2');
    $taxonomy_term_vocabulary=\Drupal::database()->query("SELECT vid FROM {taxonomy_term_field_data} WHERE tid = '$id2'")->fetchField();
    if($taxonomy_term_vocabulary!='tags') {
      $form_state->setErrorByName('Term2', $this->t("It can only replace the nodes among the taxonomy terms with vocabulary as 'tags'."));
    }
  }
  
  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name1=$form_state->getValue('Term1');
    $tid1= \Drupal::database()->query("SELECT tid FROM {taxonomy_term_field_data} WHERE name = '$name1'")->fetchField();
    $tid2 = $form_state->getValue('Term2');
    \Drupal::database()->query("UPDATE {taxonomy_index} SET tid=$tid2 where tid=$tid1")->fetchField('tid');
    \Drupal::database()->query("UPDATE {node__field_tags} SET field_tags_target_id=$tid2 where field_tags_target_id=$tid1")->fetchField('field_tags_target_id');
    $query = \Drupal::database()->delete('taxonomy_index')
      ->condition('tid', '$tid1')
      ->execute();
    drupal_set_message(t('Replaced Successfully'));
  }
}