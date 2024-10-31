<?php


/*
		GLOBALS
*/

$scf_field_types = array(
	'input-text' => (object)array(
		'label' => 'Textinput',
		'render' => 'scf_render_input_text'
	),
	'textarea' => (object)array(
		'label' => 'Textarea',
		'render' => 'scf_render_textarea'
	),
	'select' => (object)array(
		'label' => 'Select',
		'render' => 'scf_render_select'
	),
	'radio' => (object)array(
		'label' => 'Radios',
		'render' => 'scf_render_checkbox_radio',
		'element_container_class' => 'scf-radios'
	),
	'checkbox' => (object)array(
		'label' => 'Checkboxes',
		'render' => 'scf_render_checkbox_radio',
		'element_container_class' => 'scf-checkboxes'
	)
	
);

$scf_mandatory_fields = array(
	'name' => 'input-text:Name',
	'email' => 'input-text:E-Mail',
	#'message' => 'textarea:Message'
);

$scf_default_options = array(
	'scf_fields' => join( '~', array(
		join( ':', array(
			'name', 0, true, 'input-text', '', 'Name'
		) ),
		join( ':', array(
			'email', 1, true, 'input-text', '', 'E-Mail'
		) ),
		join( ':', array(
			'subject', 2, false, 'input-text', '', 'Subject'
		) ),
		join( ':', array(
			'message', 3, true, 'textarea', '', 'Message'
		) )
	) ),
	'scf_use_captcha' => true,
	'scf_recipient_email' => 'you@example.com',
	'scf_recipient_subject' => 'Contact Request from your Website!',
	'scf_success_message' => 'Thanks for your comments!',
	'scf_error_message' => 'Please fill in the required fields marked with <em>*</em>',
	'scf_captcha_error' => 'Sorry, the Captcha didnt fit',
	'scf_identifier_error' => 'Did you hit reload? I\'ve got your request already ..',
	'scf_captcha_label' => 'Please type in the captcha',
	'scf_submit_value' => 'Submit Request',
	'scf_send_confirmation' => 'no',
	'scf_confirmation_subject' => 'Thanks for your request!',
	'scf_confirmation_body' => "Hello %NAME%,\n\nThanks for you request. I will look into it and get back to you\n\nCheers"
);

$scf_captcha_inited = false;
$scf_identifier = null;


/*
		OPTIONS
*/

function scf_init_options( $resettable = false ) {
	global $scf_mandatory_fields, $scf_default_options;
	$mandatory_fields = array_merge( array(), $scf_mandatory_fields );
	
	
	$options = array();
	foreach ( $scf_default_options as $n => $v ) {
		
		// init default value for option
		if ( $resettable && isset( $_GET[ 'reset' ] ) && $_GET[ 'reset' ] == '1' ) delete_option( $n );
		add_option( $n, $v );
		
		// read current ootion
		$options[ $n ] = stripslashes( get_option( $n ) );
	}
	
	$fields_pre = split( '~', $options[ 'scf_fields' ] );
	$fields = array();
	$max_num = 0;
	foreach ( $fields_pre as $pre ) {
		$f = split( ':', $pre, 6 );
		$values = split( ';', $f[4] );
		
		// remove mandatory field from list ..
		$mandatory = false;
		if ( isset( $mandatory_fields[ $f[0] ] ) ) {
			$mandatory = true;
			$f[2] = true;
			unset( $mandatory_fields[ $f[0] ] );
		}
		
		// determine max num for saving next ..
		elseif ( preg_match( '/^custom_(\d+)$/', $f[0], $m ) ) {
			if ( $m[1] > $max_num )
				$max_num = $m[1];
		}
			
		// this is easier to use ..
		$fields []= (object)array(
			'name' => $f[0],
			'position' => $f[1],
			'required' => $f[2],
			'mandatory' => $mandatory,
			'type' => $f[3],
			'values' => $values,
			'label' => $f[5],
		);
	}
	
	// store maximum custom num ..
	$options[ 'scf_max_custom' ] = $max_num;
	
	// append all mandatory fields!
	foreach ( $mandatory_fields as $name => $v ) {
		$f = split( ':', $v, 2 );
		$fields []= (object)array(
			'name' => $name,
			'position' => 0,
			'required' => true,
			'mandatory' => true,
			'type' => $f[0],
			'values' => array(),
			'label' => $f[1],
		);
	}
	usort( $fields, 'scf_sort_fields' );
	
	$options[ 'scf_count_fields' ] = count( $fields );
	$options[ 'scf_fields' ] = $fields;
	
	return $options;
}









/*
		FORMULAR
*/


