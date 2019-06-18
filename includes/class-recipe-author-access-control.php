<?php

namespace ProAtCooking\Recipe;
include_once 'pre-flight.php';

use WP_Query;


class RecipeAuthorAccessControl {

	private $log;
	private $status_of_interest = array(
		null      => 'All',
		'publish' => 'Published',
		'draft'   => 'Drafts',
		'pending' => 'Pending',
		'trash'   => 'Trash'
	);

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'reduce_admin_menu_for_recipe_authors' ) );
		add_action( 'admin_bar_menu', array( $this, 'remove_wp_post_nodes_for_recipe_authors' ), 75 );
		add_action( 'load-index.php', array( $this, 'dashboard_redirect' ) );
		add_action( 'pre_get_posts', array( $this, 'query_set_only_recipe_author' ) );
		add_action( 'pre_get_posts', array( $this, 'disallow_recipe_authors_editing_posts' ) );
		add_action( 'pre_get_comments', array( $this, 'authors_recipe_comments_only' ) );

		$this->log = Log::get_instance();
	}

    /**
     * Remove the edit.php, tools.php and index.php pages for recipe authors
     */
    public function reduce_admin_menu_for_recipe_authors() {
		if ( RoleManager::current_user_is_recipe_author() ) {
			remove_menu_page( 'edit.php' );
			remove_menu_page( 'tools.php' );
			remove_menu_page( 'index.php' );
		}
	}

    /**
     * @param $wp_admin_bar
     *
     * Used to remove the new-post-node from the admin bar which is available because we needed to enable the
     * edit_posts capability for recipe authors.
     */
    public function remove_wp_post_nodes_for_recipe_authors($wp_admin_bar ) {
		if ( RoleManager::current_user_is_recipe_author() ) {
			$wp_admin_bar->remove_node( 'new-post' );
		}
	}

    /**
     * A redirect to the recipe edit screen is performed instead of loading the index screen.
     */
    public function dashboard_redirect() {
		if ( RoleManager::current_user_is_recipe_author() ) {
			wp_redirect( admin_url( 'edit.php?post_type=' . RecipePlugin::$post_type_name ) );
		}
	}

	/**
	 * @param $query
	 *
	 * Modifies the posts fetching query to fetch only those recipes made by the currently logged in recipe author.
	 * Respects the "suppress_filters" directive and fires only on relevant admin pages.
	 *
	 * Inspired by, refactored and recipe post type adjusted version of the "View Own Posts Media Only" plugin by
	 * Vladimir Garagulya (https://wordpress.org/plugins/view-own-posts-media-only/)
	 */
	public function query_set_only_recipe_author( $query ) {

		global $pagenow;

		if ( $query->get( 'suppress_filters' )
		     || ! is_admin()
		     || $pagenow != 'edit.php'
		        && $pagenow != 'upload.php'
		        && ( $pagenow != 'admin-ajax.php' || empty( $_POST['action'] ) || $_POST['action'] != 'query-attachments' )
		     || ! RoleManager::current_user_is_recipe_author() ) {
			return;
		}

		$query->set( 'author', wp_get_current_user()->ID );
		add_filter( 'views_edit-' . RecipePlugin::$post_type_name, array( $this, 'adjust_recipe_counts' ) );
	}

	/**
	 * @param $views
	 *
	 * This filter changes the display (in wordpress terms: views) on the edit.php?post_type=cbtb_recipe page so that
	 * recipe status counts (number behind each status) only include those recipes by the currently logged in recipe
	 * author.
	 *
	 * The default behavior would be showing the counts for recipes by anyone. This information is unnecessary
	 * for our recipe author because they can't see other peoples recipes via the backend.
	 *
	 * Inspired by, refactored and recipe post type adjusted version of the "View Own Posts Media Only" plugin by
	 * Vladimir Garagulya (https://wordpress.org/plugins/view-own-posts-media-only/)
	 *
	 * @return mixed
	 */
	public function adjust_recipe_counts( $views ) {
		global $wp_query;

		unset( $views['mine'] );

		foreach ( $this->status_of_interest as $status => $display_name ) {
			$query_result = $this->create_wp_query_for_recipes_filtered_by_post_status( $status );
			if ( $status == null ):
				$class        = ( empty( $wp_query->query_vars['post_status'] ) || $wp_query->query_vars['post_status'] == null ) ? ' class="current"' : '';
				$views['all'] = sprintf(
					'<a href="%s"' . $class . '>' . __( $display_name, 'cbtb-recipe' ) . ' <span class="count">(%d)</span></a>',
					admin_url( 'edit.php?post_type=' . RecipePlugin::$post_type_name ),
					$query_result->found_posts
				);
			elseif ( $query_result->found_posts ):
				$class            = ( ! empty( $wp_query->query_vars['post_status'] ) && $wp_query->query_vars['post_status'] == $status ) ? ' class="current"' : '';
				$views[ $status ] = sprintf(
					'<a href="%s"' . $class . '>' . __( $display_name, 'cbtb-recipe' ) . ' <span class="count">(%d)</span></a>',
					admin_url( 'edit.php?post_status=' . $status . '&post_type=' . RecipePlugin::$post_type_name ),
					$query_result->found_posts
				);
			else:
				unset( $views[ $status ] );
			endif;
		}

		return $views;
	}

	private function create_wp_query_for_recipes_filtered_by_post_status( $status ): WP_Query {
		$query_args = array(
			'author'      => wp_get_current_user()->ID,
			'post_type'   => RecipePlugin::$post_type_name,
			'post_status' => $status
		);

		return new WP_Query( $query_args );
	}

	function disallow_recipe_authors_editing_posts( $query ) {
		if ( is_admin() && RoleManager::current_user_is_recipe_author() && $query->get( 'post_type' ) === 'post' ) {
			wp_redirect( admin_url( 'edit.php?post_type=' . RecipePlugin::$post_type_name ) );
			die;
		}
	}

	/**
	 * @param $query
	 *
	 * Modifies the edit-comments.php view for recipe authors. The general idea is to only allow managing/viewing
	 * comments made under recipes authored by the currently logged in recipe author.
	 *
	 * See registered action and filters for details.
	 * Respects the "suppress_filter" directive.
	 */
	public function authors_recipe_comments_only( $query ) {
		global $pagenow;
		if ( $pagenow !== 'edit-comments.php' || ! RoleManager::current_user_is_recipe_author() || $query->get( 'suppress_filters' ) || ! is_admin() ) {
			return;
		}
		$query->query_vars['post_author'] = wp_get_current_user()->ID;
		add_action( 'admin_enqueue_scripts', array( $this, 'hide_comment_count' ) );
		add_filter( 'comment_row_actions', array( $this, 'reduce_comment_moderation_actions' ), 10, 1 );
		add_filter( 'views_edit-comments', array( $this, 'remove_comment_categories' ) );
		add_filter( 'bulk_actions-edit-comments', array( $this, 'remove_comments_bulk_actions' ) );
		add_filter( 'map_meta_cap', array( $this, 'restrict_comment_editing' ), 10, 4 );

	}

	public function hide_comment_count( $hook ) {
		if ( 'edit-comments.php' != $hook ) {
			return;
		}

		wp_enqueue_style( 'cbtb_hide_comment_count', RP()->plugin_url() . '/assets/css/hide-comment-count.css' );
	}

	public function reduce_comment_moderation_actions( $actions ) {
		unset( $actions['approve'] );
		unset( $actions['unapprove'] );
		unset( $actions['spam'] );
		unset( $actions['reply'] );

		return $actions;
	}

	public function remove_comment_categories( $views ) {
		unset( $views['approved'] );
		unset( $views['spam'] );
		unset( $views['moderated'] );

		return $views;
	}

	function remove_comments_bulk_actions( $actions ) {
		unset( $actions["unapprove"] );
		unset( $actions["approve"] );
		unset( $actions["spam"] );

		return $actions;
	}

	/**
	 * @param string[] $caps Array of the user's capabilities.
	 * @param string $cap Capability name.
	 * @param int $user_id The user ID.
	 * @param array $args Adds the context to the cap. Typically the object ID.
	 *
	 *  Disallows recipe authors from changing other peoples comments because that just seems wrong and totalitarian.
	 *
	 * @return array
	 */
	function restrict_comment_editing( $caps, $cap, $user_id, $args ) {
		if ( 'edit_comment' == $cap ) {
			$comment = get_comment( $args[0] );
			if ( $comment->user_id != $user_id ) {
				$caps = [ '' ];
			}
		}

		return $caps;
	}

}

new RecipeAuthorAccessControl();