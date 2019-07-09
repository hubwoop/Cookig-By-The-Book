<?php

namespace ProAtCooking\Recipe;
include_once 'pre-flight.php';


class RoleManager {
	public static $custom_role_name = 'recipe_author';
	private static $vanilla_capabilities = array(
		'read'              => true,
		'upload_files'      => true,
		'edit_posts'        => true,
		'moderate_comments' => true
	);
	private static $custom_capabilities = array(
		'delete_recipes'           => true,
		'delete_published_recipes' => true,
		'read_recipes'             => true,
		'edit_recipes'             => true,
		'edit_published_recipes'   => true,
		'publish_recipes'          => true
	);
	private static $custom_admin_capabilities = array(
		'edit_others_recipes'      => true,
		'read_private_recipes'     => true,
		'delete_others_recipes'    => true,
		'delete_published_recipes' => true
	);
	private $log;

	public function __construct() {
		$this->log = Log::get_instance();
	}

	/**
	 * Called on plugin (singleton) instantiation.
	 */
	public function setup_roles_and_capabilities(): void {
		$this->introduce_recipe_author_role();
		$this->add_capabilities_to_existing_roles();
	}

	private function introduce_recipe_author_role(): void {
		$all_capabilities = array_merge(
			RoleManager::$vanilla_capabilities,
			RoleManager::$custom_capabilities
		);
		$result           = add_role( RoleManager::$custom_role_name, 'Recipe Author', $all_capabilities );
		if ( null !== $result ) {
			$this->log->debug( 'Role ' . RoleManager::$custom_role_name . ' created.' );
		} else {
			$this->log->debug( 'Role ' . RoleManager::$custom_role_name . ' already exists.' );
		}
	}

	private function add_capabilities_to_existing_roles(): void {
		$admins             = get_role( 'administrator' );
		$editors            = get_role( 'editor' );
		$admin_capabilities = array_merge(
			RoleManager::$custom_capabilities,
			RoleManager::$custom_admin_capabilities
		);
		foreach ( $admin_capabilities as $capability => $enabled ) {
			$admins->has_cap( $capability ) ?: $admins->add_cap( $capability );
			$editors->has_cap( $capability ) ?: $editors->add_cap( $capability );
		}
		$this->log->debug( "Added capabilities for admins and editors to modify recipes" );
	}

	public static function current_user_is_recipe_author(): bool {
		$user = wp_get_current_user();

		return in_array( RoleManager::$custom_role_name, (array) $user->roles );
	}
}



