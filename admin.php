<?php
/**
 * @package Scaleable Contact Form
 * @author Ulrich Kautz
 * @version 0.8.1
 */
/*
Author: Ulrich Kautz
Version: 0.8.1
Author URI: http://fortrabbit.de
Thanks to: Jonathan Rogers
*/

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes.php' );

// save formular
if ( !empty( $_POST ) )
	scf_save_form( $_POST );

// print formular
scf_print_admin_form();




/*
		ADMIN
*/


function scf_save_form( $data ) {
	global $scf_mandatory_fields;
	
	// all options, but fields
	foreach ( array( 'scf_use_captcha', 'scf_recipient_email', 'scf_recipient_subject', 'scf_captcha_error', 'scf_captcha_label', 'scf_identifier_error', 'scf_error_message', 'scf_success_message', 'scf_submit_value', 'scf_send_confirmation', 'scf_confirmation_subject', 'scf_confirmation_body' ) as $optname ) {
		$val = isset( $data[ $optname ] ) ? $data[ $optname ] : 0;
		update_option( $optname, $val );
	}
	
	$fields = array();
	foreach ( $data[ 'scf_field' ] as $field ) {
		
		// delete field:
		if ( empty( $data[ 'scf_field_'. $field. '_label' ] ) && ! isset( $scf_mandatory_fields[ $field ] ) )
			continue;
		
		// get type ..
		$type;
		if ( isset( $scf_mandatory_fields[ $field ] ) ) {
			$tl = split( ':', $scf_mandatory_fields[ $field ] );
			$type = $tl[0];
		}
		else
			$type = $data[ 'scf_field_'. $field. '_type' ];
		
		// init values:
		$values = '';
		if ( in_array( $type, array( 'select', 'checkbox', 'radio' ) ) ) {
			$values = preg_replace( '/[;:]/', '', $data[ 'scf_field_'. $field. '_'. $type . '_values' ] );
			$values = join( ";", preg_split( "/\s*[\n;]\s*/", $values ) );
		}
			
		
		$fields []= array(
			'name' => $field,
			'position' => (int)$data[ 'scf_field_'. $field. '_position' ],
			'required' => isset( $data[ 'scf_field_'. $field. '_required' ] ) || isset( $scf_mandatory_fields[ $field ] ) ? 1 : 0,
			'type' => $type,
			'values' => $values,
			'label' => $data[ 'scf_field_'. $field. '_label' ],
		);
	}
	
	usort( $fields, 'scf_sort_fields' );
	$save_fields = array();
	foreach ( $fields as $field )
		$save_fields []= preg_replace( '/~/', '', join( ':', array_values( $field ) ) );
	update_option( 'scf_fields', join( '~', $save_fields ) );
}


