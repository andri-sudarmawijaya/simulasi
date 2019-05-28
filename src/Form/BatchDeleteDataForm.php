<?php

namespace Drupal\simulasi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BatchDeleteDataForm.
 */
class BatchDeleteDataForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_delete_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['from_id'] = [
      '#type' => 'number',
      '#title' => $this->t('From ID'),
      '#required' => TRUE,
      '#description' => $this->t('From ID number'),
      '#weight' => '0',
    ];
    $form['to_id'] = [
      '#type' => 'number',
      '#title' => $this->t('To ID'),
      '#required' => TRUE,
      '#description' => $this->t('To ID Number'),
      '#weight' => '1',
    ];
    $form['batch'] = [
      '#type' => 'radios',
      '#title' => 'Choose batch',
      '#required' => TRUE,
      '#options' => [
        'pendaftaran' => $this->t('Pendaftaran'),
        'user' => $this->t('User'),
      ],
      '#weight' => '2',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '10',
    ];

    return $form;
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
	 // Get all data to be processed.
	$var['from_id'] = $form_state->getValue('from_id');
	$var['to_id'] = $form_state->getValue('to_id');

    $type = $form_state->getValues()['batch'];
	$values = $form_state->getValues();

    // Set the batch, using convenience methods.
    $batch = [];
    switch ($type) {
      case 'pendaftaran':
        $batch = $this->deletePendaftaran($var);
        break;

      case 'user':
        $batch = $this->deleteUser($var);
        break;
    }
  }

    // Implement the operation method.
  public static function deletePendaftaran($var){
	$all_ids = \Drupal::entityQuery('pendaftaran')
						  ->condition('id', $var['from_id'], '>=')
						  ->condition('id', $var['to_id'], '<=')
						->execute();
	// Breakdown your process into small batches(operations).
	//      Delete 50 pendaftarans per batch.
	$operations = [];
	foreach (array_chunk($all_ids, 50) as $smaller_batch_data) {
		$operations[] = ['\Drupal\simulasi\Form\BatchDeleteDataForm::batchDeletePendaftaran'
										, [$smaller_batch_data]];
	}

	// Setup and define batch informations.
	$batch = array(
		'title' => t('Deleting pendaftarans in batch...'),
		'operations' => $operations,
		'finished' => '\Drupal\simulasi\Form\BatchDeleteDataForm::batchFinished',
	);
	batch_set($batch);
  }
  
  // Implement the operation method.
  public static function deleteUser($var){
	$all_ids = \Drupal::entityQuery('user')
						  ->condition('uid', $var['from_id'], '>=')
						  ->condition('uid', $var['to_id'], '<=')
						->execute();
	// Breakdown your process into small batches(operations).
	// Delete 50 users per batch.
	$operations = [];
	foreach (array_chunk($all_ids, 50) as $smaller_batch_data) {
		$operations[] = ['\Drupal\simulasi\Form\BatchDeleteDataForm::batchDeleteUser'
										, [$smaller_batch_data]];
	}

	// Setup and define batch informations.
	$batch = array(
		'title' => t('Deleting users in batch...'),
		'operations' => $operations,
		'finished' => '\Drupal\simulasi\Form\BatchDeleteDataForm::batchFinished',
	);
	batch_set($batch);
  }
    // Implement the operation method.
    public static function batchDeletePendaftaran($smaller_batch_data, &$context) {        
        $storage_handler = \Drupal::entityTypeManager()->getStorage('pendaftaran');
        $entities = $storage_handler->loadMultiple($smaller_batch_data);
        $storage_handler->delete($entities);
 
        // Display data while running batch.
        $batch_size=sizeof($smaller_batch_data);
        $batch_number=sizeof($context['results'])+1;
        $context['message'] = sprintf("Deleting %s entities per batch. Batch #%s"
                                            , $batch_size, $batch_number);
        $context['results'][] = sizeof($smaller_batch_data);
    }

    // Implement the operation method.
    public static function batchDeleteUser($smaller_batch_data, &$context) {        
        $storage_handler = \Drupal::entityTypeManager()->getStorage('user');
        $entities = $storage_handler->loadMultiple($smaller_batch_data);
        $storage_handler->delete($entities);
 
        // Display data while running batch.
        $batch_size=sizeof($smaller_batch_data);
        $batch_number=sizeof($context['results'])+1;
        $context['message'] = sprintf("Deleting %s user per batch. Batch #%s"
                                            , $batch_size, $batch_number);
        $context['results'][] = sizeof($smaller_batch_data);
    }

    // What to do after batch ran. Display success or error message.
    public static function batchFinished($success, $results, $operations) {
        if ($success)
            $message = count($results). ' batches processed.';
        else
            $message = 'Finished with an error.';
 
        drupal_set_message($message);
    }
}
