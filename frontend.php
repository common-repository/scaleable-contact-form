<?php
/**
 * @package Scaleable Contact Form
 * @author Ulrich Kautz
 * @version 0.8.2
 */
/*
Plugin Name: Scaleable Contact Form
Plugin URI: http://blog.foaa.de/scaleable-contact-form
Description: Another Contact Form with very scalable multi-type Fields. Uses Captcha, no Akismet. Can use external SMTP via wp_mail() and other PLugins. AJAX Support!
Author: Ulrich Kautz
Version: 0.8.2
Author URI: http://fortrabbit.de
Thanks to: Jonathan Rogers
*/
//ini_set( 'display_errors', true );

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes.php' );

// add the contact form by adding [scaleable-contact-form] in non-visual (aka html) editor:
//	in a page and / or a site
add_shortcode( 'scaleable-contact-form-ajax', 'scf_print_contact_form_ajax' );
add_shortcode( 'scaleable-contact-form', 'scf_print_contact_form' );



// announce the menu item for admin..
add_action( 'admin_menu', 'scf_init_admin_menu' );


// assure jquery
function scf_init() {
	wp_enqueue_script('jquery');
}
add_action('init', scf_init);

function scf_init_admin_menu() {
	$path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__).'/');
	add_options_page( 'Scaleable Contact Form', 'S.C.Form', 'manage_options', $path. '/admin.php', '' );
}



?>
