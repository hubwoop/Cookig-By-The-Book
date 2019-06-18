<?php

namespace ProAtCooking\Recipe;

use WP_Post;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';
include_once CBTB_PLUGIN_ROOT . '/includes/meta-boxes/iMetaBox.php';
include_once 'class-ingredients-json-validator.php';
include_once 'class-ingredient-input-attributes.php';

class IngredientsMetaBox implements iMetaBox {

	private $log;
	public static $name = "ingredients";
	private static $meta_key = "_cbtb_ingredients";
	public static $ingredient_property_names = array(
		"title"  => array(
			"display_name" => "Ingredient",
			"placeholder"  => "Tomatoes",
			"maxlength"    => 50
		),
		"amount" => array(
			"display_name" => "Amount",
			"placeholder"  => "3",
			"maxlength"    => 6
		),
		"unit"   => array(
			"display_name" => "Unit",
			"placeholder"  => "kg",
			"maxlength"    => 10
		)
	);


	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'ingredients_scripts_and_styles' ) );
		add_filter( 'sanitize_post_meta_' . self::$meta_key, array( $this, 'sanitize' ) );

		$this->log = Log::get_instance();
	}

	public function ingredients_scripts_and_styles(): void {
		$screen = get_current_screen();

		if ( ! $screen->in_admin() || $screen->id !== RecipePlugin::$post_type_name ) {
			return;
		}

		$assetsDir = RP()->plugin_url() . '/assets';

		wp_enqueue_script( 'cbtb_ingredients',
			$assetsDir . '/js/ingredients-meta-list-compiled.js',
			array( "jquery", "jquery-ui-sortable" )
		);
		wp_localize_script( 'cbtb_ingredients', 'cbtbIngredientsRestSettings', [
			'root'           => esc_url_raw( rest_url() ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'loggingEnabled' => Settings::logging_enabled()
		] );
		wp_enqueue_script( 'cbtb_ingredients_touch_support',
			$assetsDir . '/js/jquery.ui.touch-punch.min.js',
			array( "jquery", "jquery-ui-mouse", "jquery-ui-widget" )
		);
		wp_enqueue_style( 'cbtb_ingredients_list', $assetsDir . '/css/ingredients.css' );
		wp_enqueue_style( 'cbtb_meta_blocks', $assetsDir . '/css/meta-blocks.css' );

	}

	public function display( WP_Post $post ): void {
		$ingredients = get_post_meta( $post->ID, '_cbtb_ingredients', true );
		self::render_hidden_inputs( $ingredients );
		self::render_controls();
		self::render_ingredients_list();
		self::render_input();
	}

	private static function render_hidden_inputs( $ingredients ): void {
		wp_nonce_field( 'cbtb_inner_ingredients_box', 'cbtb_inner_ingredients_box_nonce' );
		echo '<input type="hidden" id="cbtb_ingredients_field" name="cbtb_ingredients_field"
               value="' . esc_attr( $ingredients ) . '" class="cbtb-ingredient-col">';
	}

	private static function render_controls(): void {
		_e( 'List the ingredients of your recipe here', 'cbtb-recipe' );
		?>
        <span id="cbtb-ingredients-list-save-notice" style="display: none;">
            <?php _e( "Saving...", 'cbtb-recipe' ) ?>
        </span>
        <span id="cbtb-history-controls">
            <span id="cbtb-ingredients-undo" style="user-select: none; cursor: pointer;">
                <span class="dashicons dashicons-undo"></span>
            </span>
            <span id="cbtb-ingredients-redo" style="user-select: none; cursor: pointer;">
                <span class="dashicons dashicons-redo"></span>
            </span>
        </span>
		<?php
	}

	private static function render_ingredients_list(): void {
		echo '<div class="cbtb-ingredients-table-head cbtb-ingredients-table">';
		foreach ( self::$ingredient_property_names as $internal_name => $properties ) {
			echo '<span class="cbtb-' . $internal_name . '-col">' . __( $properties["display_name"], 'cbtb-recipe' ) . '</span>';
		}
		echo '<span class="cbtb-ingredients-table-spacer">&nbsp;</span></div>'
		     . '<ul id="sortable_list"></ul>';
	}

	private static function render_input(): void {
		echo '<div class="cbtb-ingredients-add-new-form cbtb-ingredients-table">';
		foreach ( self::$ingredient_property_names as $internal_name => $properties ) {
			echo '<label style="display: none;" for="add-new-ingredient-' . $internal_name . '"> '
			     . __( $properties["display_name"], 'cbtb-recipe' )
			     . '</label><input' . new IngredientInputAttributes( $properties, $internal_name ) . '>';
		}
		echo '<div id="list_item_adder"><span class="dashicons dashicons-plus-alt"></span></div></div>';
	}


	public function save( int $recipe_id ): void {
		update_post_meta( $recipe_id, self::$meta_key, $_POST['cbtb_ingredients_field'] );
	}

	public function sanitize( string $input ) {
		$validator         = new IngredientsJSONValidator( $input );
		return $validator->get_trusted_representation();
	}

	public function get_name(): string {
		return self::$name;
	}
}
