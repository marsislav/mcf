<?php
/*
Plugin Name: Marsislav`s Contact Form
Plugin URI:https://marsislav.net
Description: Very simple contact form plugin for Wordprerss. Just use  [contact] shortcode where you want to use contact form.
Version: 1.0
Author: marsislav
Author URI: https://marsislav.net
*/

/**
 * MARSISLAV`S  CONTACT FORM
 * Primary class file
 */
if( !class_exists( 'marsislavContactForm' ) ) : // collision check
class marsislavContactForm {

/**
 * CONSTRUCTOR
 * Runs automatically on class init
 */
function __construct() {
	/** enqueue stylesheet */
	add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_stylesheet' ) );

	/** load textdomain for internationalization */
	add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ) );

	/** register [contact] shortcode */ 
	add_shortcode( 'contact', array( &$this, 'shortcode' ) );

	/** show captcha information (unless closed) */
	if( !get_option( 'mcf-captcha-info' ) )
		add_action( 'wp_dashboard_setup', array( &$this, 'captcha_info' ) ); // setup dashboard
}

/**
 * SHORTCODE
 * Actual HTML output for the [contact] shortcode
 */
function shortcode() {
	/** if form was not yet submitted: */
	if( !array_key_exists( 'submit', $_POST ) ) 
		return $this->draw_form(); // draw the contact form

	/** if form was submitted (without email) */
	elseif( empty( $_POST['mcf_from'] ) )
		return $this->draw_form( __( 'Въведете Вашата електронна поща!', 'marsislav-contact-form' ) ); // redraw w/ error msg

	/** if form was submitted (without a message) */
	elseif( empty( $_POST['mcf_message'] ) )
		return $this->draw_form( __( 'Въведете съобщение!', 'marsislav-contact-form' ) ); // redraw w/ error msg

	/** if form was submitted (properly) */
	else 
		return $this->send_email(); // send the email, show OK message	
}

// SEND EMAIL
function send_email() { 
	$args = array(); // init blank arguments array

	/** (TO) send email to */
	$args['to'] = get_option( 'admin_email' ); 

	/** (PREFIX) prefix for subject line */
	$args['prefix'] = '[Marsislav.NET] ';

	/** (SUBJECT) use default if no subject given */
	$args['subject'] = ( empty( $_POST['mcf_subject'] )
		? __( '(no subject)', 'marsislav-contact-form' ) // (no subject)
		: $_POST['mcf_subject'] );

	/** (NAME) use blank if no name given */
	$args['name'] = ( empty( $_POST['mcf_name'] ) 
		? '' // blank value without trailing space
		: $_POST['mcf_name'] . ' ' ); // name with trailing space

	/** (FROM) required field */
	$args['from'] = $_POST['mcf_from'];

	/** (MESSAGE) required field */
	$args['message'] = $_POST['mcf_message'];

	/** build the email headers */
	$args['headers'] = sprintf( 'From: %1$s<%2$s>' . "\r\n", 
		$args['name'], 
		$args['from'] );

	/** mail it */
	mail( $args['to'], $args['prefix'] . $args['subject'], $args['message'], $args['headers'] );

	/** wp_mail it */
	// wp_mail($args['to'], $args['subject'], $args['message'], $args['headers'] );

	return '<p class="mcf-report">' . __( 'Your message has been sent!', 'marsislav-contact-form' ) . '</p>';
}

function draw_form( $notify='' ) { 
	/** translated labels */
	$labels = array(
		'name' => __( 'Your name:', 'marsislav-contact-form' ),
		'from' => __( 'Your email:', 'marsislav-contact-form' ),
		'subject' => __( 'Subject:', 'marsislav-contact-form' ),
		'message' => __( 'Message:', 'marsislav-contact-form' ),
		'notify' => ( empty( $notify ) 
			? '' 
			: '<div class="mcf-notify"><span>' . $notify . '</span></div>' )
	);

	/** sanitized values */
	$values = array(
		'name' => ( isset( $_POST['mcf_name'] ) 
			? $_POST['mcf_name']
			: '' ),
		'from' => ( isset( $_POST['mcf_from'] ) 
			? $_POST['mcf_from']
			: '' ),
		'subject' => ( isset( $_POST['mcf_subject'] ) 
			? $_POST['mcf_subject']
			: '' ),
		'message' => ( isset( $_POST['mcf_message'] ) 
			? $_POST['mcf_message']
			: '' )
	);

	/** extra classes */
	$class = array(
		'from' => ( empty( $_POST['mcf_from'] ) && array_key_exists( 'submit', $_POST ) 
			? 'class="mcf-forgot" ' // trailing space
			: '' ),
		'message' => ( empty( $_POST['mcf_message'] ) && array_key_exists( 'submit', $_POST ) 
			? 'class="mcf-forgot" ' // trailing space
			: '' )
	);

	// build return string
	return '
<!-- Marsislav`s Contact Form -->


<div class="mcf-wrapper">
	<form action="" method="post">
		' . $labels['notify'] . '
		<p id="mcf-name-wrapper" class="mcf-input-wrapper">
			<label for="mcf_name">' . $labels['name'] . '</label>
			<input type="text"  placeholder="..." required name="mcf_name" id="mcf_name" value="' . $values['name'] . '"   />
		</p>

		<p id="mcf-from-wrapper" class="mcf-input-wrapper">
			<label for="mcf_from">' . $labels['from'] . '</label>
			<input ' . $class['from'] . 'type="email" required name="mcf_from" id="mcf_from" placeholder="...@..." value="' . $values['from'] . '" />
			</p>

		<p id="mcf-subject-wrapper" class="mcf-input-wrapper">
			<label for="mcf_subject">' . $labels['subject'] . '</label>
			<input type="text" name="mcf_subject" required placeholder="..." id="mcf_subject" value="' . $values['subject'] . '" />
		</p>

		<p id="mcf-message-wrapper" class="mcf-input-wrapper">
			<label for="mcf_message">' . $labels['message'] . '</label>
			<textarea ' . $class['message'] . 'name="mcf_message" placeholder="..." required id="mcf_message" cols="45" rows="5">' . $values['message'] . '</textarea>
		</p>
		<p class="mcf-clear"></p>
		<p id="mcf-submit-wrapper">
			<button type="submit" name="submit" id="submit" value="Изпрати" class="mcf-submit viewAll">Изпрати</button>
		</p>

		<p class="mcf-clear"></p>

	</form>
</div><!-- /.mcf-wrapper -->

<!-- // Marsislav Contact Form -->
';
}

} // end class
endif; // end collision check

/** NEW INSTANCE GET! */
new marsislavContactForm;
?>
