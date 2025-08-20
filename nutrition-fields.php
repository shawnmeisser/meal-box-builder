<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─────────────────────────────────────────────────────────
// Add "Nutrition Info" Tab to WooCommerce Product Editor
// ─────────────────────────────────────────────────────────
add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
    $tabs['nutrition'] = [
        'label'    => __( 'Nutrition Info', 'woocommerce' ),
        'target'   => 'nutrition_product_data',
        'class'    => [ 'show_if_simple' ],
        'priority' => 15,
    ];
    return $tabs;
});

// ─────────────────────────────────────────────────────────
// Render Fields Inside Nutrition Tab
// ─────────────────────────────────────────────────────────
add_action( 'woocommerce_product_data_panels', function() {
    global $post;

    echo '<div id="nutrition_product_data" class="panel woocommerce_options_panel"><div class="options_group">';

    // Nutritional value fields (key => label)
    $fields = [
        '_nutrition_serving_size'         => 'Serving Size (g)',
        '_nutrition_servings_per_container' => 'Servings per Container',
        '_nutrition_calories'             => 'Calories',
        '_nutrition_total_fat'            => 'Total Fat (g)',
        '_nutrition_total_fat_dv'         => '%DV - Total Fat',
        '_nutrition_saturated_fat'        => 'Saturated Fat (g)',
        '_nutrition_saturated_fat_dv'     => '%DV - Saturated Fat',
        '_nutrition_trans_fat'            => 'Trans Fat (g)',
        '_nutrition_cholesterol'          => 'Cholesterol (mg)',
        '_nutrition_cholesterol_dv'       => '%DV - Cholesterol',
        '_nutrition_sodium'               => 'Sodium (mg)',
        '_nutrition_sodium_dv'            => '%DV - Sodium',
        '_nutrition_carbs'                => 'Total Carbohydrates (g)',
        '_nutrition_carbs_dv'             => '%DV - Total Carbohydrates',
        '_nutrition_fiber'                => 'Dietary Fiber (g)',
        '_nutrition_fiber_dv'             => '%DV - Fiber',
        '_nutrition_sugars'               => 'Total Sugars (g)',
        '_nutrition_added_sugars'         => 'Added Sugars (g)',
        '_nutrition_added_sugars_dv'      => '%DV - Added Sugars',
        '_nutrition_protein'              => 'Protein (g)',
        '_nutrition_protein_dv'           => '%DV - Protein',
        '_nutrition_vit_d'                => 'Vitamin D (mcg)',
        '_nutrition_vit_d_dv'             => '%DV - Vitamin D',
        '_nutrition_calcium'              => 'Calcium (mg)',
        '_nutrition_calcium_dv'           => '%DV - Calcium',
        '_nutrition_iron'                 => 'Iron (mg)',
        '_nutrition_iron_dv'              => '%DV - Iron',
        '_nutrition_potassium'            => 'Potassium (mg)',
        '_nutrition_potassium_dv'         => '%DV - Potassium',
        '_nutrition_phosphorus'           => 'Phosphorus (mg)',
        '_nutrition_phosphorus_dv'        => '%DV - Phosphorus',
    ];

    foreach ( $fields as $id => $label ) {
        woocommerce_wp_text_input([
            'id'                => $id,
            'label'             => __( $label, 'woocommerce' ),
            'type'              => 'number',
            'custom_attributes' => [ 'step' => 'any' ],
            'desc_tip'          => false,
            'description'       => ''
        ]);
    }

    // Ingredients textarea
    woocommerce_wp_textarea_input([
        'id'          => '_nutrition_ingredients',
        'label'       => __( 'Ingredients (comma-separated)', 'woocommerce' ),
        'desc_tip'    => true,
        'description' => __( 'Example: Chicken, Rice, Broccoli', 'woocommerce' ),
    ]);

    // Allergens multi-select
    $allergens          = [ 'None', 'Dairy', 'Eggs', 'Fish', 'Shellfish', 'Tree Nuts', 'Peanuts', 'Wheat', 'Soybeans', 'Sesame' ];
    $selected_allergens = get_post_meta( $post->ID, '_nutrition_allergens', true );
    $selected_array     = $selected_allergens ? explode( ',', $selected_allergens ) : [];

    echo '<p class="form-field"><label for="nutrition_allergens">' . __( 'Allergens', 'woocommerce' ) . '</label>';
    echo '<select id="nutrition_allergens" name="_nutrition_allergens[]" multiple style="width: 100%; height: auto;">';

    foreach ( $allergens as $allergen ) {
        $selected = in_array( $allergen, $selected_array, true ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $allergen ) . '" ' . $selected . '>' . esc_html( $allergen ) . '</option>';
    }

    echo '</select></p>';
    echo '</div></div>';
});

// ─────────────────────────────────────────────────────────
// Save Nutrition Field Values on Product Save
// ─────────────────────────────────────────────────────────
add_action( 'woocommerce_process_product_meta', function( $post_id ) {
    $fields = [
        '_nutrition_serving_size',
        '_nutrition_servings_per_container',
        '_nutrition_calories',
        '_nutrition_total_fat',
        '_nutrition_total_fat_dv',
        '_nutrition_saturated_fat',
        '_nutrition_saturated_fat_dv',
        '_nutrition_trans_fat',
        '_nutrition_cholesterol',
        '_nutrition_cholesterol_dv',
        '_nutrition_sodium',
        '_nutrition_sodium_dv',
        '_nutrition_carbs',
        '_nutrition_carbs_dv',
        '_nutrition_fiber',
        '_nutrition_fiber_dv',
        '_nutrition_sugars',
        '_nutrition_added_sugars',
        '_nutrition_added_sugars_dv',
        '_nutrition_protein',
        '_nutrition_protein_dv',
        '_nutrition_vit_d',
        '_nutrition_vit_d_dv',
        '_nutrition_calcium',
        '_nutrition_calcium_dv',
        '_nutrition_iron',
        '_nutrition_iron_dv',
        '_nutrition_potassium',
        '_nutrition_potassium_dv',
        '_nutrition_phosphorus',
        '_nutrition_phosphorus_dv',
        '_nutrition_ingredients',
    ];

    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    // Save allergens — "None" overrides others
    if ( isset( $_POST['_nutrition_allergens'] ) ) {
        $selected = array_map( 'sanitize_text_field', $_POST['_nutrition_allergens'] );

        if ( in_array( 'None', $selected, true ) ) {
            $selected = [ 'None' ];
        }

        update_post_meta( $post_id, '_nutrition_allergens', implode( ',', $selected ) );
    } else {
        delete_post_meta( $post_id, '_nutrition_allergens' );
    }
});
