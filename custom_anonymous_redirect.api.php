<?php

/**
 * @file
 * Hooks related to the Custom Anonymous Redirect module.
 */

/**
 * @addtogroup hooks
 * @{
 */

function hook_custom_anonymous_redirect_paths_alter(string &$redirectUrlOverridesText) {
  // Add a new path to the list of included paths.
  $redirectUrlOverrides[] = 'my-custom-path';
}

/**
 * @} End of "addtogroup hooks".
 */
