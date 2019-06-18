<?php


namespace ProAtCooking\Recipe;
include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';

/**
 * Class IngredientInputAttributes
 * Wrapper for HTML attributes associated with ingredient input elements.
 * @package ProAtCooking\Recipe
 */
class IngredientInputAttributes {
	private $attributes;

	public function __construct( $properties, $internal_name ) {

		$this->attributes = array(
			'maxlength'   => $properties["maxlength"],
			'placeholder' => __( $properties["placeholder"], 'cbtb-recipe' ),
			'type'        => "text",
			'id'          => "add-new-ingredient-" . $internal_name,
			'name'        => "add-new-ingredient-" . $internal_name
		);
	}

	public function __toString() {
		$attributes = ' ';
		foreach ( $this->attributes as $name => $value ) {
			$attributes .= $name . '="' . $value . '" ';
		}

		return $attributes;
	}
}