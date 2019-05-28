<?php

namespace Drupal\simulasi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\pendaftaran\Entity\Pendaftaran;
use Drupal\wilayah_indonesia_vilage\Entity\Vilage;
use Drupal\wilayah_indonesia_district\Entity\District;
use Drupal\wilayah_indonesia_regency\Entity\Regency;
use Drupal\wilayah_indonesia_province\Entity\Province;
use Drupal\jenis_sekolah\Entity\JenisSekolah;
use Drupal\pilihan_sekolah\Entity\PilihanSekolah;
use Drupal\zonasi\Entity\Zonasi;
use Drupal\prodi_sekolah\Entity\ProdiSekolah;
use Drupal\jalur_pendaftaran\Entity\JalurPendaftaran;
use Drupal\sktm\Entity\Sktm;
use Drupal\jalur_prestasi\Entity\JalurPrestasi;
use Drupal\penyelenggara\Entity\Penyelenggara;
use Drupal\tingkat\Entity\Tingkat;
use Drupal\juara\Entity\Juara;
use Drupal\simulasi\Lorem;
use Drupal\user\Entity\User;
/**
 * Class BatchGenerateDataForm.
 */
class BatchGenerateDataForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_generate_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['num_operation'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of operation'),
      '#description' => $this->t('Number of operation data will be created'),
      '#default_value' => '10',
      '#weight' => '0',
    ];
    $form['batch'] = [
      '#type' => 'radios',
      '#title' => 'Choose batch',
      '#options' => [
        'pendaftaran' => $this->t('Pendaftaran'),
        'data_akademik' => $this->t('Data Akademik'),
      ],
      '#weight' => '1',
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
	$var['num_operation'] = $form_state->getValue('num_operation');
    $var['type'] = $form_state->getValues()['batch'];
	$values = $form_state->getValues();

    // Set the batch, using convenience methods.
    $batch = [];
    switch ($var['type']) {
      case 'pendaftaran':
	    // Handle one pass through.
        usleep(2000);
	    $pendaftaran_per_batch = 2;
	    $database = \Drupal::database();
        $query = $database->select('data_akademik', 'd');
	    $query->fields('d');
	    //$query->range($sandbox['current'], $sandbox['current'] + $pendaftaran_per_batch);
	    $query->range('0', $var['num_operation']);
	    $query->leftjoin('users_field_data', 'u', 'u.name = d.nisn');
	    $query->isNull('u.name');
	    $pids = $query->execute()->fetchAll();
	  
	    // Breakdown your process into small batches(operations).
	    //      Generate 50 pendaftarans per batch.
	    $operations = [];
	    foreach (array_chunk($pids, $pendaftaran_per_batch) as $smaller_batch_data) {
		  $operations[] = ['\Drupal\simulasi\Form\BatchGenerateDataForm::batchGenerate'
										, [$smaller_batch_data, $var]];
	    }
	    // Setup and define batch informations.
	    $batch = array(
		  'title' => t('Generating pendaftaran entities in batch...'),
		  'operations' => $operations,
		  'finished' => '\Drupal\simulasi\Form\BatchGenerateDataForm::batchFinished',
	    );
	    batch_set($batch);
      break;

      case 'user':
        $batch = \Drupal\simulasi\Form\BatchGenerateDataForm::batchGenerateUser($var);
      break;
    }
  }
  
  // Implement the operation method.
  public static function batchGenerateUser($var){
	$all_ids = \Drupal::entityQuery('user')
						  ->condition('uid', $var['from_id'], '>=')
						  ->condition('uid', $var['to_id'], '<=')
						->execute();
	// Breakdown your process into small batches(operations).
	//      Generate 50 users per batch.
	$operations = [];
	foreach (array_chunk($all_ids, 50) as $smaller_batch_data) {
		$operations[] = ['\Drupal\simulasi\Form\BatchGenerateDataForm::batchGenerate'
										, [$smaller_batch_data]];
	}

	// Setup and define batch informations.
	$batch = array(
		'title' => t('Deleting users in batch...'),
		'operations' => $operations,
		'finished' => '\Drupal\simulasi\Form\BatchGenerateDataForm::batchFinished',
	);
	batch_set($batch);
  }
  
    public function batchGenerate($smaller_batch_data, $var, &$context) {
	  //$data = (array)$smaller_batch_data;
	  dpm($context);
	  foreach($smaller_batch_data as $data){
		$data = (array)$data;
		
		$data_umum = \Drupal\simulasi\Form\BatchGenerateDataForm::createDataUmum();
		unset($data['id']);
		unset($data['uid']);
		$data = array_merge($data, $data_umum);
		$user = \Drupal\simulasi\Form\BatchGenerateDataForm::createUser($data);
		$data['year'] = date('Y', REQUEST_TIME);
		$data['user_id'] = $user->id();
		$data['name'] = $user->getUsername();
		
		$akademik =\Drupal\simulasi\Form\BatchGenerateDataForm::getSkorAkademik($data);
		$data = array_merge($data, $akademik);

		if($data['jalur_pendaftaran'] == '10'){
	      $data['skor_total'] = isset($data['skor_prestasi']) ? $data['skor_prestasi'] : '0'; 
		  $data['skor_total'] += isset($data['skor_sktm']) ? $data['skor_sktm'] : '0' ;
		  $data['skor_total'] += isset($data['skor_zonasi']) ? $data['skor_zonasi'] : '0' ;
		  $data['skor_total'] += isset($data['skor_akademik']) ? $data['skor_akademik'] : '0' ;
		}
		elseif($data['jalur_pendaftaran'] == '20'){
	      $data['skor_total'] = isset($data['skor_zonasi']) ? $data['skor_zonasi'] : '0';
	      $data['skor_total'] += isset($data['skor_akademik']) ? $data['skor_akademik'] : '0';
		}
		elseif($data['jalur_pendaftaran'] == '30'){
	      $data['skor_total'] = isset($data['skor_prestasi']) ? $data['skor_prestasi'] : '0';
	      $data['skor_total'] += isset($data['skor_zonasi']) ? $data['skor_zonasi'] : '0';
	      $data['skor_total'] += isset($data['skor_akademik']) ? $data['skor_akademik'] :'0';		
		}
		elseif($data['jalur_pendaftaran'] == '40'){
	      $data['skor_total'] = isset($data['skor_sktm']) ? $data['skor_sktm'] : '0';
	      $data['skor_total'] += isset($data['skor_zonasi']) ? $data['skor_zonasi'] : '0';
	      $data['skor_total'] += isset($data['skor_akademik']) ? $data['skor_akademik'] : '0';
		}
		$database = \Drupal::Database();
        $transaction = $database->startTransaction();
		//dpm($data);
        try {
		  $pendaftaran = Pendaftaran::create($data);
		  $pendaftaran->save();
        }
        catch (\Exception $e) {
          $transaction->rollback();
          $pendaftaran = NULL;
          watchdog_exception('simulasi', $e, $e->getMessage());
          throw new \Exception(  $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
		$sandbox['current'] = '1';
	    //$this->messenger()->addMessage($this->t('@count pendaftaran processed.', array('@count' => isset($sandbox['current']) ? $sandbox['current']++ : '1')));
	    drupal_set_message(t('@count pendaftaran processed.', array('@count' => $sandbox['current'] ? $sandbox['current']++ : '1')));

		dpm($sandbox['current']);
		
	    if ($sandbox['total'] == 0) {
		  $sandbox['#finished'] = 1;
	    } else {
		  $sandbox['#finished'] = (( isset($sandbox['current']) ? $sandbox['current']++ : '1') / $sandbox['total']);
	    }
	  }
	}
 
  // What to do after batch ran. Display success or error message.
  public static function batchFinished($success, $results, $operations) {
    if ($success)
      $message = count($results). ' batches processed.';
    else
      $message = 'Finished with an error.';
 
    drupal_set_message($message);
  }
  
  public function getSkorAkademik($data){
    $keys = ['matematika', 'ipa', 'ips', 'english', 'indonesia'];
	$data['skor_akademik'] = 0;
	foreach($keys as $key){
		$database = \Drupal::database();
		$score = $database->select('skor_akademik', 's')
		  ->fields('s', array('score'))
		  ->condition('machine', $key, '=')
		  ->execute()->fetchField();		
	 $data['skor_akademik'] += $data[$key] * $score;
	}
	return $data;
  }
  
  public function getDomisili(){
    $ids = \Drupal::entityQuery('vilage')
		->execute();
	$vilage = Vilage::load(array_rand($ids));	

    $data['desa'] = $vilage->id();
    $data['nama_desa'] = $vilage->label();
	  
	$district = $vilage->district_id->entity;
    $data['kecamatan'] = $district->id();
    $data['nama_kecamatan'] = $district->label();
	
	$regency = $district->regency_id->entity;
    $data['kabupaten'] = $regency->id();
    $data['nama_kabupaten'] = $regency->label();
    
	/*
	 * beri opsi untuk luat provinsi
	 */
	$data['provinsi'] = '36';
    $data['nama_provinsi'] = 'Banten';

	return $data;
  }
  
  public function getJenisSekolah(){
    $ids = \Drupal::entityQuery('jenis_sekolah')
		//->condition('id', '10','=')
		->execute();

	$jenis_sekolah = JenisSekolah::load(array_rand($ids));	

    $data['jenis_sekolah'] = $jenis_sekolah->id();
    $data['nama_jenis_sekolah'] = $jenis_sekolah->label();
	
	return $data;
  }

  public function getPilihanSekolah($data){
    $ids = \Drupal::entityQuery('pilihan_sekolah')
		->condition('jenis_sekolah', $data['jenis_sekolah'] ,'=')
		->condition('vilage', $data['vilage'] ,'=')
		->execute();

	$pilihan_sekolah = PilihanSekolah::load(array_rand($ids));	

    $data['pilihan_sekolah'] = $pilihan_sekolah->id();
    $data['nama_pilihan_sekolah'] = $pilihan_sekolah->label();
	
	$desa_sekolah = $pilihan_sekolah->vilage->entity;
	$zona_sekolah = $pilihan_sekolah->zona->entity;
	
	$data['desa_sekolah'] = $desa_sekolah->id();
	$data['kecamatan_sekolah'] = $desa_sekolah->district_id->target_id;
	$data['kabupaten_sekolah'] = $desa_sekolah->district_id->entity->regency_id->target_id;
	$data['provinsi_sekolah'] = $desa_sekolah->district_id->entity->regency_id->entity->province_id->target_id;
	$data['zona_sekolah'] = $zona_sekolah->id();
	$data['nama_zona_sekolah'] = $zona_sekolah->label();
	
	return $data;
  }
  public function getZonasi($data){
    if($data['provinsi'] == $data['provinsi_sekolah']){
	  $machine = 'satu_provinsi';
	  if($data['kabupaten'] == $data['kabupaten_sekolah']){
		$machine = 'satu_kabupaten';
		if($data['kecamatan'] == $data['kecamatan_sekolah']){
	      $machine = 'satu_kecamatan';
	      if($data['desa'] == $data['desa_sekolah']){
	        $machine = 'satu_desa';
	      }
		}
	  }
	}
	else{
	  $machine = 'luar_provinsi';
	}
	$id = \Drupal::entityQuery('zonasi')
	  ->condition('machine_name', $machine,'=')
	  ->condition('jenis_sekolah', $data['jenis_sekolah'],'=')
	  ->execute();
	  
	$zonasi = Zonasi::load(array_rand($id));
	
    $data['zonasi'] = $zonasi->id();
	$data['nama_zonasi'] = $zonasi->label();
	$data['skor_zonasi'] = $zonasi->score->value;
  	return $data;
  }
  
  public function getProdiSekolah($data){
	$ids = \Drupal::entityQuery('prodi_sekolah')
	  ->condition('pilihan_sekolah_id', $data['pilihan_sekolah'],'=')
	  ->execute();
	
	$prodi_sekolah = ProdiSekolah::load(array_rand($ids));
    $data['prodi_sekolah'] = $prodi_sekolah->id();
	$data['nama_prodi_sekolah'] = $prodi_sekolah->label();
	$data['kompetensi_keahlian_id'] = $prodi_sekolah->kompetensi_keahlian_id->entity->id();
	$data['nama_jurusan'] = $prodi_sekolah->kompetensi_keahlian_id->entity->label();
    return $data;
  }

  
  public function getSktm($data){
	$ids = \Drupal::entityQuery('sktm')
	  //->condition('id', '30', '=')
      ->execute();
    
	$sktm = Sktm::load(array_rand($ids));
	$data['jalur_sktm'] = $sktm->id();
    if($sktm->id() != '10' && $data['provinsi'] =='36'){
	  $data['nama_jalur_sktm'] = $sktm->label();
	  $data['skor_sktm'] = $sktm->score->value;
	}

    return $data;
  }
  
  public function getPrestasi($data){
	$ids = \Drupal::entityQuery('jalur_prestasi')
	  //->condition('id', '20', '=')
      ->execute();
    
	$jalur_prestasi = JalurPrestasi::load(array_rand($ids));
    $data['jalur_prestasi'] = $jalur_prestasi->id();
    if($jalur_prestasi->id() != '10' && $data['provinsi'] =='36'){
      $data['nama_jalur_prestasi'] = $jalur_prestasi->label();
      $data['skor_prestasi'] = FALSE;
	  
	  $ids = \Drupal::entityQuery('penyelenggara')
	  ->execute();
	  $penyelenggara = Penyelenggara::load(array_rand($ids));
    
      $data['penyelenggara'] = $penyelenggara->id();
      $data['nama_penyelenggara'] = $penyelenggara->label();
      $data['skor_penyelenggara'] = $penyelenggara->score->value;
    
      $ids = \Drupal::entityQuery('tingkat')
	  ->execute();
	  $tingkat = Tingkat::load(array_rand($ids));
    	
	  $data['tingkat'] = $tingkat->id();
      $data['nama_tingkat'] = $tingkat->label();
      $data['skor_tingkat'] = $tingkat->score->value;
      
      $ids = \Drupal::entityQuery('juara')
	  ->execute();
	  $juara = Juara::load(array_rand($ids));

	  $data['juara'] = $juara->id();
      $data['nama_juara'] = $juara->label();
      $data['skor_juara'] = $juara->score->value;
      $data['prestasi'] = Lorem::ipsum('2', '3');
	  $data['skor_prestasi'] = $data['skor_penyelenggara'] + $data['skor_tingkat'] + $data['skor_juara'];
	}

    return $data;
  }
  
  public function getJalurPendaftaran($data){
   /*
   10	Tidak Mengajukan SKTM
   20	Pemegang SKTM dari Kepala Desa / Kelurahan
   30	Pemegang Kartu Indonesia Pintar

   10	Tidak Mengikuti Jalur Prestasi
   20	Mengikuti Jalur Prestasi

   10	Jalur Terpadu
   20	Jalur Umum
   30	Jalur Prestasi
   40	Jalur SKTM
   */
    if($data['jalur_sktm'] == '10'){
		if($data['jalur_prestasi'] == '10'){
		  $code_pendaftaran = '20';
		}
		elseif($data['jalur_prestasi'] == '20'){
		  $code_pendaftaran = '30';
		}
	}
    elseif($data['jalur_sktm'] == '20'){
		if($data['jalur_prestasi'] == '10'){
		  $code_pendaftaran = '40';
		}
		elseif($data['jalur_prestasi'] == '20'){
		  $code_pendaftaran = '10';
		}
	}
    elseif($data['jalur_sktm'] == '30'){
		if($data['jalur_prestasi'] == '10'){
		  $code_pendaftaran = '40';
		}
		elseif($data['jalur_prestasi'] == '20'){
		  $code_pendaftaran = '10';
		}
	}
	$query = \Drupal::entityQuery('jalur_pendaftaran')
  	        ->condition('id', $code_pendaftaran, '=');

    $ids = $query->execute();

	$jalur_pendaftaran = JalurPendaftaran::load(array_rand($ids));
	
	$value['jalur_pendaftaran'] = $jalur_pendaftaran->id();
	$value['nama_jalur_pendaftaran'] = $jalur_pendaftaran->label();
    
    return $value;
  }
  
  public function createUser($data) { 
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user = \Drupal\user\Entity\User::create();
    //dpm($data);
    //Mandatory settings
    $user->setPassword($data['nisn']);
    $user->enforceIsNew();
    $user->setEmail($data['nisn'] . '@local');
    $user->setUsername($data['nisn']); //This username must be unique and accept only a-Z,0-9, - _ @ .

    //Optional settings
    $user->set("init", $data['nisn'] . '@local');
    $user->set("langcode", $language);
    $user->set("preferred_langcode", $language);
    $user->set("preferred_admin_langcode", $language);
    $user->addRole('siswa');
	
      //$user->set("setting_name", 'setting_value');
    $user->activate();

    //Save user
	$database = \Drupal::database();
    $transaction = $database->startTransaction();
    try {
	  $res = $user->save();
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception('simulasi', $e, $e->getMessage());
      throw new \Exception(  $e->getMessage(), $e->getCode(), $e->getPrevious());
    }
    return $user;
  }
  
  public function createDataUmum(){
    $data = [];	
	$domisili = \Drupal\simulasi\Form\BatchGenerateDataForm::getDomisili();    
	$data = array_merge($data, $domisili);

	$jenis_sekolah = \Drupal\simulasi\Form\BatchGenerateDataForm::getJenisSekolah(); 
	$data = array_merge($data, $jenis_sekolah);
	
	$pilihan_sekolah = \Drupal\simulasi\Form\BatchGenerateDataForm::getPilihanSekolah($data); 
	$data = array_merge($data, $pilihan_sekolah);

    $zonasi = \Drupal\simulasi\Form\BatchGenerateDataForm::getZonasi($data);
	$data = array_merge($data, $zonasi);
	
	$prodi_sekolah = \Drupal\simulasi\Form\BatchGenerateDataForm::getProdiSekolah($data);
	$data = array_merge($data, $prodi_sekolah);

	$prestasi = \Drupal\simulasi\Form\BatchGenerateDataForm::getPrestasi($data);
	$data = array_merge($data, $prestasi);

	$sktm = \Drupal\simulasi\Form\BatchGenerateDataForm::getSktm($data);
	$data = array_merge($data, $sktm);

	$jalur_pendaftaran = \Drupal\simulasi\Form\BatchGenerateDataForm::getJalurPendaftaran($data);
	$data = array_merge($data, $jalur_pendaftaran);

    return $data;
	//$entries = array();
  }
  
  //------------------------
}
