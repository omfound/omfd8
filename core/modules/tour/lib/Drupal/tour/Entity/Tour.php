<?php

/**
 * @file
 * Contains \Drupal\tour\Entity\Tour.
 */

namespace Drupal\tour\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\tour\TipsBag;
use Drupal\tour\TourInterface;

/**
 * Defines the configured tour entity.
 *
 * @ConfigEntityType(
 *   id = "tour",
 *   label = @Translation("Tour"),
 *   controllers = {
 *     "view_builder" = "Drupal\tour\TourViewBuilder"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class Tour extends ConfigEntityBase implements TourInterface {

  /**
   * The name (plugin ID) of the tour.
   *
   * @var string
   */
  public $id;

  /**
   * The module which this tour is assigned to.
   *
   * @var string
   */
  public $module;

  /**
   * The label of the tour.
   *
   * @var string
   */
  public $label;

  /**
   * The routes on which this tour should be displayed.
   *
   * @var array
   */
  protected $routes = array();

  /**
   * The routes on which this tour should be displayed, keyed by route id.
   *
   * @var array
   */
  protected $keyedRoutes;

  /**
   * Holds the collection of tips that are attached to this tour.
   *
   * @var \Drupal\tour\TipsBag
   */
  protected $tipsBag;

  /**
   * The array of plugin config, only used for export and to populate the $tipsBag.
   *
   * @var array
   */
  protected $tips = array();

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::__construct();
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->tipsBag = new TipsBag(\Drupal::service('plugin.manager.tour.tip'), $this->tips);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    return $this->routes;
  }

  /**
   * {@inheritdoc}
   */
  public function getTip($id) {
    return $this->tipsBag->get($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTips() {
    $tips = array();
    foreach ($this->tips as $id => $tip) {
      $tips[] = $this->getTip($id);
    }
    uasort($tips, function ($a, $b) {
      if ($a->getWeight() == $b->getWeight()) {
        return 0;
      }
      return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
    });

    \Drupal::moduleHandler()->alter('tour_tips', $tips, $this);
    return array_values($tips);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
      'routes',
      'tips',
    );
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMatchingRoute($route_name, $route_params) {
    if (!isset($this->keyedRoutes)) {
      $this->keyedRoutes = array();
      foreach ($this->getRoutes() as $route) {
        $this->keyedRoutes[$route['route_name']] = isset($route['route_params']) ? $route['route_params'] : array();
      }
    }
    if (!isset($this->keyedRoutes[$route_name])) {
      // We don't know about this route.
      return FALSE;
    }
    if (empty($this->keyedRoutes[$route_name])) {
      // We don't need to worry about route params, the route name is enough.
      return TRUE;
    }
    foreach ($this->keyedRoutes[$route_name] as $key => $value) {
      // If a required param is missing or doesn't match, return FALSE.
      if (empty($route_params[$key]) || $route_params[$key] !== $value) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function resetKeyedRoutes() {
    unset($this->keyedRoutes);
  }

}
