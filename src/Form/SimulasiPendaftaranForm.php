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


/**
 * Form with examples on how to use cache.
 */
class SimulasiPendaftaranForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simulasi_pendaftaran_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
	
	// Delete all nodes.
	//entity_delete_multiple('pendaftaran', \Drupal::entityQuery('pendaftaran')->execute());

    // Delete all users except uid > 6.
	//entity_delete_multiple('user', \Drupal::entityQuery('user')->condition('uid', '6', '>')->condition('uid', '0', '!=')->execute());

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
	  // Initialize some variables during the first pass through.
	  $database = \Drupal::database();
	  if (!isset($sandbox['total'])) {
		$query = $database->select('data_akademik', 'd')
		  //->fields('d')
		  //->range('0','3')
		  ->range()
		  ->countQuery()
		  ->execute();
		$count = $query->fetchField();  
		$sandbox['total'] = $count;
	  }
	  $pendaftaran_per_batch = 25;
	  isset($sandbox['current']) ? $sandbox['current'] : '0';
	  // Handle one pass through.
      usleep(2000);
      $query = $database->select('data_akademik', 'd');
	  $query->fields('d');
	  //$query->condition('id', '1130' , '>=');
	  //$query->condition('id', '1135' , '<');
	  $query->range('0', '300');
	  //$query->range($sandbox['current'], $sandbox['current'] + $pendaftaran_per_batch);
	  $query->leftjoin('users_field_data', 'u', 'u.name = d.nisn');
	  $query->isNull('u.name');
	  $pids = $query->execute()->fetchAll();
	  foreach($pids as $key => $pid) {
		$data = (array)$pid;
        $data_umum = $this->createDataUmum();
		unset($data['id']);
		unset($data['uid']);
		$data = array_merge($data, $data_umum);
		$user = $this->createUser($data);
		$data['year'] = date('Y', REQUEST_TIME);
		$data['user_id'] = $user->id();
		$data['name'] = $user->getUsername();
		
		$akademik = $this->getSkorAkademik($data);
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
		
        $transaction = $database->startTransaction();
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
		
	    $this->messenger()->addMessage($this->t('@count pendaftaran processed.', array('@count' => isset($sandbox['current']) ? $sandbox['current']++ : '1')));

	    if ($sandbox['total'] == 0) {
		  $sandbox['#finished'] = 1;
	    } else {
		  $sandbox['#finished'] = (( isset($sandbox['current']) ? $sandbox['current']++ : '1') / $sandbox['total']);
	    }
	  }
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
		->condition('id', '10','=')
		->execute();

	$jenis_sekolah = JenisSekolah::load(array_rand($ids));	

    $data['jenis_sekolah'] = $jenis_sekolah->id();
    $data['nama_jenis_sekolah'] = $jenis_sekolah->label();
	
	return $data;
  }

  public function getPilihanSekolah($data){
    $ids = \Drupal::entityQuery('pilihan_sekolah')
		->condition('jenis_sekolah', $data['jenis_sekolah'] ,'=')
		//->condition('vilage', '3601030015' ,'=')
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
	$domisili = $this->getDomisili();    
	$data = array_merge($data, $domisili);

	$jenis_sekolah = $this->getJenisSekolah(); 
	$data = array_merge($data, $jenis_sekolah);
	
	$pilihan_sekolah = $this->getPilihanSekolah($data); 
	$data = array_merge($data, $pilihan_sekolah);

    $zonasi = $this->getZonasi($data);
	$data = array_merge($data, $zonasi);
	
	$prodi_sekolah = $this->getProdiSekolah($data);
	$data = array_merge($data, $prodi_sekolah);

	$prestasi = $this->getPrestasi($data);
	$data = array_merge($data, $prestasi);

	$sktm = $this->getSktm($data);
	$data = array_merge($data, $sktm);

	$jalur_pendaftaran = $this->getJalurPendaftaran($data);
	$data = array_merge($data, $jalur_pendaftaran);

    return $data;
	//$entries = array();
  }

}

