<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Unit\FieldPaywallFieldItemUnitTest.
 */

namespace Drupal\field_paywall\Tests\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Form\FormState;
use Drupal\field\Tests\FieldUnitTestBase;
use Drupal\field_paywall\Plugin\Field\FieldType\PaywallItem;

/**
 * @coversDefaultClass \Drupal\field_paywall\Plugin\Field\FieldType\PaywallItem
 * @group Paywall
 */
class FieldPaywallFieldItemUnitTest extends FieldUnitTestBase {

  public static $modules = array('field_paywall');

  /**
   * The paywall field definition in use.
   *
   * @var \Drupal\field\Entity\FieldConfig;
   */
  protected $paywallFieldDefinition = NULL;

  /**
   * The paywall field storage config.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig;
   */
  protected $paywallFieldStorageConfig = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createPaywallField();
  }

  /**
   * @covers ::schema
   */
  public function testSchema() {
    $schema_output = PaywallItem::schema($this->paywallFieldStorageConfig);

    $this->assertTrue(!empty($schema_output['columns']), 'Schema present for Paywall');
    $this->assertTrue(!empty($schema_output['columns']['enabled']), 'Enabled column present for Paywall');

    $this->assertEqual('int', $schema_output['columns']['enabled']['type'], 'Enabled column is integer for Paywall');
    $this->assertEqual(1, $schema_output['columns']['enabled']['default'], 'Enabled column is enabled by default for Paywall');
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmpty() {
    $entity = $this->createTestEntity(TRUE);
    $field_base = $this->getFieldItemBaseFromEntity($entity);
    $is_empty = $field_base->isEmpty();

    $this->assertFalse($is_empty, 'Paywall is never empty');
  }

  /**
   * @covers ::propertyDefinitions
   */
  public function testPropertyDefinitions() {
    $field_storage_definition_interface = $this->paywallFieldDefinition->getFieldStorageDefinition();
    $property_definitions_output = PaywallItem::propertyDefinitions($field_storage_definition_interface);

    $enabled_definition = $property_definitions_output['enabled'];

    $this->assertTrue(isset($enabled_definition), 'Enabled definition found');
    $this->assertEqual('integer', $enabled_definition->getDataType(), 'Enabled definition type is set to integer');
    $this->assertEqual('Enabled', $enabled_definition->getlabel(), 'Enabled definition label is set to Enabled');
  }

  /**
   * @covers ::fieldSettingsForm
   */
  public function testFieldSettingsForm() {
    $entity = $this->createTestEntity(TRUE);
    $field_base = $this->getFieldItemBaseFromEntity($entity);
    $form_state = new FormState();
    $field_settings_form_output = $field_base->fieldSettingsForm(array(), $form_state);

    $this->assertEqual('fieldset', $field_settings_form_output['#type'], 'Settings form type is correct');
    $this->assertEqual('Paywall settings', $field_settings_form_output['#title'], 'Settings form title is correct');
    $this->assertTrue(!empty($field_settings_form_output['help']), 'Help markup found in settings form');
  }

  /**
   * Create the paywall field.
   */
  protected function createPaywallField() {
    $this->paywallFieldStorageConfig = entity_create('field_storage_config', array(
      'field_name' => 'field_paywall',
      'entity_type' => 'entity_test',
      'type' => 'paywall',
    ));
    $this->paywallFieldStorageConfig->save();

    $field_config = entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_paywall',
      'bundle' => 'entity_test',
    ));
    $field_config->save();

    $entity_manager = $this->container->get('entity.manager');
    $definitions = $entity_manager->getFieldDefinitions('entity_test', 'entity_test');

    $this->paywallFieldDefinition = $definitions['field_paywall'];
  }

  /**
   * Create a test entity with paywall.
   *
   * @param bool $paywall_enabled
   *   Whether or not the paywall should be enabled.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The test entity.
   */
  protected function createTestEntity($paywall_enabled = TRUE) {
    // Verify entity creation.
    $entity = entity_create('entity_test');

    $value = $paywall_enabled ? 1 : 0;
    $entity->field_paywall = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    return $entity;
  }

  /**
   * Retrieve the field item base from a given Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity to get the field item base from.
   *
   * @return \Drupal\field_paywall\Plugin\Field\FieldType\PaywallItem
   *   The paywall item base.
   */
  protected function getFieldItemBaseFromEntity(EntityInterface $entity) {
    $field_item_base = $entity->get('field_paywall')->first();

    return $field_item_base;
  }
}