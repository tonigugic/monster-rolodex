<?php
/**
 * Plugin Name: Infinite Post Loader
 * Description: Loads and displays posts infinitely.
 * Author: Fairhead Creative
 * Author URI: https://builtforimpact.net
 * Version: 1.3
 * License: GPLv2 or later
 */

namespace Infinite_Post_Loader;

const VERSION = '1.3';

/**
 * Enqueues scripts.
 *
 * @since 0.1
 */
function enqueue_scripts() {
  wp_register_script( 'fc-infinite-post-loader', plugin_dir_url( __FILE__ ) . 'js/loader.js', array( 'jquery' ), VERSION, true );
  wp_localize_script( 'fc-infinite-post-loader', 'fcipl_posts', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
  wp_enqueue_script( 'fc-infinite-post-loader' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );

/**
 * Generates additional posts for the infinite post loader.
 *
 * @since 0.1
 *
 * @return string Additional posts html and javascript code.
 */
function fcipl_more_post() {
  $post_block_ajax    = esc_attr( $_POST['template_ajax'] );
  $post_block_ajax_js = esc_attr( $_POST['template_ajax_js'] );
  $search_key         = esc_attr( $_POST['search_key'] );
  $ppp_ajax           = esc_attr( $_POST['ppp_ajax'] );
  $page               = esc_attr( $_POST['pageNumber'] );
  $cat                = esc_attr( $_POST['cat'] );
  $year               = esc_attr( $_POST['year'] );
  $monthnum           = esc_attr( $_POST['monthnum'] );
  $day                = esc_attr( $_POST['day'] );
  $author             = esc_attr( $_POST['author'] );
  $front_page         = esc_attr( $_POST['front_page'] );

  $sticky_posts = array();
  if ( ! isset( $search_key ) ) {
    $sticky_posts = get_option( 'sticky_posts' );

    if ( ! empty( $cat ) ) {
      foreach ( $sticky_posts as $sticky_post ) {
        $categories = wp_get_post_categories( $sticky_post, array( 'fields' => 'ids' ) );
        if ( in_array( $cat, $categories ) ) {
          $position = array_search( $sticky_post, $sticky_posts );
          unset( $sticky_posts[ $position ] );
        }
      }
    }

    if ( ! empty( $year ) ) {
      foreach ( $sticky_posts as $sticky_post ) {
        $sticky_post_date = get_the_date( 'Y/m/d', $sticky_post );
        $sticky_post_date_array = explode( '/', $sticky_post_date );
        if ( $sticky_post_date_array[0] === $year && ( ( ! empty( $monthnum ) && $sticky_post_date_array[1] === $monthnum ) || empty( $monthnum ) ) &&
             ( ( ! empty( $day ) && $sticky_post_date_array[2] === $day ) || empty( $day ) ) ) {
          $position = array_search( $sticky_post, $sticky_posts );
          unset( $sticky_posts[ $position ] );
        }
      }
    }
  }

  $sticky_count = count( $sticky_posts );

  header( 'Content-Type: text/html' );

  $args = array(
    'suppress_filters'    => true,
    'ignore_sticky_posts' => true,
    'post__not_in'        => $sticky_posts,
    'post_type'           => 'post',
    'posts_per_page'      => $ppp_ajax,
    'offset'              => $sticky_count - ( ( $page - 1 ) * $ppp_ajax ),
    'post_status'         => 'publish'
  );

  if ( isset( $search_key ) ) {
    $args['s'] = $search_key;
  }

  if ( ! empty( $cat ) ) {
    $args['cat'] = $cat;
  }

  if ( ! empty( $year ) ) {
    $args['year'] = $year;
  }

  if ( ! empty( $monthnum ) ) {
    $args['monthnum'] = $monthnum;
  }

  if ( ! empty( $day ) ) {
    $args['day'] = $day;
  }

  if ( ! empty( $author ) ) {
    $args['author'] = $author;
  }

  $loop = new \WP_Query( $args );

  $out = '';

  if ( $loop->post_count < $ppp_ajax ) {
    $out .= '<script type="text/javascript">
      $(".load-indicator").addClass("is-off");
    </script>';
  }

  if ( $loop->have_posts() ) :
    while ( $loop->have_posts() ) : $loop->the_post();
      ob_start();
      set_query_var( 'front_page', $front_page );
      get_template_part( $post_block_ajax );
      $template = ob_get_contents();
      ob_end_clean();
      $out .= $template;
      unset( $template );
    endwhile;

    ob_start();
    get_template_part( $post_block_ajax_js );
    $templatejs = ob_get_contents();
    ob_end_clean();
    $out .= $templatejs;
    unset( $templatejs );

  endif;
  wp_reset_postdata();
  die( $out );
}
add_action( 'wp_ajax_nopriv_fcipl_more_post', __NAMESPACE__ . '\fcipl_more_post' );
add_action( 'wp_ajax_fcipl_more_post', __NAMESPACE__ . '\fcipl_more_post' );

/**
 * Creates the initial block post html code.
 *
 * @since 0.1
 *
 * @param array $atts Shortcode attributes.
 * @return string Initial block post html code.
 */
function block_post_shortcode( $atts ) {
  global $cat;

  $a = shortcode_atts( array(
    'id'                   => 'fcipl-posts',
    'class'                => 'archive-posts',
    'child_class'          => '',
    'posts_per_page'       => 6,
    'posts_per_page_ajax'  => 6,
    'template'             => 'includes/sections/block-post',
    'template_ajax'        => 'includes/sections/block-post-ajax',
    'template_ajax_js'     => 'includes/sections/block-post-ajax-js',
    'category'             => '',
    'is_loading_on_scroll' => 'true',
  ), $atts );

  $year     = ! is_single() ? get_query_var( 'year' ) : '';
  $monthnum = ! is_single() ? get_query_var( 'monthnum' ) : '';
  $day      = ! is_single() ? get_query_var( 'day' ) : '';
  $author   = get_query_var( 'author' );

  if ( isset( $a['category'] ) && ! empty( $a['category'] ) ) {
    $category = get_cat_ID( $a['category'] );
  }

  $out = '<div id="' . esc_attr( $a['id'] ) . '" 
               class="' . esc_attr( $a['class'] ) . ( isset( $a['child_class'] ) && ! empty( $a['child_class'] ) ? ' ' . esc_attr( $a['child_class'] ) : '' ) . '" 
               data-ppp="' . esc_attr( $a['posts_per_page'] ) . '" 
               data-ppp-ajax="' . esc_attr( $a['posts_per_page_ajax'] ) . '" 
               data-is-loading-on-scroll="' . $a['is_loading_on_scroll'] . '" 
               data-template-ajax="' . esc_attr( $a['template_ajax'] ) . '" 
               data-template-ajax-js="' . esc_attr( $a['template_ajax_js'] ) . '"' .
               ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ? ' data-search-key="' . esc_attr( $_GET['s'] ) . '"' : '' ) .
               ( isset( $cat ) && ! empty( $cat ) && ! isset( $category ) ? ' data-cat="' . esc_attr( $cat ) . '"' : '' ) .
               ( isset( $category ) ? ' data-cat="' . esc_attr( $category ) . '"' : '' ) .
               ( ! empty( $year ) ? ' data-year="' . $year . '"' : '' ) .
               ( ! empty( $monthnum ) ? ' data-monthnum="' . $monthnum . '"' : '' ) .
               ( ! empty( $day ) ? ' data-day="' . $day . '"' : '' ) .
               ( ! empty( $author ) ? ' data-author="' . $author . '"' : '' ) .
               ( ! is_front_page() ? '' : ' data-front-page="true"' ) . '>';

  if ( isset( $category ) ) {
    $cat = $category;
  }

  $sticky_posts = array();
  if ( ! isset( $_GET['s'] ) && empty( $_GET['s'] ) && empty( $author ) ) {
    $sticky_posts = get_option( 'sticky_posts' );

    if ( isset( $cat ) && ! empty( $cat ) ) {
      foreach ( $sticky_posts as $sticky_post ) {
        $categories = wp_get_post_categories( $sticky_post, array( 'fields' => 'ids' ) );
        if ( in_array( $cat, $categories ) ) {
          $position = array_search( $sticky_post, $sticky_posts );
          unset( $sticky_posts[ $position ] );
        }
      }
    }

    if ( ! empty( $year ) ) {
      foreach ( $sticky_posts as $sticky_post ) {
        $sticky_post_date = get_the_date( 'Y/m/d', $sticky_post );
        $sticky_post_date_array = explode( '/', $sticky_post_date );
        if ( $sticky_post_date_array[0] === $year && ( ( ! empty( $monthnum ) && $sticky_post_date_array[1] === $monthnum ) || empty( $monthnum ) ) &&
             ( ( ! empty( $day ) && $sticky_post_date_array[2] === $day ) || empty( $day ) ) ) {
          $position = array_search( $sticky_post, $sticky_posts );
          unset( $sticky_posts[ $position ] );
        }
      }
    }
  }

  $sticky_count = count( $sticky_posts );
  if ( $sticky_count > 0 ) {
    $args = array(
      'post_type'   => 'post',
      'post__in'    => $sticky_posts,
      'post_status' => 'publish'
    );

    if ( isset( $cat ) && ! empty( $cat ) ) {
      $args['cat'] = $cat;
    }

    if ( ! empty( $year ) ) {
      $args['year'] = $year;
    }

    if ( ! empty( $monthnum ) ) {
      $args['monthnum'] = $monthnum;
    }

    if ( ! empty( $day ) ) {
      $args['day'] = $day;
    }

    $loop = new \WP_Query( $args );
    if ( $loop->have_posts() ) :
      while ( $loop->have_posts() ) : $loop->the_post();
        ob_start();
        get_template_part( esc_attr( $a['template'] ) );
        $template = ob_get_contents();
        ob_end_clean();
        $out .= $template;
        unset( $template );
      endwhile;
    endif;
  }
  
  $posts_per_page = esc_attr( $a['posts_per_page'] ) - $sticky_count;
  $args = array(
    'suppress_filters'    => true,
    'ignore_sticky_posts' => true,
    'post_type'           => 'post',
    'post__not_in'        => $sticky_posts,
    'posts_per_page'      => $posts_per_page,
    'post_status'         => 'publish'
  );

  if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
    $args['s'] = $_GET['s'];
  }

  if ( isset( $cat ) && ! empty( $cat ) ) {
    $args['cat'] = $cat;
  }

  if ( ! empty( $year ) ) {
    $args['year'] = $year;
  }

  if ( ! empty( $monthnum ) ) {
    $args['monthnum'] = $monthnum;
  }

  if ( ! empty( $day ) ) {
    $args['day'] = $day;
  }

  if ( ! empty( $author ) ) {
    $args['author'] = $author;
  }

  $loop = new \WP_Query( $args );
  if ( $loop->have_posts() ) :
    while ( $loop->have_posts() ) : $loop->the_post();
      ob_start();
      get_template_part( esc_attr( $a['template'] ) );
      $template = ob_get_contents();
      ob_end_clean();
      $out .= $template;
      unset( $template );
    endwhile;
  endif;

  $out .= '</div>';

  wp_reset_postdata();

  return $out;
}
add_shortcode( 'fcipl-block-post', __NAMESPACE__ . '\block_post_shortcode' );

/**
 * Creates the indicator html code.
 *
 * @since 0.1
 *
 * @param array $atts Shortcode attributes.
 * @return string Indicator html code.
 */
function indicator_shortcode( $atts ) {
  $a = shortcode_atts( array(
    'class'     => 'load-indicator is-in',
    'data-load' => 'loading',
    'template'  => 'includes/svg/progress'
  ), $atts );

  ob_start();
  get_template_part( esc_attr( $a['template'] ) );
  $template = ob_get_contents();
  ob_end_clean();

  return '<div class="' . esc_attr( $a['class'] ) . '" data-load="' . esc_attr( $a['data-load'] ) . '">' . $template . '</div>';
}
add_shortcode( 'fcipl-indicator', __NAMESPACE__ . '\indicator_shortcode' );
?>
