<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure $product is valid and published
if ( ! isset( $product ) || ! is_a( $product, 'WC_Product' ) || $product->get_status() !== 'publish' ) return;

// Get post
$post = get_post( $product->get_id() );

// Get nutrition fields
$fields = [
    'serving_size', 'servings_per_container', 'calories', 'total_fat', 'total_fat_dv',
    'saturated_fat', 'saturated_fat_dv', 'trans_fat', 'cholesterol', 'cholesterol_dv',
    'sodium', 'sodium_dv', 'carbs', 'carbs_dv', 'fiber', 'fiber_dv', 'sugars',
    'added_sugars', 'added_sugars_dv', 'protein', 'protein_dv', 'vit_d', 'vit_d_dv',
    'calcium', 'calcium_dv', 'iron', 'iron_dv', 'potassium', 'potassium_dv',
    'phosphorus', 'phosphorus_dv', 'ingredients', 'allergens'
];

foreach ( $fields as $field ) {
    ${$field} = get_post_meta( $product->get_id(), '_nutrition_' . $field, true );
}
?>

<div class="meal-card modern">
    <div class="meal-image-full">
        <?php echo woocommerce_get_product_thumbnail(); ?>
    </div>

    <!-- ✅ Updated nutrition top row order -->
    <div class="meal-nutrition-row">
        <div><?php echo esc_html($calories); ?><br><small>CALORIES</small></div>
        <div><?php echo esc_html($carbs); ?>g<br><small>CARBS</small></div>
        <div><?php echo esc_html($fiber); ?>g<br><small>FIBER</small></div>
        <div><?php echo esc_html($protein); ?>g<br><small>PROTEIN</small></div>
    </div>

    <div class="meal-main-info">
        <div class="meal-title-price">
            <h4><?php echo esc_html( get_the_title( $product->get_id() ) ); ?></h4>
            <span class="price"><?php echo $product->get_price_html(); ?></span>
        </div>

        <div class="meal-short-desc">
            <?php echo apply_filters( 'woocommerce_short_description', $product->get_short_description() ); ?>
        </div>

        <div class="meal-actions-wrapper">
            <div class="quantity-selector" data-meal-id="<?php echo esc_attr($product->get_id()); ?>">
                <button class="qty-down">−</button>
                <input type="number"
                       min="0"
                       max="10"
                       value="0"
                       class="meal-qty"
                       data-meal-id="<?php echo esc_attr($product->get_id()); ?>"
                       data-meal-name="<?php echo esc_attr($product->get_name()); ?>"
                       data-meal-price="<?php echo esc_attr($product->get_price()); ?>" />
                <button class="qty-up">+</button>
            </div>
            <button class="nutrition-toggle">View Nutrition Facts</button>
        </div>

        <div class="nutrition-panel nutrition-label">
            <strong>Nutrition Facts</strong>
            <div class="serving-info">
                <strong>Servings per container:</strong> <?php echo esc_html($servings_per_container); ?><br>
                <strong>Serving size:</strong> <?php echo esc_html($serving_size); ?>g
            </div>

            <div class="calories-row"><strong>Calories</strong> <?php echo esc_html($calories); ?></div>
            <div class="daily-value-label">% Daily Value*</div>
            <table>
                <tr><td><strong>Total Fat</strong> <?php echo esc_html($total_fat); ?>g</td><td><?php echo esc_html($total_fat_dv); ?>%</td></tr>
                <tr><td class="indent">Saturated Fat <?php echo esc_html($saturated_fat); ?>g</td><td><?php echo esc_html($saturated_fat_dv); ?>%</td></tr>
                <tr><td class="indent">Trans Fat <?php echo esc_html($trans_fat); ?>g</td><td>&nbsp;</td></tr>
                <tr><td><strong>Cholesterol</strong> <?php echo esc_html($cholesterol); ?>mg</td><td><?php echo esc_html($cholesterol_dv); ?>%</td></tr>
                <tr><td><strong>Sodium</strong> <?php echo esc_html($sodium); ?>mg</td><td><?php echo esc_html($sodium_dv); ?>%</td></tr>
                <tr><td><strong>Total Carbohydrates</strong> <?php echo esc_html($carbs); ?>g</td><td><?php echo esc_html($carbs_dv); ?>%</td></tr>
                <tr><td class="indent">Dietary Fiber <?php echo esc_html($fiber); ?>g</td><td><?php echo esc_html($fiber_dv); ?>%</td></tr>
                <tr><td class="indent">Total Sugars <?php echo esc_html($sugars); ?>g</td><td>&nbsp;</td></tr>
                <tr><td class="indent more-indent">Includes <?php echo esc_html($added_sugars); ?>g Added Sugars</td><td><?php echo esc_html($added_sugars_dv); ?>%</td></tr>
                <tr><td><strong>Protein</strong> <?php echo esc_html($protein); ?>g</td><td><?php echo esc_html($protein_dv); ?>%</td></tr>
                <tr><td>Vitamin D <?php echo esc_html($vit_d); ?>mcg</td><td><?php echo esc_html($vit_d_dv); ?>%</td></tr>
                <tr><td>Calcium <?php echo esc_html($calcium); ?>mg</td><td><?php echo esc_html($calcium_dv); ?>%</td></tr>
                <tr><td>Iron <?php echo esc_html($iron); ?>mg</td><td><?php echo esc_html($iron_dv); ?>%</td></tr>
                <tr><td>Potassium <?php echo esc_html($potassium); ?>mg</td><td><?php echo esc_html($potassium_dv); ?>%</td></tr>
                <tr><td>Phosphorus <?php echo esc_html($phosphorus); ?>mg</td><td><?php echo esc_html($phosphorus_dv); ?>%</td></tr>
            </table>

            <div class="nutrition-disclaimer">
                *The % Daily Value tells you how much a nutrient in a serving of food contributes to a daily diet.
                2,000 calories a day is used for general nutrition advice.
            </div>

            <p><strong>Allergens:</strong> <?php echo esc_html($allergens); ?></p>
            <p><strong>Ingredients:</strong> <?php echo esc_html($ingredients); ?></p>
        </div>
    </div>
</div>
