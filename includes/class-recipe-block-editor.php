<?php

namespace ProAtCooking\Recipe;

use Exception;

include_once 'pre-flight.php';

/* https://wordpress.org/gutenberg/handbook/ */

class RecipeBlockEditor {

	private $log;
	private $meta_boxes;

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_custom_meta_scripts_and_styles' ) );
		add_filter( 'allowed_block_types', array( $this, 'allowed_block_types' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_recipe_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_recipe_meta' ) );

		include_once 'meta-boxes/class-meta-boxes.php';
		include_once 'meta-boxes/ingredients/class-ingredients-meta-box.php';
		include_once 'meta-boxes/class-durations-meta-box.php';
		include_once 'meta-boxes/class-servings-meta-box.php';
		include_once 'meta-boxes/class-difficulty-meta-box.php';

		$this->log = Log::get_instance();

		$this->meta_boxes = new MetaBoxes(
			new IngredientsMetaBox(),
			new DurationsMetaBox(),
			new ServingsMetaBox(),
			new DifficultyMetaBox()
		);
	}

	public function allowed_block_types( $enabled_blocks ) {
		if ( RoleManager::current_user_is_recipe_author() ) {
			$enabled_blocks = array(
				'core/image',
				'core/gallery',
				'core/paragraph',
				'core/heading',
				'core-embed/youtube'
			);
		}

		return $enabled_blocks;
	}

	public function enqueue_custom_meta_scripts_and_styles() {
		$screen = get_current_screen();

		if ( ! $screen->in_admin() || $screen->id !== RecipePlugin::$post_type_name ) {
			return;
		}

		wp_enqueue_style( 'cbtb_meta_blocks', RP()->plugin_url() . '/assets/css/meta-blocks.css' );
	}

	public function add_recipe_meta_boxes( string $post_type ): void {
		if ( $post_type !== RecipePlugin::$post_type_name ) {
			return;
		}

		foreach ( $this->meta_boxes as $box ) {
			add_meta_box(
				'cbtb-' . $box->get_name(),
				__( ucfirst( $box->get_name() ), 'cbtb-recipe' ),
				array( $box, 'display' ), // display methods existence is guaranteed by iMetaBox interface.
				RecipePlugin::$post_type_name,
				'advanced'
			);
			add_filter( 'sanitize_'.RecipePlugin::$post_type_name.'_meta_birth-year', 'sanitize_birth_year_meta' );
		}
	}

	public function save_recipe_meta( int $recipe_id ): int {
		// ignore auto-saves & return early when missing capability
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		     || ! current_user_can( 'edit_recipe', $recipe_id ) ) {
			return $recipe_id;
		}

		foreach ( $this->meta_boxes as $box ) {
			if ( $this->current_request_holds_valid_post_data_for( $box->get_name() ) ) {
				$box->save( $recipe_id );
			}
		}

		return $recipe_id;

	}

	private function current_request_holds_valid_post_data_for( string $meta_box_name ): bool {
		$qualifier = 'cbtb_inner_' . $meta_box_name . '_box';

		return isset( $_POST[ $qualifier . '_nonce' ] )
		       && wp_verify_nonce( $_POST[ $qualifier . '_nonce' ], $qualifier );
	}

	public static function enforce_integer( string $potentialInt ): int {
		$guaranteedInt = PHP_INT_MIN;
		try {
			$guaranteedInt = intval( $potentialInt );
		} catch ( Exception $e ) {
			wp_die( __( 'Invalid value provided. Please provide an integer value.', 'cbtb-recipe' ) );
		}

		return $guaranteedInt;
	}
}


new RecipeBlockEditor();


