<?php
/**
 * This file defines xf_display_Renderables, a utility for common output elements.
 * 
 * PHP version 5
 * 
 * @package xf
 * @subpackage display
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 */

require_once(dirname(__FILE__).'/../errors/Error.php');

/**
 * This class is considered static. It is meant to hold any miscellanious methods that output common elements.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage display
 */

// START static class
class xf_display_Renderables {
	
	/**
	 * Helper function to output options in a dropdown list
	 * @param array an associative array where the keys are the names of the options, and the values are the values
	 * @param string|array A string or array representing the options selected by default
	 * @return string what ever value is set in this parameter is the default selected option in the list
	 */
	public static function buildSelectOptions( $args, $selected = '' ) {
		foreach ( $args as $name => $value ) {
			if( is_array($selected) ) {
				$selectedStr = ( in_array($value, $selected, true ) ) ? 'selected="selected"' : '';
			} else {
				$selectedStr = ( $selected == $value ) ? 'selected="selected"' : '';
			}
			echo "\n\t<option value=\"$value\" $selectedStr>$name</option>";
		};
	}
	
	/**
	 * Helper function to output radios or checkboxes in a list
	 *
	 * @param string $id Root id to prepend to everything in the list
	 * @param string $name Root name to prepend to everything in the list (defaults to $id)
	 * @param array $inputs Associative array where the keys are the names and values of the list inputs, and the values are the labels of the inputs
	 * @return string what ever value is set in this parameter is the default selected option in the dropdown list
	 */
	public static function buildInputList( $id, $name = '', $inputs = array(), $args = array() ) {
		$args = array_merge( array(
			'return' => false,
			'type' => 'checkbox',
			'checked' => array(),
			'beforeInput' => '',
			'afterInput' => '',
			'beforeLabel' => ' ',
			'afterLabel' => '',
			'labels' => 'right',
		), $args );
		extract( $args );
		if( !is_array( $checked ) ) $checked = array( $checked );
		if( empty( $name ) ) $name = $id;
		if( $return ) $htmlInputs = array();
		foreach ( $inputs as $key => $input ) {
			if( is_array( $label ) ) {
				$valueStr = ( isset( $input['value'] ) ) ? $input['value'] : $key;
				$labelStr = ( isset( $input['label'] ) ) ? $input['label'] : $input;
			} else {
				$valueStr = $key;
				$labelStr = $input;
			}
			$nameStr = ( $type != 'radio' ) ? $name.'['.$key.']' : $name;
			$checkedStr = ( in_array( $key, $checked, true ) ) ? 'checked="checked" ' : '';
			$inputTag = '<input id="'.$id.'-'.$key.'" type="'.$type.'" name="'.$nameStr.'" value="'.$valueStr.'" '.$checkedStr.'/> ';
			$labelTag = '<label for="'.$id.'-'.$key.'">'.$labelStr.'</label>';
			if( $return ) ob_start();
			echo $beforeInput;
			switch( $labels ) {
				case 'none' :
					echo $inputTag;
				break;
				case 'left' :
					echo $beforeLabel . $labelTag . $afterLabel . $inputTag;
				break;
				default :
					echo $inputTag . $beforeLabel . $labelTag . $afterLabel;
				break;
			}
			echo $afterInput;
			if( $return ) $htmlInputs[$key] = ob_get_clean();
		}
		if( $return ) return $htmlInputs;
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * CONSTRUCTOR
	 *
	 * This is a static class, trigger an error
	 *
	 * @return void
	 */
	public function __construct()
	{
		throw new xf_errors_Error( __CLASS__ . ' is a static class, you should not instantiate this class!' );
	}
}
// END static class
?>