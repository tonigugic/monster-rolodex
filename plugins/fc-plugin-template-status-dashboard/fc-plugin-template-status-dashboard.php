<?php
/**
 * Plugin Name: Plugin & Template Status Dashboard
 * Description: A custom WordPress Admin page with a Dashboard that provides per site information about plugins & templates.
 * Author: Fairhead Creative
 * Author URI: https://builtforimpact.net
 * Version: 0.8.1
 * License: GPLv2 or later
 */

namespace Plugin_Template_Status_Dashboard;

const VERSION = '0.8.1';

/**
 * Enqueues scripts.
 *
 * @since 0.1
 */
function enqueue_scripts() {
  wp_enqueue_style( 'fc-plugin-template-status-dashboard', plugins_url( 'assets/css/fc-plugin-template-status-dashboard.css', __FILE__ ), array(), VERSION, 'all' );
  wp_enqueue_script( 'fc-plugin-template-status-dashboard', plugins_url( 'assets/js/fc-plugin-template-status-dashboard.js', __FILE__ ), array( 'jquery-ui-tabs' ), VERSION, true );
  wp_enqueue_script( 'jquery-ui-sortable' );
  wp_enqueue_script( 'jquery-ui-draggable' );
  wp_enqueue_script( 'jquery-ui-droppable' );
  wp_enqueue_script( 'wp-lists' );
  wp_enqueue_script( 'dashboard' );
  add_thickbox();
  wp_enqueue_script( 'plugin-install' );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );

/**
 * Creates a WordPress Admin page.
 *
 * @since 0.1
 *
 */
function add_admin_menu() {
  add_meta_boxes();
  add_menu_page( 'Plugin & Template Status Dashboard', 'PTSD', 'manage_network_options', 'plugin-template-status-dashboard', __NAMESPACE__ . '\status_dashboard_page' );
}
add_action( 'network_admin_menu', __NAMESPACE__ . '\add_admin_menu' );

/**
 * Creates meta boxes.
 *
 * @since 0.1
 *
 */
function add_meta_boxes() {
  $screen = 'toplevel_page_plugin-template-status-dashboard-network';
  $sites_ids = get_sites( array( 'fields' => 'ids' ) );

  $i = 0;
  foreach ( $sites_ids as $site_id ) {
    $plugins = get_blog_option( $site_id, 'active_plugins' );
    add_meta_box(
      'site' . $site_id,
      get_blog_option( $site_id, 'blogname' ),
      __NAMESPACE__ . '\render_site_metabox',
      $screen,
      $i++ % 2 === 0 ? 'normal' : 'side',
      'default',
      array(
        'site_id' => $site_id,
        'plugins' => $plugins
      )
    );
  }
}

/**
 * Renders the Plugin & Template Status Dashboard page.
 *
 * @since 0.1
 *
 */
