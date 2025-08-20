<?php
/*
Plugin Name: Meal Box Builder
Description: A custom plugin for building meal boxes with subscription options.
Version: 1.0
Author: Synergistic Techs
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// ─────────────────────────────────────────────────────────
// Include Core Plugin Files
// ─────────────────────────────────────────────────────────

require_once plugin_dir_path( __FILE__ ) . 'includes/class-meal-box-builder.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-meal-box-checkout-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/nutrition-fields.php';

// Optional: Include coupon manager only if it exists
$coupon_manager_path = plugin_dir_path( __FILE__ ) . 'includes/class-coupon-manager.php';
if ( file_exists( $coupon_manager_path ) ) {
    require_once $coupon_manager_path;
} else {
    error_log( 'Meal Box Builder: missing ' . $coupon_manager_path );
}

// ─────────────────────────────────────────────────────────
// Initialize Plugin
// ─────────────────────────────────────────────────────────

function initialize_meal_box_builder() {
    $builder = new Meal_Box_Builder();

    // Ensure WooCommerce session is started early for all users
    add_action( 'init', array( $builder, 'maybe_start_session' ), 1 );

    // Initialize the checkout manager (handles unified checkout + subscriptions)
    new Meal_Box_Checkout_Manager();
}
add_action( 'plugins_loaded', 'initialize_meal_box_builder' );

// ─────────────────────────────────────────────────────────
// AJAX Handler: Get Meal Details by IDs
// Used to rebuild cart state when returning to Step 2
// ─────────────────────────────────────────────────────────

add_action( 'wp_ajax_get_meal_details',        'mbb_get_meal_details' );
add_action( 'wp_ajax_nopriv_get_meal_details', 'mbb_get_meal_details' );

function mbb_get_meal_details() {
    if ( ! isset( $_POST['meal_ids'] ) ) {
        wp_send_json_error( 'No meal IDs provided.' );
    }

    $ids_raw = json_decode( stripslashes( $_POST['meal_ids'] ), true );

    if ( ! is_array( $ids_raw ) ) {
        wp_send_json_error( 'Invalid meal IDs.' );
    }

    $response = [];

    foreach ( $ids_raw as $meal_id ) {
        $meal_id = intval( $meal_id );
        $product = wc_get_product( $meal_id );

        if ( $product ) {
            $response[ $meal_id ] = [
                'name'  => $product->get_name(),
                'price' => $product->get_price(),
            ];
        }
    }

    wp_send_json_success( $response );
}
