<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<!-- ✅ STEP 2 WRAPPER -->
<div id="meal-box-step2">
    <div class="meal-builder-wrapper">

        <!-- 🧭 Sidebar: Meal Category Filters -->
        <div class="meal-sidebar">
            <h3>Filter by Conditions</h3>
            <button id="clear-category-filters" class="clear-filters-btn">Clear Filters</button>
            <div class="vertical-category-list">
                <?php
                $categories = get_terms([
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'exclude'    => [1],
                ]);

                foreach ( $categories as $category ) {
                    $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
                    $image_url    = wp_get_attachment_url( $thumbnail_id );
                    ?>
                    <button class="meal-category-filter" data-cat-id="<?php echo esc_attr( $category->term_id ); ?>">
                        <div class="category-thumb-wrap">
                            <?php if ( $image_url ): ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $category->name ); ?>">
                            <?php endif; ?>
                            <span><?php echo esc_html( $category->name ); ?></span>
                        </div>
                    </button>
                    <?php
                }
                ?>
            </div>
        </div>

        <!-- 🍽️ Main Meal Area -->
        <div class="meal-content">
            <div id="meal-default-message">
                <h2>Choose All Your Health Conditions</h2>
            </div>
            <div id="meal-products"></div>
        </div>
    </div>

    <!-- 🧮 Floating Meal Counter -->
    <div id="meal-floating-counter" class="collapsed">
        <div id="selected-meal-list"></div>
        <p>Meals Selected: <span id="selected-count">0</span> / <span id="max-meals">0</span></p>
        <p id="meal-total">Total: $0.00</p>
        <button id="meal-box-continue" disabled>Continue</button>
    </div>

    <!-- 📱 Toggle for Mobile -->
    <div id="meal-counter-toggle">Show Cart</div>
</div>

<?php
// 🧠 Hydration variables (used by JavaScript to restore meal selections and box size)
$session_meals = WC()->session->get( 'selected_meals', [] );
$box_size      = WC()->session->get( 'selected_box_size', 0 );

$enriched_meals = [];

if ( is_array( $session_meals ) && ! empty( $session_meals ) ) {
    foreach ( $session_meals as $meal_id => $qty ) {
        $product = wc_get_product( $meal_id );
        if ( $product && $product->is_type( 'simple' ) ) {
            $enriched_meals[ $meal_id ] = [
                'qty'   => intval( $qty ),
                'name'  => $product->get_name(),
                'price' => floatval( $product->get_price() ),
            ];
        }
    }
}
?>

<!-- 🔁 JavaScript Hydration for selected meals -->
<script>
window.preloadedMeals = <?php echo wp_json_encode( $enriched_meals ); ?>;
window.boxSize = <?php echo intval( $box_size ); ?>;
</script>
