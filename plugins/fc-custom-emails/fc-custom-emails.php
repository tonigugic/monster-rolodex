<?php
/**
 * Plugin Name: Custom Emails
 * Description: Adds custom notification emails.
 * Author: Fairhead Creative
 * Author URI: https://builtforimpact.net
 * Version: 0.1
 * License: GPLv2 or later
 */

/**
 * Sends a confirmation request email to a user when they sign up for a new user account.
 *
 * @since 0.1
 *
 * @param string $user_login User login name.
 * @param string $user_email User email address.
 * @param string $key        Activation key created in wpmu_signup_user().
 * @param array  $meta       Signup meta data. Default empty array.
 * @return bool
 */
add_filter( 'wpmu_signup_user_notification', function ( $user_login, $user_email, $key, $meta ) {
  $user = get_user_by( 'login', $user_login );
  $switched_locale = switch_to_locale( get_user_locale( $user ) );
  $admin_email = get_option( 'admin_email' );
  $from_name = esc_html( get_option( 'blogname' ) );
  $message_headers = "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
  $message = sprintf(
    /**
     * Filters the content of the notification email for new user sign-up.
     *
     * Content should be formatted for transmission via wp_mail().
     *
     * @since MU (3.0.0)
     *
     * @param string $content    Content of the notification email.
     * @param string $user_login User login name.
     * @param string $user_email User email address.
     * @param string $key        Activation key created in wpmu_signup_user().
     * @param array  $meta       Signup meta data. Default empty array.
     */
    apply_filters( 'wpmu_signup_user_notification_email', '', $user_login, $user_email, $key, $meta ),
    site_url( "wp-activate.php?key=$key" )
  );
  $subject = '[' . $from_name . '] Activate ' . $user_login;
  wp_mail( $user_email, wp_specialchars_decode( $subject ), $message, $message_headers );

  if ( $switched_locale ) {
    restore_previous_locale();
  }

  return false;
}, 10, 4 );
?>