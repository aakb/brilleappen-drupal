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
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Query\Condition;

use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\Validator\Constraints\False;


class EventController extends ControllerBase {
  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $event_id
   */
  public function file(Request $request, $event_id = '') {
    try {
      if ($request->getMethod() != 'POST') {
        throw new \Exception('Invalid request');
      }

      $query = \Drupal::entityQuery('node')
             ->condition('type', 'gg_event', '=')
             ->condition('uuid', $event_id, '=');

      $entityIds = $query->execute();
      if (count($entityIds) != 1) {
        throw new \Exception('No such event: ' . $event_id);
      }
      $entityId = array_shift($entityIds);
      $event = Node::load($entityId);

      if (!$event) {
        throw new \Exception('No such event: ' . $event_id);
      }

      // Get posted data and save it in a file.
      $data = $request->getContent();
      if (empty($data)) {
        throw new \Exception('Missing data');
      }
      $filename = uniqid('brilleappen_' . strftime('%Y%m%dT%H%M%S') . '_') . '.' . ExtensionGuesser::getInstance()->guess($request->get('type'));
      $file = file_save_data($data, 'public://' . $filename, FILE_EXISTS_REPLACE);

      $event->field_gg_files->appendItem($file);
      $event->save();

      // Create a new Media node.
      $media = Node::create([
        'type' => 'gg_media',
        'title' => strftime('%Y-%m-%dT%H:%M:%S'),
        'field_gg_event' => $event->nid->value,
        'field_gg_file' => $file->fid->value,
      ]);
      $media->save();

      $shareMessages = ($request->get('share') == 'yes') ? $this->push($file, $event, $media) : NULL;

      $this->sendResponse([ 'status' => 'OK', 'message' => 'Media added to event "' . $event->getTitle() . '"', 'shareMessages' => $shareMessages ]);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage(), 'type' => get_class($ex) ], 400);
    }
  }

  private function push(File $file, Node $event, Node $media) {
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

        $upload = $connection->upload('media/upload', ['media' => $filepath ]);

        $statuses = $connection->post('statuses/update', [
          'status' => $caption,
          'media_ids' => $upload->media_id_string,
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

    $this->addMediaData($media, [ 'push_messages' => $messages ]);

    return $messages;
  }

  private function addMediaData(Node $media, array $data, $save = TRUE) {
    $value = @json_decode($media->field_gg_data->value);
    if (!is_array($value)) {
      $value = [];
    }
    $value += $data;
    $media->field_gg_data = json_encode($value);
    if ($save) {
      $media->save();
    }
  }

  private function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
  }
}
