<?php
/**
 * Twenty Twelve functions and definitions.
 *
 * Sets up the theme and provides some helper functions, which are used
 * in the theme as custom template tags. Others are attached to action and
 * filter hooks in WordPress to change core functionality.
 *
 * When using a child theme (see http://codex.wordpress.org/Theme_Development and
 * http://codex.wordpress.org/Child_Themes), you can override certain functions
 * (those wrapped in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before the parent
 * theme's file, so the child theme functions would be used.
 *
 * Functions that are not pluggable (not wrapped in function_exists()) are instead attached
 * to a filter or action hook.
 *
 * For more information on hooks, actions, and filters, see http://codex.wordpress.org/Plugin_API.
 *
 * @package WordPress
 * @subpackage Overseer
 */

function sism_debug($var){

   $result = var_export( $var, true );

   $trace = debug_backtrace();
   $level = 1;
   $file   = $trace[$level]['file'];
   $line   = $trace[$level]['line'];
   $object = $trace[$level]['object'];
   if (is_object($object)) { $object = get_class($object); }

   error_log("Line $line ".($object?"of object $object":"")."(in $file):\n$result");
}


?>
