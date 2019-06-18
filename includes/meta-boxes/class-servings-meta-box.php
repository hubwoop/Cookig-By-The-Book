<?php

namespace ProAtCooking\Recipe;

use WP_Post;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';
include_once 'iMetaBox.php';

class ServingsMetaBox implements iMetaBox {

	private $log;
	public static $name = "servings";
	private static $meta_key = "_cbtb_servings";

	public function __construct() {
		$this->log = Log::get_instance();
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_filter( 'sanitize_post_meta_' . self::$meta_key, array( $this, 'sanitize' ) );
	}

	public function scripts(): void {

		$screen = get_current_screen();

		if ( ! $screen->in_admin() || $screen->id !== RecipePlugin::$post_type_name ) {
			return;
		}

		wp_enqueue_script( 'cbtb_servings',
			RP()->plugin_url() . '/assets/js/servings-meta-compiled.js',
			array( "jquery" )
		);
	}

	public function display( WP_Post $post ): void {
		wp_nonce_field( 'cbtb_inner_servings_box', 'cbtb_inner_servings_box_nonce' );

		$servings = get_post_meta( $post->ID, '_cbtb_servings', true );

		// Display the form, using the current servings value.
		?>
        <label for="cbtb_servings_field">
			<?php _e( 'How many people can be served?', 'cbtb-recipe' ); ?>
        </label>
        <input type="number" id="cbtb_servings_field" name="cbtb_servings_field"
               value="<?php echo esc_attr( $servings ); ?>"
               min="0" step="1" placeholder="4"/>
		<?php
	}

	public function save( int $recipe_id ): void {
		update_post_meta( $recipe_id, self::$meta_key, $_POST['cbtb_servings_field'] );
	}

	public function sanitize( string $input ) {
		$servings = RecipeBlockEditor::enforce_integer( sanitize_text_field( $input ) );
		if ( $servings <= 0 ) {
			$servings = 1;
		}

		return $servings;
	}

	public function get_name(): string {
		return self::$name;
	}
}