function scf_print_admin_form() {
	$options = scf_init_options( true );
	$action  = get_option('siteurl') . '/wp-admin/admin.php?page='.dirname(__FILE__).'/admin.php'; // Form Action URI
	
	$captcha_ok = scf_check_simple_captcha();
	
?>
<div class="wrap">
	<h2>Scaleable Contact Form</h2>
	<style type="text/css">
		.scf-hidden {
			display: none;
		}
		.scf-field textarea {
			width: 325px;
			display: block;
		}
		.scf-field label span {
			font-size: xx-small;
		}
	</style>
	<script type="text/javascript">
	<!--
	jQuery( function( $ ) {
		$( '.scf-field' ).each( function() {
			var field = this;
			$( '.scf-choose-type', field ).change( function() {
				$( '.scf-hidden', field ).hide();
				$( '.scf-field-' + $( this ).val(), field ).show();
			} ).trigger( 'change' );
		} );
		$( '#scf_add_field' ).click( function() {
			var fields = $( '.scf-field.scf-hidden' );
			if ( fields.length > 0 ) {
				$( fields[0] ).removeClass( 'scf-hidden' );
			}
			if ( fields.length <= 1 )
				$( this ).remove();
		} );
		$( '#scf_send_confirmation' ).change( function() {
			if ( $( this ).is( ':checked' ) )
				$( '.with_confirmation_mail' ).fadeIn();
			else
				$( '.with_confirmation_mail' ).fadeOut();
		} ).trigger( 'change' );
		$( '#scf_use_captcha' ).change( function() {
			if ( $( this ).is( ':checked' ) )
				$( '.with_captcha' ).fadeIn();
			else
				$( '.with_captcha' ).fadeOut();
		} ).trigger( 'change' );
		
	} );
	//-->
	</script>
	<form action="<?php echo $action ?>" method="post">
		<h3>Options</h3>
		<table class="form-table" style="margin-bottom: 10px; border-bottom: 1px dashed #ccc" >
			<tbody>
				<tr>
					<th scope="row">
						<label for="scf_recipient_email">
							Recipient E-Mail (you)
						</label>
					</th>
					<td>
						<input type="text" class="regular-text" value="<?php echo $options[ 'scf_recipient_email' ] ?>" id="scf_recipient_email" name="scf_recipient_email"/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="scf_recipient_subject">
							E-Mail Subject
						</label>
					</th>
					<td>
						<input type="text" class="regular-text" value="<?php echo $options[ 'scf_recipient_subject' ] ?>" id="scf_recipient_subject" name="scf_recipient_subject"/>
						<br/>
						<small>For the Mail YOU receive!</small>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="scf_submit_value">
							Submit Button Value
						</label>
					</th>
					<td>
						<input type="text" class="regular-text" value="<?php echo $options[ 'scf_submit_value' ] ?>" id="scf_submit_value" name="scf_submit_value"/>
						<br/>
						<small>The title of the button.. Eg: "Send request" or something...</small>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="scf_use_captcha">
							Use Captcha
						</label>
					</th>
					<td>
						<input type="checkbox" class="regular-text" value="1" id="scf_use_captcha" name="scf_use_captcha" <?php if ( $options[ 'scf_use_captcha' ] ) echo ' checked="checked"' ?> /> Yes
						<?php if ( $captcha_ok !== null ): ?>
						<span style="color: green; padding-left: 10px;">
							(Simple CAPTCHA is installed!)
						</span>
						<?php else: ?>
						<span style="color: red; font-weight: bold; padding-left: 10px;">
							(Simple CAPTCHA is NOT installed! Please install via Wordpress Plugin installer!)
						</span>
						<?php endif; ?>
					</td>
				</tr>
				<tr class="with_captcha">
					<th scope="row">
						<label for="scf_captcha_label">
							Captcha Label
						</label>
					</th>
					<td>
						<input type="text" class="regular-text" id="scf_captcha_label" name="scf_captcha_label" value="<?php echo $options[ 'scf_captcha_label' ] ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		
		
		<h3>Confirmation Mail</h3>
		<table class="form-table" style="margin-bottom: 10px; border-bottom: 1px dashed #ccc" >
			<tbody>
				
				<tr>
					<th scope="row">
						<label for="scf_send_confirmation">
							Send confirmation mail
						</label>
					</th>
					<td>
						<input type="checkbox" class="regular-text" id="scf_send_confirmation" name="scf_send_confirmation" value="yes" <?php if ( $options[ 'scf_send_confirmation' ] == "yes" ) echo 'checked="checked"'; ?> />
						Yes
						<br/>
						<small><strong>Beware:</strong> Using this option, you might end up sending spam yourself, if somone has a funny moment and enters other peoples E-Mail addresses in the form. He might not be able to send any spammy content, but in the end, you might still send a lot of mails to people who don't want that.. </small>
					</td>
				</tr>
				<tr class="with_confirmation_mail">
					<th scope="row">
						<label for="scf_confirmation_subject">
							Confirmation mail subject
						</label>
					</th>
					<td>
						<input style="width: 80%;" type="text" class="regular-text" id="scf_confirmation_subject" name="scf_confirmation_subject" value="<?php echo $options[ 'scf_confirmation_subject' ]; ?>" />
						<br/>
						<small>You can use %NAME% as variable for the name.. eg "Hello %NAME%"</small>
					</td>
				</tr>
				<tr class="with_confirmation_mail">
					<th scope="row">
						<label for="scf_confirmation_body">
							Confirmation body
						</label>
					</th>
					<td>
						<textarea style="width: 80%;" rows="5" id="scf_confirmation_body" name="scf_confirmation_body"><?php echo $options[ 'scf_confirmation_body' ] ?></textarea>
						<br/>
						<small>You can use %NAME% as variable for the name.. eg "Hello %NAME%"</small>
					</td>
				</tr>
			</body>
		</table>
		
		<h3>Messages</h3>
		<table class="form-table" style="margin-bottom: 10px; border-bottom: 1px dashed #ccc" >
			<tbody>
				<tr>
					<th scope="row">
						<label for="scf_success_message">
							Success Message
						</label>
					</th>
					<td>
						<textarea cols="50" rows="4" style="width: 80%;" id="scf_success_message" name="scf_success_message"><?php echo $options[ 'scf_success_message' ] ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="scf_success_message">
							Error Message
						</label>
					</th>
					<td>
						<textarea cols="50" rows="4" style="width: 80%;" id="scf_error_message" name="scf_error_message"><?php echo $options[ 'scf_error_message' ] ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="scf_captcha_error">
							Captcha Error Message
						</label>
					</th>
					<td>
						<textarea cols="50" rows="4" style="width: 80%;" id="scf_captcha_error" name="scf_captcha_error"><?php echo $options[ 'scf_captcha_error' ] ?></textarea>
						<br />
						<small>Shown, if the captcha has been inputed not at all or incorrect.</small>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="scf_url_success">
							Reposting Error
						</label>
					</th>
					<td>
						<textarea cols="50" rows="4" style="width: 80%;" id="scf_identifier_error" name="scf_identifier_error"><?php echo $options[ 'scf_identifier_error' ] ?></textarea>
						<br />
						<small>Shown, if the user hit's reload after sending the contact request once.</small>
					</td>
				</tr>
			</tbody>
		</table>
		
		
		<h3>Fields</h3>
<?php
	foreach( $options[ 'scf_fields' ] as $field ) {
		scf_print_admin_form_field( $field );
	}
	foreach( range( 1, 10 ) as $add ) {
		scf_print_admin_form_field( (object)array(
			'name' => 'custom_'. ( $options[ 'scf_max_custom' ] + $add ),
			'position' => $options[ 'scf_count_fields' ] + $add,
			'required' => false,
			'mandatory' => false,
			'type' => 'input-text',
			'values' => array(),
			'label' => '',
			'additional' => true
		) );
	}
?>

	<p id="scf_add_field" class="button">
		+ Add Field
	</p>

	<p class="submit">
		<input type="submit" name="submit" value="Save all &raquo;" />
	</p>
	</form>
</div>
<?php
}



