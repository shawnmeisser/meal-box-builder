<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user      = wp_get_current_user();
$selected_meals    = WC()->session->get( 'selected_meals', [] );
$has_bought_before = is_user_logged_in() && wc_get_customer_order_count( $current_user->ID ) > 0;

if ( function_exists( 'WC' ) && WC()->cart && ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
    WC()->cart->get_cart();
}

$totals         = WC()->cart->get_totals();
$pre_total      = $totals['subtotal'];
$shipping_total = $totals['shipping_total'];
$discount       = $totals['discount_total'];
$post_total     = $totals['total'];
?>

<style>
.checkout-wrapper {
  max-width: 980px;
  margin: 0px auto;
  padding: 0px 10px;
  font-family: inherit;
}

.checkout-heading {
  text-align: center;
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 40px;
}

.checkout-content {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  padding: 30px;
}

.step3-section {
  margin-bottom: 40px;
}

.step3-section h3 {
  font-size: 1.5rem;
  margin-bottom: 20px;
  text-align: center;
}

.toggle-group {
  display: flex;
  gap: 20px;
  margin-top: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.toggle-option {
  display: flex;
  align-items: center;
  gap: 10px;
}

#subscription-frequency label {
  margin-right: 15px;
}

.step3-section > div[style*="margin-top: 15px;"] {
  text-align: center;
}

.meal-summary-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.meal-summary-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  border-bottom: 1px dashed #ddd;
  font-size: 1rem;
  gap: 10px;
  flex-wrap: nowrap;
}

.meal-summary-list li span:first-child {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.meal-summary-list li strong {
  white-space: nowrap;
}

.meal-summary-list li span:first-child strong {
  display: inline-block;
  white-space: normal;
  word-break: break-word;
}

.meal-summary-list li img {
  width: 80px;
  height: 55px;
  border-radius: 6px;
  object-fit: cover;
}

.summary-bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 30px;
  padding: 0 12px;
  margin-top: 10px;
}

#custom-coupon-wrap {
  flex: 1;
  max-width: 400px;
}

#custom-coupon-wrap label {
  font-weight: 500;
  display: block;
  margin-bottom: 6px;
}

/* ✅ FIXED DESKTOP COUPON ROW */
#custom-coupon-wrap .input-row {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: nowrap;
}

#custom-coupon-code {
  padding: 8px 12px;
  flex-grow: 1;
  flex-shrink: 1;
  border: 1px solid #ccc;
  border-radius: 4px;
  min-width: 0;
}

#apply-custom-coupon {
  background: #0f4c5c;
  color: #fff;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}

#apply-custom-coupon:hover {
  background: #0c3c4a;
}

#custom-coupon-message {
  margin-top: 10px;
  font-size: 14px;
  padding: 10px;
  border-radius: 6px;
  background-color: #d1e7dd;
  color: #0f5132;
  border: 1px solid #badbcc;
  display: none;
}

.totals {
  flex: 0 0 auto;
  text-align: right;
  font-size: 1rem;
}

.totals p {
  margin: 4px 0;
}

.totals strong {
  font-weight: 600;
}

.step3-actions {
  display: flex;
  justify-content: flex-end;
  padding: 0 12px;
  gap: 14px;
  margin-top: 20px;
}

.step3-actions button {
  padding: 12px 24px;
  font-size: 1rem;
  font-weight: 600;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.step3-actions .button {
  background: #ccc;
}

.step3-actions .button.alt {
  background: #0f4c5c;
  color: #fff;
}

.step3-actions .button.alt:hover {
  background: #0c3c4a;
}

/* ------------------- MEDIA QUERIES ------------------- */

@media (max-width: 768px) {
  .checkout-wrapper {
    padding: 30px 16px;
  }

  .checkout-content {
    padding: 24px 16px;
    border-radius: 16px;
  }

  .step3-actions {
    flex-direction: column;
    align-items: stretch;
    padding: 0;
  }

  .totals {
    text-align: left;
  }

  .summary-bottom-row {
    flex-direction: column;
    padding: 0;
  }

  #custom-coupon-wrap .input-row {
    flex-direction: column;
    align-items: stretch;
  }

  #apply-custom-coupon {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .meal-summary-list li img {
    display: none;
  }
}

