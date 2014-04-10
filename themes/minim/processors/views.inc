<?php

/**
 * Implements hook_preprocess_views_view_fields
 */
function minim_preprocess_views_view_fields(&$variables) {
  $fields_rendered = '';
  if (!empty($variables['fields'])) {
    foreach ($variables['fields'] AS $id => $field) {
      // Seperator
      if (!empty($field->separator)) {
        $fields_rendered .= $field->separator;
      }
      $fields_rendered .= $field->wrapper_prefix;
      $fields_rendered .= $field->label_html;
      $fields_rendered .= $field->content;
      $fields_rendered .= $field->wrapper_suffix;
    }
  }
  $variables['fields_rendered'] = $fields_rendered;
  // Allow for more granular preproces_functions
  $sub_functions = array();
  $sub_functions[] = __FUNCTION__ . '__' . $variables['view']->storage->id;
  $sub_functions[] = __FUNCTION__ . '__' . $variables['view']->storage->id . '__' . $variables['view']->current_display;
  foreach ($sub_functions AS $function) {
    if (function_exists($function)) {
      $function($variables);
    }
  }
}

/**
 * Implements hook_preprocess_views_view_fields__viewname
 * Adds js & css for basic slideshow
 */
function minim_preprocess_views_view_fields__slides(&$vars) {
  $slider_attachments = array(
    '#attached' => array(
      'js' => array(
        drupal_get_path('theme', 'minim') . '/js/rotator.js' => array(),
      ),
      'css' => array(
        drupal_get_path('theme', 'minim') . '/css/slider.css' => array(),
      ),
      'library' => array(
        'minim/jquery.cycle',
      ),
    ),
  );
  drupal_render($slider_attachments);

}
