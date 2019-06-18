<?php

namespace ProAtCooking\Recipe;

use WP_REST_Request;
use WP_REST_Server;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';
include_once 'class-ingredients-json-validator.php';

class IngredientsRestInterface {

	private $log;

	public function __construct() {
		$this->log = Log::get_instance();
		$this->extend_rest_api();
	}

	private function extend_rest_api(): void {
		add_action( 'rest_api_init', array( $this, 'add_ingredients_endpoint' ) );
	}

	function add_ingredients_endpoint() {
		register_rest_route( 'cbtb-recipe/v1', '/recipe/(?P<id>\d+)/ingredients', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'update_ingredients_meta' ),
			'permission_callback' => array( $this, 'permission_callback' )
		) );
	}

	/**
	 * Updates the requested recipes ingredients meta field with the requests body (a JSON formatted recipe list)
	 * A transforming validation enforces that only escaped and trusted JSON formatted ingredient lists can be stored.
	 *
	 * @param WP_REST_Request $request
	 */
	function update_ingredients_meta( WP_REST_Request $request ) {
		update_post_meta( $request['id'], '_cbtb_ingredients', wp_slash( $request->get_body() ) );
	}

	/**
	 * Checks if the api caller is allowed to modify the requested recipe and if the provided ingredients are well
	 * formatted.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	function permission_callback( WP_REST_Request $request ) {
		$recipe_id = $request['id'];

		return current_user_can( 'edit_recipes', $recipe_id )
		       && IngredientsJSONValidator::valid_ingredients( $request->get_body() );
	}

}

new IngredientsRestInterface();