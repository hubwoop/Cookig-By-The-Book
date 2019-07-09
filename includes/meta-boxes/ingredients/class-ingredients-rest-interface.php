<?php

namespace ProAtCooking\Recipe;

use WP_REST_Request;
use WP_REST_Server;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';
include_once 'class-ingredients-json-validator.php';

class IngredientsRestInterface {

	private $log;
	private $namespace = 'cbtb-recipe/v1';
	private $route = '/recipe/(?P<id>\d+)/ingredients';

	public function __construct() {
		$this->log = Log::get_instance();
		$this->extend_rest_api();
	}

	private function extend_rest_api(): void {
		add_action( 'rest_api_init', array( $this, 'add_ingredients_endpoint' ) );
	}

	function add_ingredients_endpoint() {
		register_rest_route( $this->namespace, $this->route,
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_ingredients_meta' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
				'schema' => array( $this, 'get_recipe_schema' )
			)
		);
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

	/**
	 * @return array
	 * defines and returns the ingredient list JSON-schema (https://json-schema.org/). Try:
	 *
	 * fetch('http://localhost/wp-json/cbtb-recipe/v1/recipe/<RECIPE_ID>/ingredients', {method: 'OPTIONS'})
	 * .then(function(response) { return response.json(); })
	 * .then(function(myJson) { console.log(myJson); });
	 *
	 * in your browser! See also: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
	 */
	function get_recipe_schema() {
		$schema = array(
			'definitions' => array(),
			'$schema'     => 'http://json-schema.org/draft-07/schema#',
			'$id'         => rest_url() . $this->namespace . $this->route,
			'type'        => 'array',
			'maxItems'    => 100,
			'title'       => 'The Root Schema',
			'items'       =>
				array(
					'$id'                  => '#/items',
					'type'                 => 'object',
					'title'                => 'The Items Schema',
					'required'             =>
						array(
							0 => 'title',
							1 => 'amount',
							2 => 'unit',
						),
					'properties'           =>
						array(
							'title'  =>
								array(
									'$id'       => '#/items/properties/title',
									'type'      => 'string',
									'maxLength' => 100,
									'title'     => 'The Title Schema',
									'default'   => '',
									'examples'  =>
										array(
											0 => 'Salt',
										),
									'pattern'   => '^(.*)$',
								),
							'amount' =>
								array(
									'$id'       => '#/items/properties/amount',
									'type'      => 'string',
									'maxLength' => 100,
									'title'     => 'The Amount Schema',
									'default'   => '',
									'examples'  =>
										array(
											0 => '3',
										),
									'pattern'   => '^(.*)$',
								),
							'unit'   =>
								array(
									'$id'       => '#/items/properties/unit',
									'type'      => 'string',
									'maxLength' => 100,
									'title'     => 'The Unit Schema',
									'default'   => '',
									'examples'  =>
										array(
											0 => 'Tbsp.',
										),
									'pattern'   => '^(.*)$',
								),
						),
					"additionalProperties" => false,
				),
		);

		return $schema;
	}

}

new IngredientsRestInterface();