<?php

namespace ProAtCooking\Recipe;

use WP_Post;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';
include_once 'iMetaBox.php';

class DurationsMetaBox implements iMetaBox {

	private $log;
	public static $name = "durations";
	private $duration_types = array(
		"prep" => array(
			"display_name" => "Preparation"
		),
		"cook" => array(
			"display_name" => "Cooking"
		)
	);

	public function __construct() {

		$this->log = Log::get_instance();
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		foreach ( array_keys( $this->duration_types ) as $duration_type ) {
			add_filter( 'sanitize_post_meta__cbtb_durations_' . $duration_type . '_time', array( $this, 'sanitize' ) );
		}

	}

	public function scripts_and_styles(): void {

		$screen = get_current_screen();

		if ( ! $screen->in_admin() || $screen->id !== RecipePlugin::$post_type_name ) {
			return;
		}

		$assetsDir = RP()->plugin_url() . '/assets';

		wp_enqueue_script( 'cbtb_durations',
			$assetsDir . '/js/durations-meta-compiled.js',
			array( "jquery" )
		);
		wp_enqueue_style( 'cbtb_durations', $assetsDir . '/css/durations.css' );
	}

	public function display( WP_Post $post ): void {
		$overall_duration = 0;
		foreach ( $this->duration_types as $duration_type => $duration_properties ) {
			$meta                                           = get_post_meta( $post->ID, '_cbtb_durations_' . $duration_type . '_time', true );
			$this->duration_types[ $duration_type ]["meta"] = $meta;
			$overall_duration                               += intval( $meta );
		}

		$this->render_durations_meta_box( $overall_duration );
	}

	private function render_durations_meta_box( int $duration ): void {
		wp_nonce_field( 'cbtb_inner_durations_box', 'cbtb_inner_durations_box_nonce' );
		foreach ( $this->duration_types as $duration_type => $duration_properties ) {
			$this->render_duration_input( $duration_type, $duration_properties );
		}
		?>
        <div class="cbtb-overall-duration">
			<?php echo __( "Overall duration", 'cbtb-recipe' ) . ': <span id="cbtb-duration">' . $duration . '</span> ' . __( 'minutes', 'cbtb-recipe' ) ?>
        </div>
        <div id="cbtb-durations-save-notice" style="display: none;">
			<?php _e( "Remember to save any changes you made here by saving, publishing or updating this recipe.", 'cbtb-recipe' ) ?>
        </div>
		<?php
	}

	private function render_duration_input( $duration_type, $duration_properties ): void {
		$value      = $duration_properties["meta"];
		$identifier = "cbtb_durations_" . $duration_type . "_time_field";
		?>
        <div>
            <span class="cbtb-<?php echo $duration_type ?>-duration-icon"></span>
            <label for="cbtb_durations_prep_time_field">
				<?php _e( $duration_properties['display_name'] . ' duration estimate (minutes)', 'cbtb-recipe' ); ?>
            </label>
            <input type="number" id="<?php echo $identifier; ?>" name="<?php echo $identifier; ?>"
                   value="<?php echo $value; ?>" size="5" min="0" step="1"/>
        </div>
		<?php

	}

	public function save( int $recipe_id ): void {
		foreach ( $this->duration_types as $duration_type => $display_name ) {
			$duration_field = 'cbtb_durations_' . $duration_type . '_time_field';
			if ( isset( $_POST[ $duration_field ] ) ) {
				$this->save_duration( $_POST[ $duration_field ], $duration_type, $recipe_id );
			}
		}
	}

	private function save_duration( int $duration, string $duration_type, int $recipe_id ): void {
		update_post_meta( $recipe_id, '_cbtb_durations_' . $duration_type . '_time', $duration );
	}

	public function sanitize( string $input ) {
		$duration = RecipeBlockEditor::enforce_integer( sanitize_text_field( $input ) );
		if ( $duration < 0 || $duration > 9000 ) {
			$duration = 0;
		}

		return $duration;
	}

	public function get_name(): string {
		return self::$name;
	}
}
