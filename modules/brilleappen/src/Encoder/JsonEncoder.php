<?php

/**
 * @file
 * Contains \Drupal\brilleappen\Encoder\JsonEncoder.
 */

namespace Drupal\brilleappen\Encoder;

use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class JsonEncoder extends \Drupal\serialization\Encoder\JsonEncoder {
  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = array()) {
    if (isset($data['type'], $data['type'][0], $data['type'][0]['target_id']) && $data['type'][0]['target_id'] == 'gg_event') {
      $eventUuid = $data['uuid'][0]['value'];
      $data['add_file_url'] = Url::fromRoute('brilleappen.add_file', [ 'event_id' => $eventUuid ], [ 'absolute' => TRUE ])->toString();
    }
    return parent::encode($data, $format, $context);
  }

}
