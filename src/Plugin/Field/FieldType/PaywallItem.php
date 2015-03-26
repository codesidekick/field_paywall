<?php
/**
 * @file
 * Contains \Drupal\field_paywall\Plugin\Field\FieldType\PaywallItem.
 */

namespace Drupal\field_paywall\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paywall' field type.
 *
 * @FieldType(
 *   id = "paywall",
 *   label = @Translation("Paywall"),
 *   description = @Translation("Hides fields when an entity is viewed and displays a custom message to the visitor."),
 *   default_widget = "paywall_widget",
 *   default_formatter = "paywall_formatter"
 * )
 */
class PaywallItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return array(
      'columns' => array(
        'enabled' => array(
          'description' => 'Boolean indicating whether the paywall should is enabled by default or not.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 1,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['enabled'] = DataDefinition::create('integer')
      ->setLabel(t('Enabled'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array(
      '#type' => 'fieldset',
      '#title' => t('Paywall settings'),
    );

    $element['help'] = array(
      '#type' => 'markup',
      '#prefix' => '<h2>' . t('User permissions') . '</h2>',
      '#markup' => '<p>' . t('By default all users will receive the paywall when enabled unless you change the user permissions for who can bypass the paywall.') . '</p>',
      '#weight' => 99,
    );

    return $element;
  }
}
