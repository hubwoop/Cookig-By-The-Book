<?php

namespace ProAtCooking\Recipe;

class IngredientsJSONValidator {

	private static $json_settings = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK;
	private $untrusted_input;
	private $trusted_representation = array();

	public function __construct( string $untrusted_ingredients_json ) {
		$this->untrusted_input = $untrusted_ingredients_json;
	}

	/**
	 * Based on the arguments validity as a JSON representation of an ingredient list
	 * this function either returns true or false.
	 *
	 * Well formed ingredient lists as accepted by this validator look like this:
	 * [ {"title": "Sugar", "amount": "2", "unit": "Tbsp."}, ... ]
	 * Ingredient lists with more than 100 ingredients are NOT considered valid even if all ingredients are valid.
	 *
	 * The provided json is decoded and then tested for the expected structure described above.
	 *
	 * @param string $untrusted_ingredients_list *JSON* formatted
	 *
	 * @return bool
	 */
	static function valid_ingredients( $untrusted_ingredients_list ): bool {
		$ingredients = json_decode( $untrusted_ingredients_list, self::$json_settings );
		if ( ! self::accepted_array_format( $ingredients ) ) {

			return false;
		}
		foreach ( $ingredients as $ingredient ) {
			foreach ( $ingredient as $key => $value ) {
				if ( ! self::is_accepted_key_value_pair( $key, $value ) ) {
					self::log_warning_about_modified_input();

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Based on the validity of this object as a JSON representation of an ingredient list
	 * this function either returns an ingredient list or an empty array if the provided ingredients are forged.
	 *
	 * Well formed ingredient lists as accepted by this validator look like this:
	 * [ {"title": "Sugar", "amount": "2", "unit": "Tbsp."}, ... ]
	 *
	 * The provided json is decoded and then tested for the expected structure described above.
	 *
	 * @return string *trusted* JSON representation of an ingredient list
	 */
	public function get_trusted_representation(): string {

		$ingredients = json_decode( $this->untrusted_input, self::$json_settings );

		if ( self::accepted_array_format( $ingredients ) ) {
			$this->parse_ingredients( $ingredients );
		}

		return json_encode( $this->trusted_representation, self::$json_settings );
	}

	private function parse_ingredients( $ingredients ): void {
		foreach ( $ingredients as $ingredient ) {
			$parsed_ingredient = $this->parse_ingredient_details( $ingredient );
			if ( $parsed_ingredient ) {
				array_push( $this->trusted_representation, $parsed_ingredient );
			}
		}
	}

	private function parse_ingredient_details( $ingredient ) {
		$filtered_ingredient = array();
		foreach ( $ingredient as $key => $value ) {
			if ( self::is_accepted_key_value_pair( $key, $value ) ) {
				$filtered_ingredient[ $key ] = $value;
			}
		}

		return $filtered_ingredient;
	}

	private static function is_accepted_key_value_pair( $key, $value ) {
		if ( self::valid_key( $key ) && self::valid_value( $value ) ) {
			return true;
		}
		self::log_warning_about_modified_input();

		return false;
	}

	private static function log_warning_about_modified_input() {
		$bad_user = wp_get_current_user();
		Log::get_instance()->warning(
			$bad_user->nickname . " (user id: " . $bad_user->id . ") supplied modified ingredients."
		);
	}

	private static function valid_key( $key ) {
		return array_key_exists( $key, IngredientsMetaBox::$ingredient_property_names );
	}

	private static function valid_value( $value ) {
		return strlen( $value ) < 100;
	}

	private static function accepted_array_format( $untrusted_ingredients_list_array ) {
		return is_array( $untrusted_ingredients_list_array ) && sizeof( $untrusted_ingredients_list_array ) < 100;
	}

}