function scf_init_form( $options ) {
	global $scf_identifier;
	$newCaptcha = scf_get_captcha_instance();
	
	// decide wheter use captcha
	
	$print_captcha = $options[ 'scf_use_captcha' ] == 1 ? 1 : 0;
	
	$result = array( 'use_captcha' => $print_captcha, 'try_save' => false );
	
	// check / send contact fomular
	if ( !empty( $_POST ) && ! @empty( $_POST[ 'scf_identifier' ] ) ) {
		@session_start();	
		
		// user want's to save / send
		$result[ 'try_save' ] = true;
		
		// no valid identifier.. repost ?
		if ( @empty( $_SESSION[ 'scf_identifier' ] ) || $_POST[ 'scf_identifier' ] != $_SESSION[ 'scf_identifier' ] ) {
			$result[ 'form_error' ]     = true;
			$result[ 'result_class' ]   = 'scf-error scf-identifier-error';
			$result[ 'result_message' ] = $options[ 'scf_identifier_error' ];
			return (object)$result;
		}
		
		// get new identifier .. one per post!
		$_SESSION[ 'scf_identifier' ] = scf_get_identifier( true );
		
		// default captcha result to OK ..
		$captcha_ok = true;
	
		// check the captcha, if enabled and installed
		if ( $print_captcha && ! is_null( $newCaptcha ) ) {
		
			// get pubkey and captcha
			$publicKey = $_POST['scf_captcha'];
			$secretKey = $_SESSION['secret'];
			
			// remove captcha.. one time usage
			$_SESSION['secret'] = '';
			
			// check captcha
			$captcha_ok = $newCaptcha->validateKey($publicKey, $secretKey);
		}
		
		// captcha doesnt fit ..
		if ( ! $captcha_ok ) {
			$result[ 'form_error' ]     = true;
			$result[ 'result_class' ]   = 'scf-error scf-captcha-error';
			$result[ 'result_message' ] = $options[ 'scf_captcha_error' ];
		}
		
		// captcha OK
		else {
			
			// check now form and try sending .. if succeeded -> print success and return
			if ( scf_check_and_send_form( $options, $_POST ) ) {
				$result[ 'form_error' ]     = false;
				$result[ 'result_class' ]   = 'scf-form-success';
				$result[ 'result_message' ] = $options[ 'scf_success_message' ];
			}
			
			// or print error and print form
			else {
				$result[ 'form_error' ]     = true;
				$result[ 'result_class' ]   = 'scf-error scf-form-error';
				$result[ 'result_message' ] = $options[ 'scf_error_message' ];
			}
		}
	}
	
	elseif ( is_null( $scf_identifier ) ) {
		@session_start();
		scf_get_identifier();
	}
	
	
	return (object)$result;
}


function scf_print_contact_form( $content = "" ) {
	global $scf_field_types;
	global $scf_identifier;
	$newCaptcha		= scf_get_captcha_instance();
	$options		= scf_init_options();
	$form			= scf_init_form( $options );
	$permalink		= get_permalink();
	
	
	$captcha_url	= scf_check_simple_captcha();
	$print_captcha	= $captcha_url !== null && $options[ 'scf_use_captcha' ] == 1;
	$str = <<<HTML
<div class="scf-form">
HTML;
	if ( $form->try_save && ! $form->form_error )
		$str .= <<<HTML
	<div class="$form->result_class">
		$form->result_message
	</div>
HTML;
	else {
		$str .= <<<HTML
	<form action="$permalink" method="post">
		<input type="hidden" name="scf_identifier" value="$scf_identifier" />
HTML;
		if ( $form->try_save && $form->form_error )
			$str .= <<<HTML
		<div class="$form->result_class">
			$form->result_message
		</div>
HTML;

		
		foreach ( $options[ 'scf_fields' ] as $field ) {
			$str .= <<<HTML
		<div class="form-row">
HTML;
			$str .= call_user_func( $scf_field_types[ $field->type ]->render, $field );
			$str .= <<<HTML
		</div>
HTML;
		}

		if ( $print_captcha ) {
			$bloginfo_url = get_bloginfo('url');
			$str .= <<<HTML
		<div class="scf-captcha-container">
			<div class="scf-captcha">
				<img id="simple_captcha" src="$bloginfo_url/wp-content/plugins/$captcha_url/gdimg.php?re=0" title="Simple CAPTCHA v{$newCaptcha->version} by zorex" alt="" />
			</div>
			<div class="form-label">
				<label for="scf_captcha">
					{$options[ 'scf_captcha_label' ]}
					<span class="required">*</span>
				</label>
			</div>
			<div class="form-input">
				<input type="text" class="text" id="scf_captcha" name="scf_captcha" value="" />
			</div>
		</div>
HTML;
		}

		$str .= <<<HTML
		<div class="form-submit">
			<input type="submit" name="submit" value="{$options[ 'scf_submit_value' ]}" />
		</div>
	</form>
HTML;
	}
	$str .= <<<HTML
</div>
HTML;
	return $str;
}