@media (max-width: 480px) {
  .checkout-wrapper {
    width: 95%;
    padding: 20px 10px;
    margin: 20px auto;
  }

  .checkout-content {
    padding: 20px 14px;
    border-radius: 16px;
  }

  .step3-section h3 {
    font-size: 1.25rem;
    margin-bottom: 16px;
  }

  .meal-summary-list li {
    padding: 10px 12px;
    font-size: 0.95rem;
  }

  .meal-summary-list li span:first-child {
    gap: 10px;
  }

  #custom-coupon-wrap .input-row {
    flex-direction: column;
    gap: 8px;
  }

  #apply-custom-coupon {
    width: 100%;
    padding: 10px 0;
  }

  .step3-actions button {
    font-size: 1rem;
    padding: 12px 0;
    width: 100%;
  }

  .totals {
    font-size: 0.95rem;
    text-align: left;
  }

  .meal-summary-list li img {
    width: 60px;
    height: 45px;
  }
}

@media (max-width: 768px) {
  .summary-bottom-row {
    flex-direction: column;
    align-items: stretch;
    padding: 0;
    gap: 30px;
  }

  #custom-coupon-wrap {
    order: -1;
    margin-top: 20px;
  }

  .totals {
    text-align: right;
    font-size: 1rem;
    padding-right: 8px;
  }

  .totals p {
    margin: 6px 0;
  }
}

@media (max-width: 768px) {
  .meal-summary-list li {
    flex-direction: column;
    align-items: flex-start;
  }

  .meal-summary-list li span:last-child {
    margin-top: 6px;
    font-size: 1rem;
    color: #333;
    text-align: left;
  }
}
</style>


<div id="meal-box-selection" class="checkout-wrapper">
  <h1 class="checkout-heading">Your Box Summary</h1>
  <div class="checkout-content">

    <div class="step3-section">
      <h3>Choose How You’d Like to Purchase</h3>
      <div class="toggle-group">
        <label class="toggle-option">
          <input type="radio" name="purchase_type" value="onetime" checked>
          <span>One-Time Purchase</span>
        </label>
        <label class="toggle-option">
          <input type="radio" name="purchase_type" value="subscription">
          <span>Subscribe &amp; Save 10%</span>
        </label>
      </div>
      <div id="subscription-frequency" style="display:none; margin-top:15px;">
        <label><input type="radio" name="subscription_interval" value="2" checked> Every 2 Weeks</label>
        <label><input type="radio" name="subscription_interval" value="1"> Monthly</label>
      </div>
      <?php if ( ! $has_bought_before ): ?>
      <div style="margin-top: 15px;">
        <label>
          <input type="checkbox" id="first-time-check">
          Is this your first time ordering? <strong>Get 50% off</strong> this box.
        </label>
        <p id="first-time-note" class="woocommerce-message" style="display:none; margin-top:10px;">
          🎉 Your 50% discount will be applied at checkout.
        </p>
      </div>
      <?php endif; ?>
    </div>

    <div class="step3-section" style="text-align:left;">
      <h3 style="text-align:left; padding-left:12px;">Your Selected Meals</h3>
      <ul class="meal-summary-list">
        <?php foreach ( $selected_meals as $meal_id => $data ) :
          if ( is_array( $data ) ) {
            $qty   = intval( $data['qty'] );
            $name  = $data['name'];
            $price = floatval( $data['price'] );
          } else {
            $qty     = intval( $data );
            $product = wc_get_product( $meal_id );
            if ( ! $product ) continue;
            $name  = $product->get_name();
            $price = floatval( $product->get_price() );
          }
          $line_total = $price * $qty;
          $thumbnail = get_the_post_thumbnail_url( $meal_id, 'medium' );
        ?>
          <li data-meal-id="<?= esc_attr( $meal_id ); ?>">
        <span style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0;">
  <?php if ( $thumbnail ) : ?>
    <img src="<?= esc_url( $thumbnail ); ?>" alt="" style="flex-shrink: 0;">
  <?php endif; ?>
  <span style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; flex: 1; min-width: 0;">
    <strong style="white-space: nowrap;"><?= esc_html( $name ); ?></strong>
    <span style="font-size: 0.9rem; color: #555; white-space: nowrap;">× <?= $qty; ?></span>
  </span>
