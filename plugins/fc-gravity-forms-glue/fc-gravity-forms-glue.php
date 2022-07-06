<?php
/**
 * Plugin Name: Gravity Forms Glue
 * Description: Acts as an integration point between the Gravity Forms plugin and Helipad child themes.
 * Author: Fairhead Creative
 * Author URI: https://builtforimpact.net
 * Version: 0.7
 * License: GPLv2 or later
 */

namespace Gravity_Forms_Glue;

/*
 * Disables Gravity Form's URL validation, which uses an old RFC specification.
 *
 * @since 0.5
 * 
 */
add_filter( 'gform_rfc_url_validation', '__return_false', 10, 1 );

/**
 * Filters Gravity Form's URL validation and returns whether URL is valid or not.
 *
 * @since 0.5
 *
 * @param  bool   $is_valid Gravity Form's URL validation result (true or false).
 * @param  string $url      The URL to validate.
 * @return bool   $is_valid Custom URL validation result (true or false).
 */
function custom_validation( $is_valid, $url ) {
  $regex  = "((https?|ftp)://)?"; // Scheme
  $regex .= "([a-z0-9\-\.]*)\.(([a-z]{2,9})|([0-9]{1,3}\.([0-9]{1,3})\.([0-9]{1,3})))"; // Host or IP
  $regex .= "(/([a-z0-9+\$_%-]\.?)+)*/?"; // Path
  if ( preg_match( "~^$regex$~i", $url ) ) {
    $is_valid = true;
  }

  return $is_valid;
}
add_filter( 'gform_is_valid_url', __NAMESPACE__ . '\custom_validation', 10, 2 );

/**
 * Modifies output of Gravity Form's fields.
 *
 * @since 0.1
 *
 * @param  string $field_content Unfiltered content.
 * @param  object $field         Field object
 * @return string $field_content Filtered or unfiltered content.
 */
function modify_fields( $field_content, $field ) {
  if ( is_admin() ) {
    return $field_content;
  }

  switch ( $field->type ) {
    case 'select':
      $field_content = str_replace( ' gfield_select', '', $field_content );
      $field_content = str_replace( '<select', '<div class="gfield_select"><select', $field_content );
      $field_content = str_replace( '</select>', '</select></div>', $field_content );
      break;

    case 'number':
      if ( isset( $field->appendContent ) && ! empty( $field->appendContent ) ) {
        $field_content = str_replace( 'aria-invalid="false"/>', 'aria-invalid="false"/><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
        $field_content = str_replace( 'aria-invalid="true"/>', 'aria-invalid="true"/><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
      }

      if ( isset( $field->prependContent ) && ! empty( $field->prependContent ) ) {
        $field_content = str_replace( '<input', '<div class="ginput_prepend">' . esc_html( $field->prependContent ) . '</div><input', $field_content );
      }
      break;

    case 'text':
      if ( isset( $field->appendContent ) && ! empty( $field->appendContent ) ) {
        $field_content = str_replace( 'aria-invalid="false" />', 'aria-invalid="false" /><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
        $field_content = str_replace( 'aria-invalid="true" />', 'aria-invalid="true" /><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
      }

      if ( isset( $field->prependContent ) && ! empty( $field->prependContent ) ) {
        $field_content = str_replace( '<input', '<div class="ginput_prepend">' . esc_html( $field->prependContent ) . '</div><input', $field_content );
      }
      break;

    case 'email':
      if ( isset( $field->appendContent ) && ! empty( $field->appendContent ) ) {
        $field_content = str_replace( 'aria-invalid="false" />', 'aria-invalid="false" /><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
        $field_content = str_replace( 'aria-invalid="true" />', 'aria-invalid="true" /><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
      }

      if ( isset( $field->prependContent ) && ! empty( $field->prependContent ) ) {
        $field_content = str_replace( '<input', '<div class="ginput_prepend">' . esc_html( $field->prependContent ) . '</div><input', $field_content );
      }
      break;

    case 'phone':
      if ( isset( $field->appendContent ) && ! empty( $field->appendContent ) ) {
        $field_content = str_replace( 'aria-invalid="false" />', 'aria-invalid="false" /><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
        $field_content = str_replace( 'aria-invalid="true" />', 'aria-invalid="true" /><div class="ginput_append">' . esc_html( $field->appendContent ) . '</div>', $field_content );
      }

      if ( isset( $field->prependContent ) && ! empty( $field->prependContent ) ) {
        $field_content = str_replace( '<input', '<div class="ginput_prepend">' . esc_html( $field->prependContent ) . '</div><input', $field_content );
      }
      break;
  }

  return $field_content;
}
add_filter( 'gform_field_content', __NAMESPACE__ . '\modify_fields', 10, 2 );

/*
* Replace Gravity Form's URL with the current site's URL in domain validation message
*
* @since 0.7
* 
*/
function validate_fields($result, $value, $form, $field) {
  if ($field->get_input_type() == 'website') {
    if ($result['is_valid'] == false) {
      $result['message'] = preg_replace('/(.*\(e.g. )(http:\/\/www.gravityforms.com)(\).*)/', '$1' . site_url() . '$3', $result['message']);
    }
  }

  return $result;
}
add_filter( 'gform_field_validation', __NAMESPACE__ . '\validate_fields', 10, 4 );

/**
 * Adds two text fields to the advanced tab of form fields.
 *
 * @since 0.2
 *
 * @param  integer $position Position of the field.
 * @param  integer $form_id  Form ID.
 * @return string            New advanced fields.
 */
function add_advanced_fields( $position, $form_id ) {
  if ( $position == 50 ) {
    ?>
    <li class="prepend_content_setting field_setting">
      <label class="section_label" for="prepend_content">Prepend Content</label>
      <input type="text" class="fieldwidth-3" id="prepend_content" size="35" onkeyup="SetFieldProperty( 'prependContent', this.value );"/>
    </li>
    <li class="append_content_setting field_setting">
      <label class="section_label" for="append_content">Append Content</label>
      <input type="text" class="fieldwidth-3" id="append_content" size="35" onkeyup="SetFieldProperty( 'appendContent', this.value );"/>
    </li>
    <?php
  }
}
add_action( 'gform_field_advanced_settings', __NAMESPACE__ . '\add_advanced_fields', 10, 2 );

/**
 * Adds text fields to form fiels and provides existing values to fields.
 *
 * @since 0.2
 *
 * @return string JavaScript code.
 */
function add_gform_editor_js() {
  ?>
  <script type='text/javascript'>
  jQuery( document ).ready( function() {
    if ( typeof fieldSettings === 'undefined' ) {
      return;
    }

    jQuery.each( fieldSettings, function( type, items ) {
      fieldSettings[ type ] += ', .prepend_content_setting, .append_content_setting';
    } );
  } );

  jQuery( document ).bind( 'gform_load_field_settings', function( event, field, form ) {
    jQuery( '#prepend_content' ).val( field.prependContent === undefined ? '' : field.prependContent );
    jQuery( '#append_content' ).val( field.appendContent === undefined ? '' : field.appendContent );
  } );
  </script>
  <?php
}
add_action( 'gform_editor_js', __NAMESPACE__ . '\add_gform_editor_js' );
?>
