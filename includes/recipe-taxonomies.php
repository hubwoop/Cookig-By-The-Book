<?php

namespace ProAtCooking\Recipe;
include_once 'pre-flight.php';


function create_recipe_taxonomies() {
	$mealTypeLabels = array(
		'name'              => _x( 'Meal Types', 'taxonomy general name', 'cbtb-recipe' ),
		'singular_name'     => _x( 'Meal Type', 'taxonomy singular name', 'cbtb-recipe' ),
		'search_items'      => __( 'Search Meal Types', 'cbtb-recipe' ),
		'all_items'         => __( 'All Meal Types', 'cbtb-recipe' ),
		'parent_item'       => __( 'Parent Meal Type', 'cbtb-recipe' ),
		'parent_item_colon' => __( 'Parent Meal Type:', 'cbtb-recipe' ),
		'edit_item'         => __( 'Edit Meal Type', 'cbtb-recipe' ),
		'update_item'       => __( 'Update Meal Type', 'cbtb-recipe' ),
		'add_new_item'      => __( 'Add New Meal Type', 'cbtb-recipe' ),
		'new_item_name'     => __( 'New Meal Type Name', 'cbtb-recipe' ),
		'menu_name'         => __( 'Meal Type', 'cbtb-recipe' ),
	);

	$mealTypeConfig = array(
		'hierarchical'      => true,
		'labels'            => $mealTypeLabels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'meal-type' ),
		'show_in_rest'      => true,
	);


	$worldCuisineLabels = array(
		'name'                       => _x( 'World Cuisine', 'taxonomy general name', 'cbtb-recipe' ),
		'singular_name'              => _x( 'World Cuisine', 'taxonomy singular name', 'cbtb-recipe' ),
		'search_items'               => __( 'Search World Cuisine', 'cbtb-recipe' ),
		'popular_items'              => __( 'Popular World Cuisine', 'cbtb-recipe' ),
		'all_items'                  => __( 'All World Cuisine', 'cbtb-recipe' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Region', 'cbtb-recipe' ),
		'update_item'                => __( 'Update Region', 'cbtb-recipe' ),
		'add_new_item'               => __( 'Add New Region', 'cbtb-recipe' ),
		'new_item_name'              => __( 'New Region Name', 'cbtb-recipe' ),
		'separate_items_with_commas' => __( 'Separate Regions with commas', 'cbtb-recipe' ),
		'add_or_remove_items'        => __( 'Add or remove regions', 'cbtb-recipe' ),
		'choose_from_most_used'      => __( 'Choose from the most used World Cuisine', 'cbtb-recipe' ),
		'not_found'                  => __( 'No World Cuisine found.', 'cbtb-recipe' ),
		'menu_name'                  => __( 'World Cuisine', 'cbtb-recipe' ),
	);

	$worldCuisineConfig = array(
		'hierarchical'          => false,
		'labels'                => $worldCuisineLabels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'world-cuisine' ),
		'show_in_rest'          => true,
	);


	if ( ! is_blog_installed() ) {
		return;
	}

	register_taxonomy( 'world-cuisine', RecipePlugin::$post_type_name, $worldCuisineConfig );
	register_taxonomy( 'meal-type', RecipePlugin::$post_type_name, $mealTypeConfig );
}

add_action( 'init', __NAMESPACE__ . '\create_recipe_taxonomies', 0 );

