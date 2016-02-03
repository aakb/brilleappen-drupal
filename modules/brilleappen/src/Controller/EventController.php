<?php
/**
 * @file
 * Contains \Drupal\brilleappen\Controller\FileController.
 */

namespace Drupal\brilleappen\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Query\Condition;

use Abraham\TwitterOAuth\TwitterOAuth;


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
      $file = file_save_data($data, 'public://' . uniqid('brilleappen_' . strftime('%Y%m%dT%H%M%S') . '_') . '.jpg', FILE_EXISTS_REPLACE);

      $node->field_gg_files->appendItem($file);
      $node->save();

      // @TODO Handle this in a queue with retries and stuff …
      $pushMessages = $this->push($file, $node);

      $this->sendResponse([ 'status' => 'OK', 'message' => 'File added to event "' . $node->getTitle() . '"', 'pushMessages' => $pushMessages ]);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage(), 'type' => get_class($ex) ], 400);
    }
  }

  private function push(File $file, Node $event) {
    $messages = [];

    $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());

    if ($event->field_gg_twitter_push->value) {
      try {
        $consumerKey = $event->field_gg_twitter_consumer_key->value;
        $consumerSecret = $event->field_gg_twitter_consumer_secret->value;
        $accessToken = $event->field_gg_twitter_access_token->value;
        $accessTokenSecret = $event->field_gg_twitter_access_secret->value;
        $caption = $event->field_gg_twitter_caption->value;

        $connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
        $content = $connection->get('account/verify_credentials');

        if (isset($content->errors)) {
          throw new \Exception($content->errors[0]->message);
        }

        $media = $connection->upload('media/upload', ['media' => $filepath ]);

        $statuses = $connection->post('statuses/update', [
          'status' => $caption,
          'media_ids' => $media->media_id_string,
        ]);

        $messages['twitter'] = 'OK';
      } catch (\Exception $ex) {
        $messages['twitter'] = $ex->getMessage();
      }
    }

    if ($event->field_gg_instagram_push->value) {
      try {
        $username = $event->field_gg_instagram_username->value;
        $password = $event->field_gg_instagram_password->value;
        $caption = $event->field_gg_instagram_caption->value;

        $igDataPath = file_directory_temp() . '/igdata/';
        file_prepare_directory($igDataPath, FILE_CREATE_DIRECTORY);
        $igDataPath .= '/';
        $instagram = new \Instagram($username, $password, FALSE, $igDataPath);

        $instagram->login();
        $instagram->uploadPhoto($filepath, $caption);
        $instagram->logout();
        $messages['instagram'] = 'OK';
      } catch (\Exception $ex) {
        $messages['instagram'] = $ex->getMessage();
      } finally {
        // Remove instagram cookies.
        $cookiesFilename = 'cookies.dat';
        if (file_exists($igDataPath . '/' . $cookiesFilename)) {
          unlink($igDataPath . '/' . $cookiesFilename);
        }
      }
    }

    return $messages;
  }

  private function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
  }
}
