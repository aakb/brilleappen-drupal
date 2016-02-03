<?php
/**
 * @file
 * Contains \Drupal\brilleappen\Controller\FileController.
 */

namespace Drupal\brilleappen\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Query\Condition;

class FileController extends ControllerBase {
  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $event
   */
  public function main(Request $request, $event = '') {
    try {
      if ($request->getMethod() != 'POST') {
        throw new \Exception('Invalid request');
      }

      $query = \Drupal::entityQuery('node')
             ->condition('type', 'gg_event', '=')
             ->condition('uuid', $event, '=');

      $entityIds = $query->execute();
      if (count($entityIds) != 1) {
        throw new \Exception('No such event: ' . $event);
      }
      $entityId = array_shift($entityIds);
      $node = Node::load($entityId);

      if (!$node) {
        throw new \Exception('No such event: ' . $event);
      }

      // Get posted data and save it in a file.
      $data = $request->getContent();
      if (empty($data)) {
        throw new \Exception('Missing data');
      }
      $file = file_save_data($data, 'public://' . uniqid('brilleappen_', TRUE) . '.jpg', FILE_EXISTS_REPLACE);

      $node->field_gg_files->appendItem($file);
      $node->save();

      $this->sendResponse([ 'status' => 'OK', 'message' => 'File added to event "' . $node->getTitle() . '"' ]);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage() ], 400);
    }
  }

  private function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
  }
}
