<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FormatterPluginManager.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for field formatters.
 */
class FormatterPluginManager extends DefaultPluginManager {

  /**
   * An array of formatter options for each field type.
   *
   * @var array
   */
  protected $formatterOptions;

  /**
   * The field type manager to define field.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a FormatterPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LanguageManager $language_manager, FieldTypePluginManagerInterface $field_type_manager) {

    parent::__construct('Plugin/Field/FieldFormatter', $namespaces, $module_handler, 'Drupal\Core\Field\Annotation\FieldFormatter');

    $this->setCacheBackend($cache_backend, $language_manager, 'field_formatter_types_plugins');
    $this->alterInfo('field_formatter_info');
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // @todo This is copied from \Drupal\Core\Plugin\Factory\ContainerFactory.
    //   Find a way to restore sanity to
    //   \Drupal\Core\Field\FormatterBase::__construct().
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    return new $plugin_class($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode']);
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - field_definition: (FieldDefinitionInterface) The field definition.
   *   - view_mode: (string) The view mode.
   *   - prepare: (bool, optional) Whether default values should get merged in
   *     the 'configuration' array. Defaults to TRUE.
   *   - configuration: (array) the configuration for the formatter. The
   *     following key value pairs are allowed, and are all optional if
   *     'prepare' is TRUE:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - type: (string) The formatter to use. Defaults to the
   *       'default_formatter' for the field type, The default formatter will
   *       also be used if the requested formatter is not available.
   *     - settings: (array) Settings specific to the formatter. Each setting
   *       defaults to the default value specified in the formatter definition.
   *
   * @return \Drupal\Core\Field\FormatterInterface
   *   A formatter object.
   */
  public function getInstance(array $options) {
    $configuration = $options['configuration'];
    $field_definition = $options['field_definition'];
    $field_type = $field_definition->getType();

    // Fill in default configuration if needed.
    if (!isset($options['prepare']) || $options['prepare'] == TRUE) {
      $configuration = $this->prepareConfiguration($field_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default formatter if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    $definition = $this->getDefinition($configuration['type']);
    if (!isset($definition['class']) || !in_array($field_type, $definition['field_types'])) {
      // Grab the default widget for the field type.
      $field_type_definition = $this->fieldTypeManager->getDefinition($field_type);
      $plugin_id = $field_type_definition['default_formatter'];
    }

    $configuration += array(
      'field_definition' => $field_definition,
      'view_mode' => $options['view_mode'],
    );
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Merges default values for formatter configuration.
   *
   * @param string $field_type
   *   The field type.
   * @param array $properties
   *   An array of formatter configuration.
   *
   * @return array
   *   The display properties with defaults added.
   */
  public function prepareConfiguration($field_type, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += array(
      'label' => 'above',
      'settings' => array(),
    );
    // If no formatter is specified, use the default formatter.
    if (!isset($configuration['type'])) {
      $field_type = $this->fieldTypeManager->getDefinition($field_type);
      $configuration['type'] = $field_type['default_formatter'];
    }
    // Fill in default settings values for the formatter.
    $configuration['settings'] += $this->getDefaultSettings($configuration['type']);

    return $configuration;
  }

  /**
   * Returns an array of formatter options for a field type.
   *
   * @param string|null $field_type
   *   (optional) The name of a field type, or NULL to retrieve all formatters.
   *
   * @return array
   *   If no field type is provided, returns a nested array of all formatters,
   *   keyed by field type.
   */
  public function getOptions($field_type = NULL) {
    if (!isset($this->formatterOptions)) {
      $options = array();
      $field_types = $this->fieldTypeManager->getDefinitions();
      $formatter_types = $this->getDefinitions();
      uasort($formatter_types, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
      foreach ($formatter_types as $name => $formatter_type) {
        foreach ($formatter_type['field_types'] as $formatter_field_type) {
          // Check that the field type exists.
          if (isset($field_types[$formatter_field_type])) {
            $options[$formatter_field_type][$name] = $formatter_type['label'];
          }
        }
      }
      $this->formatterOptions = $options;
    }
    if ($field_type) {
      return !empty($this->formatterOptions[$field_type]) ? $this->formatterOptions[$field_type] : array();
    }
    return $this->formatterOptions;
  }

  /**
   * Returns the default settings of a field formatter.
   *
   * @param string $type
   *   A field formatter type name.
   *
   * @return array
   *   The formatter type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['settings']) ? $info['settings'] : array();
  }

}
