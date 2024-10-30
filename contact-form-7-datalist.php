<?php

/*
Plugin Name: Contact Form 7 - Datalist
Description: Adds a datalist shortcode to Contact Form 7 forms.  Requires Contact Form 7 plugin to function.
Version: 1.1
Author: Stuart Clark
License: GPL2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

////////////////////////////////
//
// Contact Form 7 check
//
////////////////////////////////

add_action( 'admin_init', 'wpcf7datalist_has_contactform7' );
function wpcf7datalist_has_contactform7() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        add_action( 'admin_notices', 'wpcf7datalist_contactform7_required' );

        deactivate_plugins( plugin_basename( __FILE__ ) ); 

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function wpcf7datalist_contactform7_required(){
    ?><div class="error"><p>Sorry, but Child Plugin requires the Contact Form 7 plugin to be installed and active.</p></div><?php
}

////////////////////////////////
//
// Initialise
//
////////////////////////////////

add_action( 'plugins_loaded', 'wpcf7datalist_init' , 20 );

function wpcf7datalist_init(){
	add_action( 'wpcf7_init', 'wpcf7datalist_add_shortcode' );
	add_filter( 'wpcf7_validate_datalist*', 'wpcf7datalist_validation_filter', 10, 2 );
}

function wpcf7datalist_add_shortcode() {
	// Add the form tags
	wpcf7_add_form_tag( array( 'datalist' , 'datalist*' ),
		'wpcf7datalist_shortcode_handler', true );
}

function wpcf7datalist_validation_filter( $result, $tag ) {
	$tag = new WPCF7_FormTag( $tag );

	$name = $tag->name;

	// Extract the field value
	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';

	// If this is required and the value is blank then error
	if ( $tag->is_required() && '' == $value ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	}

	return $result;
}

////////////////////////////////
//
// Shortcode
//
////////////////////////////////

function wpcf7datalist_shortcode_handler( $tag ) {
	$tag = new WPCF7_FormTag( $tag );

	//Hide tags without names, the name is required to send the information and to generate the list id
	if ( empty( $tag->name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	// Add the default class for this field
	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7datalist-datalist' );
	// If this field has errored then add the error styling
	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	// Setup the input object attributes
	$atts = array();

	$atts['type'] = 'text';
	$atts['name'] = $tag->name;	
	$atts['id'] = $tag->get_id_option();	
	$atts['class'] = $tag->get_class_option( $class );	
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );	
	// Generate the list id from the name
	$listid = $atts['name']."-list";
	$atts['list'] = $listid;

	if ( $tag->has_option( 'readonly' ) )
		$atts['readonly'] = 'readonly';

	if ( $tag->is_required() )
		$atts['aria-required'] = 'true';

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts = wpcf7_format_atts( $atts );
	
	// Generate the datalist values
	$values = $tag->values;
	
	$datalist = '<datalist id="'.$listid.'">';
	foreach ( $values as $key => $value ) {
		$item_atts = array(
			'value' => $value);

		$item_atts = wpcf7_format_atts( $item_atts );

		$label = isset( $labels[$key] ) ? $labels[$key] : $value;

		$datalist .= sprintf( '<option %1$s>%2$s</option>',
			$item_atts, esc_html( $label ) );
	}
	$datalist.= '</datalist>';

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s%4$s</span>',
		sanitize_html_class( $tag->name ), $atts, $datalist, $validation_error );

	return $html;
}

////////////////////////////////
//
// Administration
//
////////////////////////////////

if ( is_admin() ) {
	add_action( 'wpcf7_admin_init' , 'wpcf7datalist_add_tag_generator' , 100 );
}

function wpcf7datalist_add_tag_generator() {

	if ( ! class_exists( 'WPCF7_TagGenerator' ) ) return;

	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'datalist', __( 'datalist', 'contact-form-7' ),
		'wpcf7datalist_tag_generator' );
}


function wpcf7datalist_tag_generator( $contact_form , $args = '' ){
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];
?>
<div class="control-box">
<fieldset>
<legend><?php echo esc_html( __( "Generate a form-tag for a text input field and linked HTML5 datalist.", 'contact-form-7' ) ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></legend>
		<textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea>
		<label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><span class="description"><?php echo esc_html( __( "One option per line.", 'contact-form-7' ) ); ?></span></label><br />
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>

	<br class="clear" />

</div>
<?php
}