function status_dashboard_page() {
  if ( ! function_exists( 'plugins_api' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
  }

  $screen = get_current_screen();
  $columns = absint( $screen->get_columns() );
  $columns_css = '';
  if ( $columns ) {
    $columns_css = ' columns-' . $columns;
  }
  ?>
  <div class="wrap">
    <h1>Plugin & Template Status Dashboard</h1>
    <div id="welcome-panel" class="welcome-panel">
    	<div class="welcome-panel-content">
    	  <div class="welcome-panel-column-container">
	        <div class="welcome-panel-column ptsd">
			      <h3>Network activated plugins</h3>
            <ul class="fc-ptsd-network-plugins-list">
              <?php
              $plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
              $plugins = array_keys( $plugins );
              sort( $plugins );
              foreach ( $plugins as $plugin ) {
                $slug = dirname( plugin_basename( $plugin ) );
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                if ( 'Fairhead Creative' !== $plugin_data['Author'] ) {
                  $changelog_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $slug . '&section=changelog&TB_iframe=true' );
                  $args = array(
                    'slug'   => $slug,
                    'fields' => array(
                      'version'       => true,
                      'homepage'      => true,
                      'download_link' => true
                    )
                  );
                  $call_api = plugins_api( 'plugin_information', $args );
                  if ( is_wp_error( $call_api ) ) {
                    $api_error = $call_api->get_error_message();
                  } else {
                    if ( ! empty( $call_api->version ) ) {
                      $api_latest_version = $call_api->version;
                      $api_homepage = $call_api->homepage;
                      $api_download_link = $call_api->download_link;
                    }
                  }
                }
                echo '<li' . ( isset( $api_latest_version ) && $api_latest_version !== $plugin_data['Version'] ? ' class="update"' : '' ) . '>' .
                esc_html( $plugin_data['Name'] ) . ' ' . esc_html( $plugin_data['Version'] ) .
                ( isset( $api_latest_version ) && $api_latest_version !== $plugin_data['Version'] ? ' 
                <a href="' . esc_url( $changelog_url ) . '" class="dashicons dashicons-list-view fc thickbox open-plugin-details-modal" 
                title="Changelog for ' . esc_html( $plugin_data['Name'] ) . ' ' . esc_html( $api_latest_version ) . '"></a> 
                <a href="' . esc_url( $api_download_link ) . '" class="dashicons dashicons-download fc" 
                title="Download ' . esc_html( $plugin_data['Name'] ) . ' ' . esc_html( $api_latest_version ) . '"></a>' : '' ) . '</li>';

                unset( $api_latest_version, $api_homepage, $api_download_link, $slug, $changelog_url );
              }
              ?>
            </ul>
	        </div>
	        <div class="welcome-panel-column">
		        
	        </div>
	        <div class="welcome-panel-column welcome-panel-last">
		        
	        </div>
	      </div>
      </div>
    </div>
    <div id="dashboard-widgets-wrap">
      <div id="dashboard-widgets" class="metabox-holder<?php echo $columns_css; ?>">
        <div id="postbox-container-1" class="postbox-container">
          <?php do_meta_boxes( $screen->id, 'normal', '' ); ?>
        </div>
        <div id="postbox-container-2" class="postbox-container">
          <?php do_meta_boxes( $screen->id, 'side', '' ); ?>
        </div>
        <div id="postbox-container-3" class="postbox-container">
          <?php do_meta_boxes( $screen->id, 'column3', '' ); ?>
        </div>
        <div id="postbox-container-4" class="postbox-container">
          <?php do_meta_boxes( $screen->id, 'column4', '' ); ?>
        </div>
      </div>
    </div>
  </div>
  <?php
  wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
  wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
}

/**
 * Renders sites metaboxes.
 *
 * @since 0.1
 *
 */
function render_site_metabox( $post, $metabox ) {
  if ( ! function_exists( 'plugins_api' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
  }

  $site_id = $metabox['args']['site_id'];
  $plugins = $metabox['args']['plugins'];
  ?>
  <div class="tabs">
    <ul>
      <li><a href="#tabs-<?php echo $site_id; ?>-plugins">Plugins</a></li>
      <?php
      if ( false !== array_search( 'woocommerce/woocommerce.php', $plugins ) ) {
        ?>
        <li><a href="#tabs-<?php echo $site_id; ?>-woocommerce-templates">WooCommerce templates</a></li>
        <?php
      }  
      ?>
    </ul>
    <div id="tabs-<?php echo $site_id; ?>-plugins">
      <ul class="fc-ptsd-plugins-list">
        <?php
        foreach ( $plugins as $plugin ) {
          $slug = dirname( plugin_basename( $plugin ) );
          $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
          if ( 'woocommerce' !== $slug && ( strstr( $plugin_data['PluginURI'], 'woothemes.com' ) || strstr( $plugin_data['PluginURI'], 'woocommerce.com' ) ) ) {
            $changelog_url = 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . dirname( $plugin ) . '/changelog.txt';
            $changelog = wp_safe_remote_get( $changelog_url );
            $cl_lines  = explode( "\n", wp_remote_retrieve_body( $changelog ) );
            if ( ! empty( $cl_lines ) ) {
              foreach ( $cl_lines as $line_num => $cl_line ) {
                if ( preg_match( '/^[0-9]/', $cl_line ) ) {
                  $api_latest_version = preg_replace( '~[^0-9,.]~' , '' , stristr( $cl_line , 'version' ) );
                  $api_homepage = $plugin_data['PluginURI'];
                  $api_download_link = get_admin_url( 1, 'admin.php?page=wc-addons&section=helper' );
                  break;
                }
              }
            }
            $changelog_url .= '?TB_iframe=true';
          } else {
            $changelog_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $slug . '&section=changelog&TB_iframe=true' );
            $args = array(
              'slug'   => $slug,
              'fields' => array(
                'version'       => true,
                'homepage'      => true,
                'download_link' => true
              )
            );
            $call_api = plugins_api( 'plugin_information', $args );
            if ( is_wp_error( $call_api ) ) {
              $api_error = $call_api->get_error_message();
            } else {
              if ( ! empty( $call_api->version ) ) {
                $api_latest_version = $call_api->version;
                $api_homepage = $call_api->homepage;
                $api_download_link = $call_api->download_link;
              }
            }
          }
          echo '<li' . ( isset( $api_latest_version ) && $api_latest_version !== $plugin_data['Version'] ? ' class="update"' : '' ) . '>' .
          esc_html( $plugin_data['Name'] ) . ' ' . esc_html( $plugin_data['Version'] ) . ( isset( $api_latest_version ) && $api_latest_version !== $plugin_data['Version'] ? '
          <a href="' . esc_url( $changelog_url ) . '" class="dashicons dashicons-list-view fc thickbox open-plugin-details-modal"
          title="Changelog for ' . esc_html( $plugin_data['Name'] ) . ' ' . esc_html( $api_latest_version ) . '"></a>
          <a href="' . esc_url( $api_download_link ) . '" class="dashicons dashicons-download fc"
          title="Download ' . esc_html( $plugin_data['Name'] ) . ' ' . esc_html( $api_latest_version ) . '"></a>' : '' ) . '</li>' . "\n";

          unset( $api_latest_version, $api_homepage, $api_download_link, $slug, $changelog_url );
        }
        ?>
      </ul>
    </div>
    <?php
    if ( false !== array_search( 'woocommerce/woocommerce.php', $plugins ) ) {
      ?>
      <div id="tabs-<?php echo $site_id; ?>-woocommerce-templates">
        <?php
        switch_to_blog( $site_id );
        $theme_slug = get_stylesheet();
        $plugin_path = WP_PLUGIN_DIR . '/woocommerce';
        $template_path = 'woocommerce/';
        $stylesheet_directory = get_stylesheet_directory();
        $template_directory = get_template_directory();
        $override_files     = array();
        $outdated_templates = false;
        $scan_files         = scan_template_files( $plugin_path . '/templates/' );
        foreach ( $scan_files as $file ) {
          if ( file_exists( $stylesheet_directory . '/' . $template_path . $file ) ) {
            $theme_file = $stylesheet_directory . '/' . $template_path . $file;
          } elseif ( file_exists( $template_directory . '/' . $template_path . $file ) ) {
            $theme_file = $template_directory . '/' . $template_path . $file;
          } else {
            $theme_file = false;
          }

          if ( ! empty( $theme_file ) ) {
            $core_version  = get_file_version( $plugin_path . '/templates/' . $file );
            $theme_version = get_file_version( $theme_file );
            $override_files[] = array(
              'file'         => str_replace( $stylesheet_directory . '/' . $template_path, '', $theme_file ),
              'version'      => $theme_version,
              'core_version' => $core_version,
            );
          }
        }
        restore_current_blog();
        ?>
        <ul class="fc-ptsd-woocommerce-templates-list">
          <?php
          foreach( $override_files as $override_file ) {
            ?>
            <li<?php echo ( $override_file['version'] < $override_file['core_version'] ? ' class="update"' : '' ); ?>>
              <?php echo $override_file['file']; ?>
              <?php echo ( $override_file['version'] < $override_file['core_version'] ? ' (theme: ' . $override_file['version'] . '
              <a href="https://github.com/fairheadcreative/fairsites/blob/master/wp-content/themes/' . $theme_slug . '/' . $template_path . $override_file['file'] . '"
              target="_blank" class="dashicons dashicons-admin-links"></a>; core: ' . $override_file['core_version'] . '
              <a href="https://github.com/woocommerce/woocommerce/blob/master/templates/' . $override_file['file'] . '"
              target="_blank" class="dashicons dashicons-admin-links"></a>)' : '' ); ?>
            </li>
            <?php
          }
          ?>
        </ul>
      </div>
      <?php
    }
    ?>
    </div>
    <?php
}

/**
 * Scans the template files. Copy of WC scan_template_files.
 *
 * @since 0.6
 *
 * @param  string $template_path WooCommerce templates path.
 * @return array  $result        All WooCommerce templates files.
 */
function scan_template_files( $template_path ) {
  $files = @scandir( $template_path );
  $result = array();

  if ( ! empty( $files ) ) {
    foreach ( $files as $key => $value ) {
      if ( ! in_array( $value, array( ".", ".." ) ) ) {
        if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
          $sub_files = scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
          foreach ( $sub_files as $sub_file ) {
            $result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
          }
        } else {
          $result[] = $value;
        }
      }
    }
  }

  return $result;
}

/**
 * Retrieves metadata from a file. Based on WP Core's get_file_data function and a copy of WC get_file_version.
 *
 * @since 0.6
 *
 * @param  string $file    Path to the file.
 * @return string $version File version number.
 */
function get_file_version( $file ) {
  if ( ! file_exists( $file ) ) {
    return '';
  }

  $fp = fopen( $file, 'r' );
  $file_data = fread( $fp, 8192 );
  fclose( $fp );

  $file_data = str_replace( "\r", "\n", $file_data );
  $version = '';
  if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
    $version = _cleanup_header_comment( $match[1] );
  }

  return $version;
}
?>