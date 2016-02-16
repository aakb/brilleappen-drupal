<?php

/**
 * @file
 * Contains Drupal\brilleappen\Plugin\Field\FieldType\ContactPersonItem.
 */

namespace Drupal\brilleappen\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'brilleappen_contact_person' field type.
 *
 * @FieldType(
 *   id = "brilleappen_contact_person",
 *   label = @Translation("Contact person"),
 *   description = @Translation("This field stores data for a contact person."),
 *   default_widget = "brilleappen_contact_person",
 *   default_formatter = "brilleappen_contact_person"
 * )
 */
class ContactPersonItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'name' => array(
          'description' => 'Stores the name',
          'type' => 'varchar',
          'length' => '255',
          'not null' => TRUE,
        ),
        'email' => array(
          'description' => 'Stores the email',
          'type' => 'varchar',
          'length' => '255',
          'not null' => TRUE,
        ),
        'phone' => array(
          'description' => 'Stores the phone',
          'type' => 'varchar',
          'length' => '255',
          'not null' => TRUE,
        ),
      ),
      'indexes' => array(
        'name' => array('name'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['name'] = DataDefinition::create('string')
                        ->setLabel(t('Name'));

    $properties['email'] = DataDefinition::create('email')
                        ->setLabel(t('Email'));

    $properties['phone'] = DataDefinition::create('string')
                        ->setLabel(t('Phone'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $name = $this->get('name')->getValue();
    return $name === NULL || $name === '';
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->name = trim($this->name);
    $this->email = trim($this->email);
    $this->phone = trim($this->phone);
  }

}
