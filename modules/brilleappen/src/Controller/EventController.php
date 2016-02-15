<?php
/**
 * @file
 * Contains \Drupal\brilleappen\Controller\FileController.
 */

namespace Drupal\brilleappen\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\Request;

use Abraham\TwitterOAuth\TwitterOAuth;


class EventController extends ControllerBase {
  public function createEvent(Request $request) {
    try {
      $content = $request->getContent();
      if (empty($content)) {
        throw new \Exception('Missing data');
      }
      $data = @json_decode($content);
      if (empty($data)) {
        throw new \Exception('Invalid data');
      }
      if (empty($data->title)) {
        throw new \Exception('Missing title');
      }

      $event = Node::create([
        'type' => 'gg_event',
        'title' => $data->title,
      ]);
      $event->save();

      $this->sendResponse([
        'status' => 'OK',
        'url' => $url = Url::fromRoute('entity.node.canonical', [ 'node' => $event->nid->value ], [ 'absolute' => true ])->toString(),
        'add_file_url' => $url = Url::fromRoute('brilleappen.add_file', [ 'event_id' => $event->uuid->value ], [ 'absolute' => true ])->toString(),
      ], 201);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage(), 'type' => get_class($ex) ], 400);
    }
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $event_id
   */
  public function addFile(Request $request, $event_id = '') {
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

      // Create a new Media node.
      $media = Node::create([
        'type' => 'gg_media',
        'title' => strftime('%Y-%m-%dT%H:%M:%S'),
        'field_gg_event' => $event->nid->value,
        'field_gg_file' => $file->fid->value,
      ]);
      $media->save();

      $shareMessages = ($request->get('share') == 'yes') ? $this->push($file, $event, $media) : NULL;

      $this->sendResponse([
        'status' => 'OK',
        'message' => 'Media added to event "' . $event->getTitle() . '"',
        'media_id' => $media->uuid->value,
        'notify_url' => Url::fromRoute('brilleappen.notify_file', [ 'event_id' => $event->uuid->value, 'media_id' => $media->uuid->value ], [ 'absolute' => TRUE ])->toString(),
        'shareMessages' => $shareMessages,
      ]);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage(), 'type' => get_class($ex) ], 400);
    }
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $event_id
   */
  public function notifyFile(Request $request, $event_id, $media_id) {
    try {
      $event = $this->getEvent($event_id);
      $media = $this->getMedia($media_id);
      $file = File::load($media->field_gg_file->target_id);

      if (!$file) {
        throw new \Exception('Cannot load file');
      }

      $notifyMessages = $this->push($file, $event, $media);

      $this->sendResponse([
        'status' => 'OK',
        'notifyMessages' => $notifyMessages,
      ]);
    } catch (\Exception $ex) {
      $this->sendResponse([ 'status' => 'ERROR', 'message' => $ex->getMessage(), 'type' => get_class($ex) ], 400);
    }
  }

  /**
   * Get an Event by uuid.
   *
   * @param string $eventId
   *   The event id.
   * @return \Drupal\node\Entity\Node The event.
   * The event.
   * @throws \Exception
   */
  private function getEvent($eventId) {
    $query = \Drupal::entityQuery('node')
           ->condition('type', 'gg_event', '=')
           ->condition('uuid', $eventId, '=');

    $entityIds = $query->execute();
    if (count($entityIds) != 1) {
      throw new \Exception('No such event: ' . $eventId);
    }
    $entityId = array_shift($entityIds);
    $event = Node::load($entityId);

    if (!$event) {
      throw new \Exception('No such event: ' . $eventId);
    }

    return $event;
  }

  /**
   * Get a Media by uuid.
   *
   * @param string $mediaId
   *   The media id.
   * @return \Drupal\node\Entity\Node The media.
   * The media.
   * @throws \Exception
   */
  private function getMedia($mediaId) {
    $query = \Drupal::entityQuery('node')
           ->condition('type', 'gg_media', '=')
           ->condition('uuid', $mediaId, '=');

    $entityIds = $query->execute();
    if (count($entityIds) != 1) {
      throw new \Exception('No such media: ' . $mediaId);
    }
    $entityId = array_shift($entityIds);
    $media = Node::load($entityId);

    if (!$media) {
      throw new \Exception('No such media: ' . $mediaId);
    }

    return $media;
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

    if ($event->field_gg_email_push->value) {
      try {
        $recipients = $event->field_gg_email_recipients->value;
        $langcode = 'en';
        $params = [
          'event' => $event,
          'media' => $media,
        ];

        $message = \Drupal::service('plugin.manager.mail')->mail('brilleappen', 'media_added', $recipients, $langcode, $params, TRUE);
        $messages['email'] = ($message['result'] === TRUE) ? 'OK' : 'NOT_SENT';
      } catch (\Exception $ex) {
        $messages['email'] = $ex->getMessage();
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