function scf_print_admin_form_field( $field ) {
	global $scf_field_types;
?>
	<table class="form-table scf-field<?php if ( isset( $field->additional ) ) echo " scf-hidden"; ?>" style="margin-bottom: 10px; border-bottom: 1px dashed #ccc" >
		<tbody>
			<tr>
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_label">
						Label
					</label>
					<input type="hidden" name="scf_field[]" value="<?php echo $field->name ?>" />
				</th>
				<td>
					<input type="text" class="regular-text" value="<?php echo $field->label ?>" id="scf_field_<?php echo $field->name ?>_label" name="scf_field_<?php echo $field->name ?>_label"/>
<?php if ( $field->mandatory ): ?>
					<strong>* Mandatory: <?php echo $field->name ?></strong>
<?php else: ?>
					<span>
						to remove this field just empty the label input and save
					</span>
<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_position">
						Position
					</label>
				</th>
				<td>
					<input type="text" class="regular-text" value="<?php echo $field->position ?>" id="scf_field_<?php echo $field->name ?>_position" name="scf_field_<?php echo $field->name ?>_position"/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_type">
						Type
					</label>
				</th>
				<td>
<?php if ( $field->mandatory ): ?>
			<?php echo $scf_field_types[ $field->type ]->label; ?>
<?php else: ?>
					<select class="scf-choose-type" name="scf_field_<?php echo $field->name ?>_type" id="scf_field_<?php echo $field->name ?>_type">
	<?php foreach ( $scf_field_types as $type => $opt ): ?>
						<option value="<?php echo $type ?>"<?php if ( $field->type == $type ) echo ' selected="selected"'?>>
							<?php echo $opt->label ?>
						</option>
	<?php endforeach; ?>
					</select>
<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_required">
						Required
					</label>
				</th>
				<td>
					<input type="checkbox" class="regular-text" value="1" id="scf_field_<?php echo $field->name ?>_required" name="scf_field_<?php echo $field->name ?>_required" <?php if ( $field->required ) echo ' checked="checked"'?> <?php if ( $field->required ) echo ' checked="checked"'?> <?php if ( $field->mandatory ) echo ' disabled="disabled"'?> /> Yes
				</td>
			</tr>
			<tr class="scf-hidden scf-field-select">
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_select_values">
						Select Options
					</label>
				</th>
				<td>
					<textarea id="scf_field_<?php echo $field->name ?>_select_values" name="scf_field_<?php echo $field->name ?>_select_values"><?php echo join( "\n", $field->values ) ?></textarea>
					<span>One option per line, first is default</span>
				</td>
			</tr>
			<tr class="scf-hidden scf-field-radio">
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_radio_values">
						Radio Options
					</label>
				</th>
				<td>
					<textarea id="scf_field_<?php echo $field->name ?>_radio_values" name="scf_field_<?php echo $field->name ?>_radio_values"><?php echo join( "\n", $field->values ) ?></textarea>
					<span>One option per line, first ist default</span>
				</td>
			</tr>
			<tr class="scf-hidden scf-field-checkbox">
				<th scope="row">
					<label for="scf_field_<?php echo $field->name ?>_checkbox_values">
						Radio Options
					</label>
				</th>
				<td>
					<textarea id="scf_field_<?php echo $field->name ?>_checkbox_values" name="scf_field_<?php echo $field->name ?>_checkbox_values"><?php echo join( "\n", $field->values ) ?></textarea>
					<span>One option per line</span>
				</td>
			</tr>
		</tbody>
	</table>
<?php
}












?>

