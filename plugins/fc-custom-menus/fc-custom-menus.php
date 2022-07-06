<?php
/**
 * Plugin Name: Custom Menus
 * Description: Adds custom menus with shortcodes.
 * Author: Fairhead Creative
 * Author URI: https://builtforimpact.net
 * Version: 0.1
 * License: GPLv2 or later
 */

namespace Custom_Menus;

/**
 * Returns the specified url or shortcode when running the clean_url filter.
 *
 * @since 0.1
 *
 * @param string $url Cleaned up url.
 * @param string $item Original, not cleaned up url.
 * @param string $context Context of the clean_url filter (db or display).
 * @return string orig_url|url Specified url or shortcode.
 */
function allow_shortcodes( $url, $orig_url, $context ) {
  if ( 'db' === $context && ( '[fc_search]' === $orig_url || '[fc_cart]' === $orig_url ) ) {
    return $orig_url;
  } else if ( 'display' === $context && ( '[fc_search]' === $orig_url || '[fc_cart]' === $orig_url ) ) {
    return do_shortcode( $orig_url );
  }

  return $url;
}
add_filter( 'clean_url', __NAMESPACE__ . '\allow_shortcodes', 1, 3 );

/**
 * Returns the search shortcode template.
 *
 * @since 0.1
 *
 * @return string search shortcode html code.
 */
function search_shortcode() {
  ob_start();
  get_template_part( 'includes/sections/menu-item-fc-search' );
  $template = ob_get_contents();
  ob_end_clean();
  return $template;
}
add_shortcode( 'fc_search', __NAMESPACE__ . '\search_shortcode' );

/**
 * Returns the cart shortcode template.
 *
 * @since 0.1
 *
 * @return string cart shortcode html code.
 */
function cart_shortcode() {
  ob_start();
  get_template_part( 'includes/sections/menu-item-fc-cart' );
  $template = ob_get_contents();
  ob_end_clean();
  return $template;
}
add_shortcode( 'fc_cart', __NAMESPACE__ . '\cart_shortcode' );

/**
 * Returns the modified menu item output with the shortcode template.
 *
 * @since 0.1
 *
 * @param string $item_output Menu item output.
 * @param object $item Item object.
 * @return string menu item output.
 */
function modify_shortcode_menu_item( $item_output, $item ) {
  if ( '[fc_search]' === $item->url || '[fc_cart]' === $item->url ) {
    $item_output = do_shortcode( $item->url );
  }

  return $item_output;
}
add_filter( 'walker_nav_menu_start_el', __NAMESPACE__ . '\modify_shortcode_menu_item', 20, 2 );
?>