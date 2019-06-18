<?php

namespace ProAtCooking\Recipe;

use WP_Post;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';


class DifficultyMetaBox implements iMetaBox {

	private $log;
	public static $name = "difficulty";
	private $difficulty_levels = array( "easy", "medium", "hard" );
	private static $meta_key = "_cbtb_difficulty";

	public function __construct() {

		add_filter( 'sanitize_post_meta_' . self::$meta_key, array( $this, 'sanitize' ) );

		$this->log = Log::get_instance();
	}

	public function display( WP_Post $post ): void {
		wp_nonce_field( 'cbtb_inner_difficulty_box', 'cbtb_inner_difficulty_box_nonce' );

		$difficulty = esc_html( get_post_meta( $post->ID, '_cbtb_difficulty', true ) );

		// Display the form, using the current difficulty value.
		echo _e( 'How complicated is cooking this recipe?', 'cbtb-recipe' );
		echo "<br>";
		foreach ( $this->difficulty_levels as $difficulty_level ) {
			self::render_difficulty_level( $difficulty_level, $difficulty );
		}
	}

	private static function render_difficulty_level( $difficulty_level, $selected_difficulty ): void {
		?>
        <br>
        <div>
            <input type="radio" id="<?php echo $difficulty_level ?>"
                   name="cbtb_difficulty_field"
                   value="<?php echo $difficulty_level ?>"
				<?php checked( $selected_difficulty === $difficulty_level ) ?>>
            <label for="<?php echo $difficulty_level ?>">
				<?php _e( ucfirst( $difficulty_level ), 'cbtb-recipe' ); ?>
            </label>
        </div>
		<?php
	}

	public function save( int $recipe_id ): void {
		update_post_meta( $recipe_id, self::$meta_key, $_POST['cbtb_difficulty_field'] );
	}

	public function sanitize( string $input ) {
		$possibly_difficulty = sanitize_text_field( $input );
		$difficulty          = "";
		if ( in_array( $possibly_difficulty, $this->difficulty_levels ) ) {
			$difficulty = $possibly_difficulty;
		}

		return $difficulty;
	}

	public function get_name(): string {
		return self::$name;
	}
}
