<?php

namespace ProAtCooking\Recipe;

use WP_Post;

include_once 'pre-flight.php';
include_once 'class-settings.php';

/**
 * Class MetaDisplay
 * Displays recipe meta data after recipe content on the front-end.
 * @package ProAtCooking\Recipe
 */
class MetaDisplay {

	private $log;

	public function __construct() {
		$this->log = Log::get_instance();

		add_filter( 'the_content', array( $this, 'add_meta' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'include_display_styles' ) );
	}

	/**
	 * @param $content
	 *
	 * Appends a recipe meta to it's front end single view display.
	 * Some checks ensure that sidebar display etc. are omitted from this extension.
	 *
	 * @return string
	 */
	public function add_meta( $content ) {

		if ( is_single() && ! empty( $GLOBALS['post'] ) ) {
			$post = $GLOBALS['post'];
			if ( $post->ID === get_the_ID() && $post->post_type === RecipePlugin::$post_type_name ) {
				$content .= "<div class='cbtb-after-content'>" . $this->generate_meta( $post ) . "</div>";
			}
		}

		return $content;
	}

	private function generate_meta( WP_Post $post ): string {
		$meta             = "<h4>" . __( 'Recipe Details', 'cbtb-recipe' ) . "</h4><p>";
		$servings         = $post->_cbtb_servings;
		$difficulty       = $post->_cbtb_difficulty;
		$prep             = $post->_cbtb_durations_prep_time;
		$cook             = $post->_cbtb_durations_cook_time;
		$overall_duration = intval( $prep ) + intval( $cook );
		$ingredients      = $post->_cbtb_ingredients;

		if ( $servings ) {
			$meta .= __( 'Servings', 'cbtb-recipe' ) . ": " . $servings . "<br>";
		}
		if ( $difficulty ) {
			$meta .= __( 'Difficulty', 'cbtb-recipe' )
			         . ": <span class='cbtb-difficulty-" . $difficulty . "'>" . $difficulty . "</span><br>";
		}
		if ( $prep ) {
			$meta .= "<span class='cbtb-prep-duration'>" . __( 'Preparation Duration', 'cbtb-recipe' ) . ": " . $prep . "</span>";
		}
		if ( $cook ) {
			$meta .= "<span class='cbtb-cook-duration'>" . __( 'Cooking Duration', 'cbtb-recipe' ) . ": " . $cook . "</span>";
		}
		if ( $overall_duration ) {
			$meta .= "<span class='cbtb-overall-duration'>" . __( 'Overall Duration', 'cbtb-recipe' ) . ": " . $overall_duration . "</span><br>";
		}
		$meta = $this->generate_ingredients_html( $ingredients, $meta );
		$meta .= "</p>";

		return $meta;
	}

	function include_display_styles() {
		wp_enqueue_style( 'meta-display', RP()->plugin_url() . '/assets/css/meta-display.css' );
	}

	/**
	 * @param $ingredients
	 * @param string $meta
	 *
	 * @return string
	 */
	private function generate_ingredients_html( $ingredients, string $meta ): string {

		if ( ! $ingredients || $ingredients === '[]' ) {
			return $meta;
		}
		$ingredients = json_decode( $ingredients, JSON_UNESCAPED_UNICODE );
		array_unshift( $ingredients, array(
				"table head title"  => __( 'Title', 'cbtb-recipe' ),
				"table head amount" => __( 'Amount', 'cbtb-recipe' ),
				"table head unit"   => __( 'Unit', 'cbtb-recipe' )
			)
		);

		$ingredients = apply_filters( 'cbtb-ingredients-pre-table-generation', $ingredients );
		$table       = $this->build_table( $ingredients );

		return $meta . $table;
	}

	/**
	 * @param $ingredients
	 *
	 * @return string
	 */
	private function build_table( $ingredients ): string {
		$table = "<table class='wp-block-table'>";
		$table .= $this->build_table_head( array_shift( $ingredients ) );
		$table .= $this->build_table_body( $ingredients );
		$table = apply_filters( 'cbtb-ingredients-post-table-generation', $table );

		return $table;
	}

	/**
	 * @param $table_head
	 *
	 * @return string
	 */
	private static function build_table_head( $table_head ): string {
		$head = "<thead><tr>";
		foreach ( $table_head as $tableHeadColumn ) {
			$head .= "<th>" . $tableHeadColumn . "</th>";
		}
		$head .= "</tr></thead>";

		return $head;
	}

	/**
	 * @param $ingredients
	 *
	 * @return string
	 */
	private static function build_table_body( $ingredients ): string {
		$body = "<tbody>";
		foreach ( $ingredients as $ingredient ) {
			$body .= "<tr>";
			foreach ( $ingredient as $part ) {
				$body .= "<td>" . html_entity_decode( $part ) . "</td>";
			}
			$body .= "</tr>";
		}
		$body .= "</tbody></table>";

		return $body;
	}
}

if ( Settings::meta_info_display_enabled() ) {
	new MetaDisplay();
}