<?php

namespace Drupal\simulasi;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Class PendaftaranController.
 *
 *  Returns responses for Pendaftaran routes.
 */

//class LoremController extends ControllerBase  {
class Lorem {

  public static function batchDelete($smaller_batch_data, &$context) {
    $entry = [
      'nisn' => rand(11111111111111, 99999999999999),
      'uid' => '1',
      'nama_lengkap' => 'Nama 1',
      'nama_ayah' => 'Aayah 1',
      'pekerjaan_ayah' => 'Aayah 1',
      'tempat_lahir' => 'tempat_lahir 1',
      'tgl_lahir' => makeRandomDateInclusive('1999-01-01', '2001-05-30'),
      'matematika' => mt_rand(300,990) / 10,
      'ipa' => mt_rand(400,990) / 10,
      'ips' => mt_rand(530,990) / 10,
      'english' => mt_rand(330,990) / 10,
      'indonesia' => mt_rand(430,990) / 10,
    ];

	$database = \Drupal::database();
	$transaction = $database->startTransaction();
	try {
	  $database->insert('data_akademik')->fields($entry)->execute();
	  //$this->messenger()->addMessage($this->t('Selamat data anda sudah disimpan.'));
	}
	catch (\Exception $e) {
	  $transaction->rollback();
	  watchdog_exception('simulasi', $e, $e->getMessage());
	  throw new \Exception(  $e->getMessage(), $e->getCode(), $e->getPrevious());
	}
  }
  
  
  /**
   * Batch 'finished' callback used by both batch 1 and batch 2.
   */
  public static function batchFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $messenger->addMessage(t('@count results processed.', ['@count' => count($results)]));
      $messenger->addMessage(t('The final result was "%final"', ['%final' => end($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }
  
   private static $lorem = [
        0 => 'lorem',
        1 => 'ipsum',
        2 => 'dolor',
        3 => 'sit',
        4 => 'amet',
        5 => 'consectetur',
        6 => 'adipiscing',
        7 => 'elit',
        8 => 'praesent',
        9 => 'interdum',
        10 => 'dictum',
        11 => 'mi',
        12 => 'non',
        13 => 'egestas',
        14 => 'nulla',
        15 => 'in',
        16 => 'lacus',
        17 => 'sed',
        18 => 'sapien',
        19 => 'placerat',
        20 => 'malesuada',
        21 => 'at',
        22 => 'erat',
        23 => 'etiam',
        24 => 'id',
        25 => 'velit',
        26 => 'finibus',
        27 => 'viverra',
        28 => 'maecenas',
        29 => 'mattis',
        30 => 'volutpat',
        31 => 'justo',
        32 => 'vitae',
        33 => 'vestibulum',
        34 => 'metus',
        35 => 'lobortis',
        36 => 'mauris',
        37 => 'luctus',
        38 => 'leo',
        39 => 'feugiat',
        40 => 'nibh',
        41 => 'tincidunt',
        42 => 'a',
        43 => 'integer',
        44 => 'facilisis',
        45 => 'lacinia',
        46 => 'ligula',
        47 => 'ac',
        48 => 'suspendisse',
        49 => 'eleifend',
        50 => 'nunc',
        51 => 'nec',
        52 => 'pulvinar',
        53 => 'quisque',
        54 => 'ut',
        55 => 'semper',
        56 => 'auctor',
        57 => 'tortor',
        58 => 'mollis',
        59 => 'est',
        60 => 'tempor',
        61 => 'scelerisque',
        62 => 'venenatis',
        63 => 'quis',
        64 => 'ultrices',
        65 => 'tellus',
        66 => 'nisi',
        67 => 'phasellus',
        68 => 'aliquam',
        69 => 'molestie',
        70 => 'purus',
        71 => 'convallis',
        72 => 'cursus',
        73 => 'ex',
        74 => 'massa',
        75 => 'fusce',
        76 => 'felis',
        77 => 'fringilla',
        78 => 'faucibus',
        79 => 'varius',
        80 => 'ante',
        81 => 'primis',
        82 => 'orci',
        83 => 'et',
        84 => 'posuere',
        85 => 'cubilia',
        86 => 'curae',
        87 => 'proin',
        88 => 'ultricies',
        89 => 'hendrerit',
        90 => 'ornare',
        91 => 'augue',
        92 => 'pharetra',
        93 => 'dapibus',
        94 => 'nullam',
        95 => 'sollicitudin',
        96 => 'euismod',
        97 => 'eget',
        98 => 'pretium',
        99 => 'vulputate',
        100 => 'urna',
        101 => 'arcu',
        102 => 'porttitor',
        103 => 'quam',
        104 => 'condimentum',
        105 => 'consequat',
        106 => 'tempus',
        107 => 'hac',
        108 => 'habitasse',
        109 => 'platea',
        110 => 'dictumst',
        111 => 'sagittis',
        112 => 'gravida',
        113 => 'eu',
        114 => 'commodo',
        115 => 'dui',
        116 => 'lectus',
        117 => 'vivamus',
        118 => 'libero',
        119 => 'vel',
        120 => 'maximus',
        121 => 'pellentesque',
        122 => 'efficitur',
        123 => 'class',
        124 => 'aptent',
        125 => 'taciti',
        126 => 'sociosqu',
        127 => 'ad',
        128 => 'litora',
        129 => 'torquent',
        130 => 'per',
        131 => 'conubia',
        132 => 'nostra',
        133 => 'inceptos',
        134 => 'himenaeos',
        135 => 'fermentum',
        136 => 'turpis',
        137 => 'donec',
        138 => 'magna',
        139 => 'porta',
        140 => 'enim',
        141 => 'curabitur',
        142 => 'odio',
        143 => 'rhoncus',
        144 => 'blandit',
        145 => 'potenti',
        146 => 'sodales',
        147 => 'accumsan',
        148 => 'congue',
        149 => 'neque',
        150 => 'duis',
        151 => 'bibendum',
        152 => 'laoreet',
        153 => 'elementum',
        154 => 'suscipit',
        155 => 'diam',
        156 => 'vehicula',
        157 => 'eros',
        158 => 'nam',
        159 => 'imperdiet',
        160 => 'sem',
        161 => 'ullamcorper',
        162 => 'dignissim',
        163 => 'risus',
        164 => 'aliquet',
        165 => 'habitant',
        166 => 'morbi',
        167 => 'tristique',
        168 => 'senectus',
        169 => 'netus',
        170 => 'fames',
        171 => 'nisl',
        172 => 'iaculis',
        173 => 'cras',
        174 => 'aenean',
    ];

    public static function ipsum($start, $count){
	  $nwords = random_int($start, $count);
	  $words = self::random_values(self::$lorem, $nwords);
	  $frags[] = implode(' ', $words);
    return reset($frags);
    }	
    private static function random_float() {
        return random_int(0, PHP_INT_MAX-1)/PHP_INT_MAX;
    }

    private static function random_values($arr, $count) {
        $keys = array_rand($arr, $count);
        if($count == 1) {
            $keys = [$keys];
        }
        return array_intersect_key($arr, array_fill_keys($keys, null));
    }
	
  }