function scf_print_contact_form_ajax( $content = "" ) {
	$str = <<<HTML
	<div class="scf-ajax-form">
		<div class="scf-ajax-ok" style="display: none">
		</div>
		<div class="scf-ajax-error" style="display: none">
		</div>
		<div class="scf-ajax-loading" style="display: none">
			Loading
		</div>
HTML;
	$str .= scf_print_contact_form( $content );
	$str .= <<<HTML
	</div>
	<script type="text/javascript">
	<!--
	function log( msg ) { if ( window.DEBUG === true ) console.debug( msg ) }
	jQuery( function($) {
		$( '.scf-ajax-form form input[type=submit]' ).click( function() {
			var form = $( this ).parents( 'form' ).get(0);
			var data = {};
			$( 'input,select,textarea', form ).each( function() {
				var n = $( this ).attr( 'name' ), v = $( this ).val();
				if ( n !== undefined && n !== null && n != '' ) data[ n ]= v;
			} );
			data.json = 1;
			
			$( '.scf-ajax-form form,.scf-ajax-ok,.scf-ajax-error' ).slideUp();
			$( '.scf-ajax-loading' ).slideDown();
			
			$.post( $( form ).attr( 'action' ), data, function( text ) {
				var res = $( '.scf-captcha-error,.scf-form-error,.scf-form-success', text );
				$( '.scf-ajax-loading' ).slideUp();
				if ( res.length > 0 ) {
					res = $( res.get(0) );
					if ( res.hasClass( 'scf-captcha-error' ) || res.hasClass( 'scf-form-error' ) ) {
						$( '.scf-ajax-error' ).empty().append( res ).slideDown();
						$( '.scf-ajax-form form' ).slideDown();
					}
					else {
						$( '.scf-ajax-form .scf-ajax-ok' ).empty().append( res ).slideDown();
					}
				}
			} );
			return false;
		} );
	} );
	//-->
	</script>
HTML;
	return $str;
}

function scf_render_label_simple( $field ) {
	$str = <<<HTML
	<div class="form-label">
		<label for="scf_field_$field->name">
			$field->label
HTML;
	
	if ( $field->required )
			$str .= <<<HTML
			<em class="required">*</em>
HTML;
	$str .= <<<HTML
		</label>
	</div>
HTML;
	return $str;
}

function scf_render_input_text( $field ) {
	$value = isset( $_POST[ 'scf_field_'. $field->name ] )
		? $_POST[ 'scf_field_'. $field->name ]
		: ''
	;
	$required = $field->required ? 'required' : '';
	$str = scf_render_label_simple($field);
	$str .= <<<HTML
	<input type="text" value="$value" class="text $required" name="scf_field_{$field->name}" id="scf_field_$field->name" />
HTML;
	return $str;
}


function scf_render_textarea( $field ) {
	$value = isset( $_POST[ 'scf_field_'. $field->name ] ) ? $_POST[ 'scf_field_'. $field->name ] : '';
	$required = $field->required ? 'required' : '';
	$str = scf_render_label_simple($field);
	$str .= <<<HTML
	<textarea rows="4" cols="40" class="$required" name="scf_field_$field->name" id="scf_field_$field->name">$value</textarea>
HTML;
	return $str;
}


function scf_render_select( $field ) {
	$value = isset( $_POST[ 'scf_field_'. $field->name ] ) ? $_POST[ 'scf_field_'. $field->name ] : '';
	$required = $field->required ? 'required' : '';
	$str = scf_render_label_simple($field);
	$str .= <<<HTML
	<select name="scf_field_$field->name" id="scf_field_$field->name" class="$required">
HTML;
	foreach ( $field->values as $select_value ) {
		$selected = $value == $select_value ? ' selected="selected"' : '';
		$str .= <<<HTML
		<option value="$select_value" $selected>
			$select_value
		</option>
HTML;
	}
	return "$str</select>";
}

