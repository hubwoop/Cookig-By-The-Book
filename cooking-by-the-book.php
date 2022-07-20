<?php
/*
Plugin Name: Cooking by the book
Plugin URI: https://github.com/hubwoop/Cookig-By-The-Book
Description: Allows users to create recipes with lots of meta data
Version: 1.0
Author: Jonas Mai
Author URI: http://jonasmai.de
License: GPL2
Text Domain: cbtb-recipe

{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {License URI}.
*/


namespace ProAtCooking\Recipe;

include_once 'includes/pre-flight.php';


RecipePlugin::get_instance();

class RecipePlugin {

	private $log;
	public static $post_type_name = 'cbtb_recipe';
	protected static $_instance = null;

	static function activate(): void {
		$log = Log::get_instance();
		if ( ! current_user_can( 'activate_plugins' ) ) {
			$log->warning( "Someone tried to activate the plugin with insufficient rights" );

			return;
		}

		self::get_instance();
		do_action( 'cbtb_plugin_activated' );
	}

	static function deactivate(): void {
		$log = Log::get_instance();
		if ( ! current_user_can( 'activate_plugins' ) ) {
			$log->warning( "Someone tried to deactivate the plugin with insufficient rights" );

			return;
		}
		remove_role( RoleManager::$custom_role_name );
		self::$_instance = null;
		$log->info( 'Plugin deactivated.' );
	}

	static function uninstall(): void {
		$log = Log::get_instance();
		if ( ! current_user_can( 'delete_plugins' ) ) {
			$log->warning( "Someone tried to uninstall the plugin with insufficient rights" );

			return;
		}
		$log->debug( 'Role ' . RoleManager::$custom_role_name . ' removed.' );
		$log->info( 'Plugin uninstalled.' );
	}

	public static function get_instance(): RecipePlugin {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	function __construct() {

		if ( ! defined( 'CBTB_PLUGIN_ROOT' ) ) {
			define( 'CBTB_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
		}

		$static_identifier = __NAMESPACE__ . '\RecipePlugin';
		register_activation_hook( __FILE__, array( $static_identifier, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $static_identifier, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( $static_identifier, 'uninstall' ) );
		add_action( 'pre_get_posts', array( $this, 'only_show_recipes_on_home' ) );

		include_once 'includes/class-log.php';
		include_once 'includes/class-role-manager.php';
		include_once 'includes/recipe-post-type.php';
		include_once 'includes/recipe-taxonomies.php';
		include_once 'includes/class-recipe-author-access-control.php';
		include_once 'includes/class-recipe-block-editor.php';
		include_once 'includes/class-meta-display.php';
		include_once 'includes/meta-boxes/ingredients/class-ingredients-rest-interface.php';
		include_once 'includes/class-settings.php';

		$this->log = Log::get_instance();
		add_action( 'cbtb_plugin_activated', array( $this, 'activate_completed' ), 100 );
	}

	protected function __clone() {
		// Prevent singleton cloning
	}

	function activate_completed(): void {
		$roleManager = new RoleManager();
		$roleManager->setup_roles_and_capabilities();
		$this->log->info( 'Plugin activated.' );
	}

	static function only_show_recipes_on_home( $query ) {
		if ( is_home() && $query->is_main_query() && Settings::recipe_loop_enabled() ) {
			$query->set( 'post_type', array( RecipePlugin::$post_type_name ) );
		}

		return $query;
	}

    /**
     * Convenience method, allows quick building of (front-end) resource paths
     * @return string The plugins base URL without a trailing slash.
     */
    public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

}

function RP(): RecipePlugin {
	return RecipePlugin::get_instance();
}

