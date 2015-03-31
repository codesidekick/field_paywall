<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Unit\FieldPaywallFieldItemUnitTest.
 */

namespace Drupal\field_paywall\Tests\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * @coversDefaultClass \Drupal\field_paywall\Plugin\Field\FieldType\PaywallWidget
 * @group Paywall
 */
class FieldPaywallFieldWidgetUnitTest extends FieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('field_paywall');

  /**
   * The paywall field definition in use.
   *
   * @var \Drupal\field\Entity\FieldConfig;
   */
  protected $paywallFieldDefinition = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createPaywallField();
  }

  /**
   * @covers ::formElement
   */
  public function testFormElement() {
    $entity = $this->createTestEntity(TRUE);
    $paywallWidget = $this->getFieldWidgetFromEntity($entity);

    $items = $entity->get('field_paywall');
    $delta = 0;
    $element = array();
    $form = array();
    $form_state = new FormState();
    $form_element_output = $paywallWidget->formElement($items, $delta, $element, $form, $form_state);

    $entity->field_paywall[0]->setValue(array(
      'enabled' => 0,
    ));
    $entity->save();
    $disabled_items = $entity->get('field_paywall');
    $form_element_output_disabled = $paywallWidget->formElement($disabled_items, $delta, $element, $form, $form_state);

    $this->assertTrue(!empty($form_element_output['enabled']), 'Enabled form element found');
    $this->assertEqual('Enabled', $form_element_output['enabled']['#title'], 'Enabled form element title correct');
    $this->assertEqual('checkbox', $form_element_output['enabled']['#type'], 'Enabled form element type correct');

    // Test both scenarios in which the default value is checked and not checked.
    $this->assertEqual(1, $form_element_output['enabled']['#default_value'], 'Enabled form element default value correct');
    $this->assertEqual(0, $form_element_output_disabled['enabled']['#default_value'], 'Disabled form element default value correct');
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
   * @return \Drupal\field_paywall\Plugin\Field\FieldWidget\PaywallWidget
   *   The paywall item base.
   */
  protected function getFieldWidgetFromEntity(EntityInterface $entity) {
    $widget = \Drupal::service('plugin.manager.field.widget')
      ->getInstance(array('field_definition' => $this->paywallFieldDefinition));

    return $widget;
  }
}