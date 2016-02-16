<?php

/**
 * @file
 * Contains \Drupal\brilleappen\Plugin\Field\FieldFormatter\ContactPersonFormatter.
 */

namespace Drupal\brilleappen\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'brilleappen_contact_person' formatter.
 *
 * @FieldFormatter(
 *   id = "brilleappen_contact_person",
 *   module = "brilleappen_contact_person",
 *   label = @Translation("Contact person"),
 *   field_types = {
 *     "brilleappen_contact_person"
 *   }
 * )
 */
class ContactPersonFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#theme' => 'brilleappen_contact_person_formatter',
        '#name' => $item->name,
        '#email' => $item->email,
        '#telephone' => $item->telephone,
      );
    }

    return $element;
  }

}
