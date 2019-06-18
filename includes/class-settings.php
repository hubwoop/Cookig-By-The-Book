<?php

namespace ProAtCooking\Recipe;
include_once 'pre-flight.php';

class Settings {
    private $log;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'cbtb_add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'cbtb_settings_init' ) );
		$this->log = Log::get_instance();
	}

	public static function logging_enabled(): bool {
		return self::enabled('cbtb_logging_enabled');
    }

	public static function recipe_loop_enabled(): bool {
		return self::enabled('cbtb_recipes_on_home_enabled');
	}

	public static function meta_info_display_enabled(): bool {
	    return self::enabled('cbtb_append_recipe_meta_enabled');
	}

	private static function enabled(string $setting): bool {
		$options = get_option( 'cbtb_settings');
		if(!$options || !array_key_exists($setting, $options)) {
			return false;
		}
		return true;
    }

	function cbtb_add_admin_menu() {
		add_submenu_page(
			'options-general.php',
			'Cooking By The Book',
			'Cooking By The Book',
			'manage_options',
			'cooking_by_the_book',
			array( $this, 'cbtb_options_page' )
		);
	}

	function cbtb_settings_init() {

		register_setting( 'cbtbPluginPage', 'cbtb_settings' );

		add_settings_section(
			'cbtb_general_settings_section',
			__( 'General plugin Settings', 'cbtb-recipe' ),
			array( $this, 'cbtb_settings_section_callback' ),
			'cbtbPluginPage'
		);

		add_settings_field(
			'cbtb_logging_enabled',
			__( 'Logging', 'cbtb-recipe' ),
			array( $this, 'cbtb_logging_enabled_render' ),
			'cbtbPluginPage',
			'cbtb_general_settings_section'
		);

		add_settings_field(
			'cbtb_recipes_on_home_enabled',
			__( 'Home: Show recipes', 'cbtb-recipe' ),
			array( $this, 'cbtb_recipes_on_home_enabled_render' ),
			'cbtbPluginPage',
			'cbtb_general_settings_section'
		);

		add_settings_field(
			'cbtb_append_recipe_meta_enabled',
			__( 'Display meta', 'cbtb-recipe' ),
			array( $this, 'cbtb_append_recipe_meta_enabled_render' ),
			'cbtbPluginPage',
			'cbtb_general_settings_section'
		);

	}

	function cbtb_logging_enabled_render() {
		?>
        <input type='checkbox'
               name='cbtb_settings[cbtb_logging_enabled]' <?php checked( self::logging_enabled()); ?>
               value='1'>
        <span><?php _e("Requires write privileges for wordpress in the plugins directory. Enables logging in PHP and JS.") ?></span>
		<?php
	}

	function cbtb_recipes_on_home_enabled_render() {
		?>
        <input type='checkbox'
               name='cbtb_settings[cbtb_recipes_on_home_enabled]' <?php checked( self::recipe_loop_enabled()); ?>
               value='1'>
        <span><?php _e("Shows only recipes instead of posts on the blogs front page. Might be useful if your theme does not handle this.") ?></span>
		<?php
	}

	function cbtb_append_recipe_meta_enabled_render() {
		?>
        <input type='checkbox'
               name='cbtb_settings[cbtb_append_recipe_meta_enabled]' <?php checked( self::meta_info_display_enabled()); ?>
               value='1'>
        <span>
            <?php
            _e("Appends metadata (Ingredients, Durations, etc.) to recipes in front end. Can be useful if your theme does not support the");
            echo " <code>" . RecipePlugin::$post_type_name . "</code> ";
            _e("post type.")
            ?>
        </span>
		<?php
	}

	function cbtb_settings_section_callback() {
		echo __( 'Enable or disable general settings here.', 'cbtb-recipe' );
	}

	function cbtb_options_page() {
		?>
        <form action='options.php' method='post'>

            <h2>Cooking By The Book</h2>

			<?php
			settings_fields( 'cbtbPluginPage' );
			do_settings_sections( 'cbtbPluginPage' );
			submit_button();
			?>

        </form>
		<?php
	}
}


new Settings();