function scf_render_checkbox_radio( $field ) {
	global $scf_field_types;
	$values = isset( $_POST[ 'scf_field_'. $field->name ] ) ? $_POST[ 'scf_field_'. $field->name ] : '';
	$required = $field->required ? '<em class="required">*</em>' : '';
	$value_checked = array();
	if ( !empty( $values ) )
		foreach ( $values as $value )
			$value_checked[ $value ] = true;
	
	$str = <<<HTML
	<fieldset class="{$scf_field_types[ $field->type ]->element_container_class}">
		<legend>
			$field->label
			$required
		</legend>
		<ol>
HTML;

	foreach ( $field->values as $i => $select_value ) {
		$field_id = "scf_field_" . $field->name . $i;
		$checked = $value_checked[ $select_value ] ? ' checked="checked"' : '';
		$str .= <<<HTML
			<li>
				<label>
					<input class="scf-radio" type="$field->type" value="$select_value" id="scf_field_{$field->name}_$i" name="scf_field_{$field->name}[]" $checked />
					<span>$select_value</span>
				</label>
			</li>
HTML;
	}
	
	$str .= <<<HTML
		</ol>
	</fieldset>
HTML;
	return $str;
}





/*
		SAVE FORM
*/

function scf_check_and_send_form( $options, $data ) {
	
	// build the mail and check all required attributes
	$mail = array();
	$from_mail = '';
	$from_name = '';
	foreach ( $options[ 'scf_fields' ] as $field ) {
		$value = isset( $data[ 'scf_field_'. $field->name ] )
			? $data[ 'scf_field_'. $field->name ]
			: ""
		;
		
		// collect the sender's email and name
		if ( $field->name == 'email' )
			$from_mail = $value;
		if ( $field->name == 'name' )
			$from_name = $value;
		
		// oops, missing reuired!
		if ( empty( $value ) && $field->required )
			return false;
		
		// add toe mail
		$mail []= "*** ". $field->label. " ***";
		if ( is_array( $value ) ) {
			foreach ( $value as $row )
				$mail []= " * $row";
		}
		else
			$mail []= "$value";
		
		// empty line after ..
		$mail []= "";
		$mail []= "";
	}
	
	// now send the mail
	wp_mail( $options[ 'scf_recipient_email' ], $options[ 'scf_recipient_subject' ], join( "\r\n", $mail ) );
	
	
	// send confirmation mail ??
	if ( !@empty( $options[ 'scf_send_confirmation' ] ) && $options[ 'scf_send_confirmation' ] == 'yes' && ! empty( $from_mail ) ) {
		
		$subject = preg_replace( '/%NAME%/m', $from_name, $options[ 'scf_confirmation_subject' ] );
		$body = preg_replace( '/%NAME%/m', $from_name, $options[ 'scf_confirmation_body' ] );
		
		wp_mail( $from_mail, $subject, $body );
	}
		
	return true;
}









/*
		MISC / HELPER
*/

function scf_check_simple_captcha() {
	global $scf_captcha_inited;
	
	// already loaded ?
	if ( $scf_captcha_inited !== false )
		return $scf_captcha_inited;
	
	// try load ..
	foreach ( array( 'simple-captcha/simpleCAPTCHA', 'simpleCAPTCHA' ) as $path ) {
		$captcha_path = WP_CONTENT_DIR . '/plugins/'. $path . '/zrx_captcha.inc.php';
		if ( file_exists( $captcha_path ) ) {
			include_once( $captcha_path );
			$scf_captcha_inited = $path;
			return $path;
		}
	}
	return null;
}




function scf_sort_fields( $a, $b ) {
	return is_object( $a )
		? ( $a->position == $b->position
			? $a->label < $b->label ? -1 : 1 // alphabetic ascending if position same
			: $a->position < $b->position ? -1 : 1 // ascending by position
		)
		: ( $a[ 'position' ] == $b[ 'position' ]
			? $a[ 'label' ] < $b[ 'label' ] ? -1 : 1 // alphabetic ascending if position same
			: $a[ 'position' ] < $b[ 'position' ] ? -1 : 1 // ascending by position
		)
	;
}


function scf_get_captcha_instance() {
	global $newCaptcha;
	
	// already loaded ?
	if ( $newCaptcha !== null )
		return $newCaptcha;
	
	// check wheter loadable
	$loadable = scf_check_simple_captcha();
	if ( $loadable === null )
		return null;
	
	// try load captcha
	if ( ( ! isset( $newCaptcha ) || is_null( $newCaptcha ) ) ) {
		if ( class_exists( 'zrx_captcha' ) )
			$newCaptcha = new zrx_captcha();
		else
			error_log( "Captcha is enabled but class 'zrx_captcha' could not be loaded!" );
	}
	
	return $newCaptcha;
}


function scf_get_identifier( $force = false ) {
	global $scf_identifier;
	if ( @empty( $_SESSION[ 'scf_identifier' ] ) || $force ) {
		$scf_identifier	= md5( wp_generate_password() );
		$_SESSION[ 'scf_identifier' ] = $scf_identifier;
	}
	else {
		$scf_identifier = $_SESSION[ 'scf_identifier' ];
	}
	
	return $scf_identifier;
}





?>
