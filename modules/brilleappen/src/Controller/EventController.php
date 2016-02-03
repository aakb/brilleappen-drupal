<?php
/**
 * @file
 * Contains \Drupal\brilleappen\Controller\FileController.
 */

namespace Drupal\brilleappen\Controller;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Query\Condition;

require_once __DIR__ . '/../../vendor/autoload.php';

class EventController extends ControllerBase {
  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $event
   */
  public function file(Request $request, $event = '') {
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

      $this->push($file, $node);

      $this->sendResponse([ 'status' => 'OK', 'message' => 'File added to event "' . $node->getTitle() . '"' ]);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage(), 'type' => get_class($ex) ], 400);
    }
  }

  private function push(File $file, Node $event) {
    if ($event->field_gg_instagram_push->value) {
      $username = $event->get('field_gg_instagram_username')->value;
      $password = $event->get('field_gg_instagram_password')->value;
      $caption = $event->get('field_gg_instagram_caption')->value;
      $path = \Drupal::service('file_system')->realpath($file->getFileUri());

      $igDataPath = file_directory_temp() . '/igdata/';
      file_prepare_directory($igDataPath, FILE_CREATE_DIRECTORY);
      $igDataPath .= '/';
      $instagram = new \Instagram($username, $password, FALSE, $igDataPath);
      try {
        $instagram->login();
        $instagram->uploadPhoto($path, $caption);
        $instagram->logout();
      } finally {
        // Remove instagram cookies.
        $cookiesFilename = 'cookies.dat';
        if (file_exists($igDataPath . '/' . $cookiesFilename)) {
          unlink($igDataPath . '/' . $cookiesFilename);
        }
      }
    }
  }

  private function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
  }
}