</span>

<span style="white-space: nowrap; text-align: right; min-width: 60px;"><?= '$' . number_format( $line_total, 2 ); ?></span>

          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="summary-bottom-row">
      <div id="custom-coupon-wrap" style="display:none;">
        <label for="custom-coupon-code">Have a coupon?</label>
        <div class="input-row">
          <input type="text" id="custom-coupon-code" placeholder="Enter coupon code">
          <button id="apply-custom-coupon">Apply</button>
        </div>
        <p id="custom-coupon-message"></p>
      </div>

      <div class="totals">
        <p><strong>Subtotal:</strong> <?= wc_price( $pre_total ); ?></p>
        <p><strong>Shipping:</strong> <?= $shipping_total > 0 ? wc_price( $shipping_total ) : 'Free'; ?></p>
        <?php if ( $discount > 0 ): ?>
          <p><strong>Discount:</strong> -<?= wc_price( $discount ); ?></p>
        <?php endif; ?>
        <p><strong>Total:</strong> <?= wc_price( $post_total ); ?></p>
      </div>
    </div>

    <div class="step3-actions">
      <button id="go-back-step2" class="button">Back</button>
      <button id="go-to-checkout" class="button alt">Continue to Checkout</button>
    </div>

  </div>
</div>

<script>
jQuery(function($){
  $('input[name="purchase_type"]').on('change', function(){
    $('#subscription-frequency').slideToggle($(this).val()==='subscription');
  });

  $('#first-time-check').on('change', function(){
    $('#first-time-note').toggle(this.checked);
    $('#custom-coupon-wrap').toggle(!this.checked);
  });

  if ( ! $('#first-time-check').is(':checked') ) {
    $('#custom-coupon-wrap').show();
  }

  $('#apply-custom-coupon').on('click', function(){
    const code = $('#custom-coupon-code').val().trim(),
          msg  = $('#custom-coupon-message');
    if ( ! code ) { msg.text('Please enter a coupon code.').show(); return; }
    msg.text('Applying coupon...').show();
    $.post(mealBoxAjax.ajax_url, {
      action: 'apply_custom_coupon',
      nonce: mealBoxAjax.nonce,
      coupon_code: code
    }).done(function(resp){
      if ( resp.success ) {
        msg.html('✅ Coupon applied! Your discount will be reflected at checkout.').show();
        $.post(mealBoxAjax.ajax_url, {
          action: 'load_review_template',
          nonce: mealBoxAjax.nonce
        }).done(function(r){
          if ( r.success ) {
            $('#meal-box-selection').replaceWith(r.data.html);
          }
        });
      } else {
        msg.text('❌ ' + (resp.data?.message||'Coupon failed.')).show();
      }
    }).fail(function(){
      msg.text('❌ Error applying coupon.').show();
    });
  });

  $('#go-to-checkout').on('click', function(){
    const payload = {
      action: 'set_subscription_choice',
      purchase_type: $('input[name="purchase_type"]:checked').val(),
      interval: $('input[name="subscription_interval"]:checked').val(),
      first_time_discount: $('#first-time-check').is(':checked'),
      nonce: mealBoxAjax.nonce
    };
    const manualCoupon = $('#custom-coupon-code').val().trim();
    if ( manualCoupon ) payload.coupon_code = manualCoupon;
    $.post(mealBoxAjax.ajax_url, payload, function(r){
      if ( r.success ) {
        window.location.href = mealBoxAjax.checkout_url || '<?= esc_url( wc_get_checkout_url() ); ?>';
      } else {
        alert('Something went wrong setting your selection. Please try again.');
      }
    });
  });

  $('#go-back-step2').on('click', function(){
    $.post(mealBoxAjax.ajax_url, {
      action: 'load_meal_selection_template',
      nonce: mealBoxAjax.nonce
    }, function(r){
      if ( r.success ) {
        $('.checkout-wrapper').replaceWith(r.data.html);
      } else {
        alert('Failed to load previous step.');
      }
    }).fail(function(){
      alert('AJAX error while trying to go back to Step 2.');
    });
  });
});
</script>
<script>
  window.scrollTo(0, 0);
</script>

<script>
window.preloadedMeals = <?php echo wp_json_encode( $selected_meals ); ?>;
</script>
