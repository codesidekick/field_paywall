<?php
/**
 * @file
 * Contains \Drupal\field_paywall\Plugin\Field\FieldFormatter\PaywallFormatter.
 */

namespace Drupal\field_paywall\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'paywall' formatter.
 *
 * @FieldFormatter(
 *   id = "paywall_formatter",
 *   label = @Translation("Paywall"),
 *   field_types = {
 *     "paywall"
 *   }
 * )
 */
class PaywallFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      if (!empty($item->enabled)) {
        // Render output using snippets_default theme.
        $elements[$delta] = array(
          '#theme' => 'paywall',
          '#message' => String::checkPlain($this->getSetting('message')),
          '#hidden_fields' => String::checkPlain($this->getSetting('hidden_fields')),
        );
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    foreach ($entities_items as $entities_item) {
      $entity = $entities_item->getEntity();
      $value = $entities_item->getValue();
      $field_name = $entities_item->getFieldDefinition()->getName();

      if (!empty($value) && !empty($value[0]['enabled'])) {
        $entity->active_paywalls[$field_name] = array(
          'enabled' => TRUE,
          'hidden_fields' => array_filter($this->getSetting('hidden_fields')),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'message' => t('You have limited access to this item.'),
      'hidden_fields' => array(),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['message'] = array(
      '#title' => t('Message'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('message'),
      '#description' => array(
        '#markup' => t('You can change the style of the message by overriding the field template.'),
      ),
    );
    $element['hidden_fields'] = array(
      '#title' => t('Hidden fields'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('hidden_fields'),
      '#options' => $this->getAvailableFields(),
    );

    return $element;
  }

  /**
   * Returns a list of fields for this entity and this display mode to hide.
   *
   * @return array
   *
   */
  protected function getAvailableFields() {
    $available_fields = array();

    $field_definition = $this->fieldDefinition;
    $context = 'view';
    $target_id = $field_definition->getTargetEntityTypeId();
    $target_bundle = $field_definition->getTargetBundle();

    $entity_field_definitions = \Drupal::entityManager()->getFieldDefinitions($target_id, $target_bundle);
    foreach ($entity_field_definitions as $entity_field_definition) {
      if ($entity_field_definition->isDisplayConfigurable($context) && $entity_field_definition->getName() != $field_definition->getName()) {
        $available_fields[$entity_field_definition->getName()] = $entity_field_definition->getLabel();
      }
    }

    return $available_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $message = $this->getSetting('message');
    $summary[] = t('Message: @message', array(
      '@message' => $message,
    ));

    $hidden_fields = array_filter($this->getSetting('hidden_fields'));
    $summary[] = t('Hidden fields: @fields', array(
      '@fields' => implode(', ', $hidden_fields),
    ));

    return $summary;
  }
}