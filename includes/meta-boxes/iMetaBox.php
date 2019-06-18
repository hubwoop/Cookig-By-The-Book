<?php


namespace ProAtCooking\Recipe;

include_once CBTB_PLUGIN_ROOT . '/includes/pre-flight.php';

use WP_Post;

/**
 * Interface iMetaBox
 * Defines the methods necessary for meta boxes to be handled by RecipeBlockEditor.
 * @package ProAtCooking\Recipe
 */
interface iMetaBox {

    /**
     * @param WP_Post $post
     * Echo some HTML that displays your meta box here.
     */
    public function display(WP_Post $post ): void;

    /**
     * @param int $recipe_id
     * Should call update_post_meta with own meta key.
     */
    public function save(int $recipe_id ): void;

    /**
     * @param string $input
     * Method responsible for sanitizing the received user input.
     * @return mixed
     */
    public function sanitize(string $input );

    /**
     * Should return the meta boxes own name / identifier.
     * @return string
     */
    public function get_name(): string;
}