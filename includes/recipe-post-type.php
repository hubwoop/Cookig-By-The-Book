<?php

namespace ProAtCooking\Recipe;
include_once 'pre-flight.php';

function create_recipe_post_type(): void {

	$config = array(
		'labels'              => array(
			'name'                     => __( 'Recipes', 'cbtb-recipe' ),
			'singular_name'            => __( 'Recipe', 'cbtb-recipe' ),
			'add_new_item'             => __( 'Add New Recipe', 'cbtb-recipe' ),
			'new_item'                 => __( 'New Recipe', 'cbtb-recipe' ),
			'view_item'                => __( 'View Recipe', 'cbtb-recipe' ),
			'view_items'               => __( 'View Recipes', 'cbtb-recipe' ),
			'edit_item'                => __( 'Edit Recipe', 'cbtb-recipe' ),
			'search_items'             => __( 'Search Recipes', 'cbtb-recipe' ),
			'not_found'                => __( 'No recipe found', 'cbtb-recipe' ),
			'not_found_in_trash'       => __( 'No recipe found in Trash', 'cbtb-recipe' ),
			'all_items'                => __( 'All recipes', 'cbtb-recipe' ),
			'archives'                 => __( 'Recipe Archives', 'cbtb-recipe' ),
			'attributes'               => __( 'Recipe Attributes', 'cbtb-recipe' ),
			'insert_into_item'         => __( 'Insert into recipe', 'cbtb-recipe' ),
			'uploaded_to_this_item'    => __( 'Uploaded to this recipe', 'cbtb-recipe' ),
			'item_published'           => __( 'Recipe published', 'cbtb-recipe' ),
			'item_published_privately' => __( 'Recipe published privately', 'cbtb-recipe' ),
			'item_reverted_to_draft'   => __( 'Recipe reverted to draft', 'cbtb-recipe' ),
			'item_scheduled'           => __( 'Recipe scheduled', 'cbtb-recipe' ),
			'item_updated'             => __( 'Recipe updated', 'cbtb-recipe' )
		),
		'description'         => 'A cooking recipe',
		'public'              => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_nav_menus'   => true,
		'show_in_rest'        => true,
		'has_archive'         => true,
		'menu_position'       => 5,
		/* the following icon is a modified version of
		https://material.io/tools/icons/?icon=restaurant_menu&style=baseline
		licensed under the Apache License (Apache-2.0):
		https://opensource.org/licenses/Apache-2.0
		a copy is available in this files directory:
		LICENSE-2.0.txt */
		'menu_icon'           => 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none" fill-opacity="0" /><path fill="black" d="M8.1 13.34l2.83-2.83L3.91 3.5c-1.56 1.56-1.56 4.09 0 5.66l4.19 4.18zm6.78-1.81c1.53.71 3.68.21 5.27-1.38 1.91-1.91 2.28-4.65.81-6.12-1.46-1.46-4.2-1.1-6.12.81-1.59 1.59-2.09 3.74-1.38 5.27L3.7 19.87l1.41 1.41L12 14.41l6.88 6.88 1.41-1.41L13.41 13l1.47-1.47z"/></svg>' ),
		'capability_type'     => 'recipe',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'supports'            => array(
			'title',
			'editor',
			'author',
			'thumbnail',
			'excerpt',
			'custom-fields',
			'comments',
			'revisions'
		),
		'rewrite'             => array( 'slug' => 'recipe' )
	);

	if ( ! is_blog_installed() || post_type_exists( RecipePlugin::$post_type_name ) ) {
		return;
	}
	$post_type = register_post_type( RecipePlugin::$post_type_name, $config );
	if ( is_wp_error( $post_type ) ) {
		Log::get_instance()->error( $post_type->get_error_message() );
	}
}

add_action( 'init', __NAMESPACE__ . '\create_recipe_post_type' );