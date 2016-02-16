<?php

/**
 * @file
 * Contains \Drupal\brilleappen\Plugin\Field\FieldWidget\ContactPersonWidget.
 */

namespace Drupal\brilleappen\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'brilleappen_contact_person' widget.
 *
 * @FieldWidget(
 *   id = "brilleappen_contact_person",
 *   label = @Translation("Contact person"),
 *   field_types = {
 *     "brilleappen_contact_person"
 *   }
 * )
 */
class ContactPersonWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->name)) ? $items[$delta]->name : NULL,
      '#maxlength' => 255,
      '#description' => $this->t('Name'),
    );

    $element['email'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->email)) ? $items[$delta]->email : NULL,
      '#maxlength' => 255,
      '#description' => $this->t('Email'),
    );

    $element['telephone'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Telephone'),
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->telephone)) ? $items[$delta]->telephone : NULL,
      '#maxlength' => 255,
      '#description' => $this->t('Telephone'),
    );

    return $element;
  }

}
