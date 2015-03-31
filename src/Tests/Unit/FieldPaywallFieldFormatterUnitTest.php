<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Unit\FieldPaywallFieldFormatterUnitTest.
 */

namespace Drupal\field_paywall\Tests\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Tests\FieldUnitTestBase;
use Drupal\field_paywall\Plugin\Field\FieldFormatter\PaywallFormatter;
use Drupal\Core\Language\LanguageInterface;

/**
 * @coversDefaultClass \Drupal\field_paywall\Plugin\Field\FieldFormatter\PaywallFormatter
 * @group Paywall
 */
class FieldPaywallFieldFormatterUnitTest extends FieldUnitTestBase {

  public static $modules = array('field_paywall');

  protected $paywallTestMessage = 'test paywall message';

  protected $paywallHiddenFields = array();

  protected $otherFieldNames = array(
    'field_test_1',
    'field_test_2',
  );

  /**
   * The paywall formatter plugin to test.
   *
   * @var \Drupal\field_paywall\Plugin\Field\FieldFormatter\PaywallFormatter;
   */
  protected $paywallFormatterInstance = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createPaywallField();

    foreach ($this->otherFieldNames as $field_name) {
      $this->createBasicTextField($field_name);
    }

    $this->setFormatterInstance();
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElements() {
    $entity = $this->createTestEntity(TRUE);

    $method_output = $this->paywallFormatterInstance->view($entity->field_paywall);

    $this->assertEqual('paywall', $method_output[0]['#theme'], 'Paywall field theme correct');
    $this->assertEqual($this->paywallTestMessage, $method_output[0]['#message'], 'Paywall message correct');
    $this->assertEqual($this->paywallHiddenFields, $method_output[0]['#hidden_fields'], 'Paywall hidden fields correct');
  }

  /**
   * @covers ::prepareView
   */
  public function testPrepareView() {
    $entity = $this->createTestEntity(TRUE);

    $this->paywallFormatterInstance->prepareView(array($entity->field_paywall));

    $this->assertTrue(!empty($entity->activePaywalls['field_paywall']), 'Active paywall set on Entity');

    $active_paywall = $entity->activePaywalls['field_paywall'];
    $enabled = $active_paywall['enabled'];
    $hidden_fields = $active_paywall['hidden_fields'];

    $this->assertEqual(1, $enabled, 'Paywall is enabled');
    $this->assertEqual($this->paywallHiddenFields, $hidden_fields, 'Hidden fields set');
  }

  /**
   * @covers ::defaultSettings
   */
  public function testDefaultSettings() {
    $default_settings = $this->paywallFormatterInstance->defaultSettings();

    $this->assertEqual('You have limited access to this item.', $default_settings['message'], 'Default message correct');
    $this->assertEqual(array(), $default_settings['hidden_fields'], 'Default hidden fields correct');
  }

  /**
   * @covers ::settingsForm
   */
  public function testSettingsForm() {
    $form_state = new FormState();
    $settings_form = $this->paywallFormatterInstance->settingsForm(array(), $form_state);

    $this->assertEqual('textarea', $settings_form['message']['#type'], 'Message field is a textarea');
    $this->assertEqual('checkboxes', $settings_form['hidden_fields']['#type'], 'Hidden fields field is checkboxes');

    $this->assertEqual($this->paywallTestMessage, $settings_form['message']['#default_value'], 'Message default value in settings form correct');
    $this->assertEqual($this->paywallHiddenFields, $settings_form['hidden_fields']['#default_value'], 'Hidden fields default value in settings form correct');

    // Check that hidden fields is showing availables correctly.
    $hidden_field_options = $settings_form['hidden_fields']['#options'];
    $this->assertEqual(count($this->otherFieldNames), count($hidden_field_options), 'Correct number of hidden field options');
    foreach ($settings_form['hidden_fields']['#options'] as $field_name) {
      $this->assertTrue(in_array($field_name, $this->otherFieldNames), 'Field name option correct');
    }
  }
//
//  /**
//   * @covers ::getAvailableFields
//   */
//  public function testGetAvailableFields() {
//    $this->assertTrue(TRUE, 'true');
//  }
//
//  /**
//   * @covers ::shouldUserSeePaywall
//   */
//  public function testShouldUserSeePaywall() {
//    $this->assertTrue(TRUE, 'true');
//  }
//
//  /**
//   * @covers ::settingsSummary
//   */
//  public function testSettingsSummary() {
//    $this->assertTrue(TRUE, 'true');
//  }

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
   * Create the paywall field.
   */
  protected function createPaywallField() {
    entity_create('field_storage_config', array(
      'field_name' => 'field_paywall',
      'entity_type' => 'entity_test',
      'type' => 'paywall',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_paywall',
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Create a basic string textfield and attach to the entity bundle.
   *
   * @param string $field_name
   *   The field name to create.
   */
  protected function createBasicTextField($field_name) {
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'string',
      'cardinality' => 1,
    ))->save();

    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $field_name,
      'bundle' => 'entity_test',
    ))->save();

    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Set the formatter instance used in the test.
   */
  protected function setFormatterInstance() {
    $formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');

    $entity_manager = $this->container->get('entity.manager');
    $definitions = $entity_manager->getFieldDefinitions('entity_test', 'entity_test');
    $formatter_options = array(
      'field_definition' => $definitions['field_paywall'],
      'view_mode' => 'default',
      'configuration' => array(
        'type' => 'paywall_formatter',
        'settings' => array(
          'message' => $this->paywallTestMessage,
          'hidden_fields' => $this->paywallHiddenFields,
        ),
      ),
    );

    $this->paywallFormatterInstance = $formatter_plugin_manager->getInstance($formatter_options);
  }

}