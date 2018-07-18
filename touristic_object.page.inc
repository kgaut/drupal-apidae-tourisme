<?php

/**
 * @file
 * Contains touristic_object.page.inc.
 *
 * Page callback for Touristic object entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Touristic object templates.
 *
 * Default template: touristic_object.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_touristic_object(array &$variables) {
  // Fetch TouristicObject Entity Object.
  $touristic_object = $variables['elements']['#touristic_object'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
