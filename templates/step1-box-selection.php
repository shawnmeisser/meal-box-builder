<?php
// ✅ Detect active subscription and lock box size based on quantity
$locked_box_size = null;

if (function_exists('wcs_get_users_subscriptions')) {
    $user_id = get_current_user_id();
    $subscriptions = wcs_get_users_subscriptions($user_id);

    foreach ($subscriptions as $subscription) {
        if (!$subscription->has_status('active')) continue;

        foreach ($subscription->get_items() as $item) {
            $qty = $item->get_quantity();
            if ($qty === 10 || $qty === 20) {
                $locked_box_size = $qty;
                break 2;
            }
        }
    }
}
?>

<div id="meal-box-step-wrapper">
  <div id="meal-box-step1">
    <div id="meal-box-selection" class="meal-box-step">
      <div class="step1-inner">

        <!-- 🏷️ Step Heading -->
        <h2>Select Your Box Size</h2>

        <!-- 📦 Box Size Options -->
        <div class="meal-box-options">

          <!-- 🔟 10-Meal Option -->
          <button
            class="box-option <?php echo ($locked_box_size == 10) ? 'selected locked' : (($locked_box_size !== null) ? 'disabled' : ''); ?>"
            data-box-size="10"
            <?php echo ($locked_box_size !== null && $locked_box_size != 10) ? 'disabled' : ''; ?>>
            <div class="box-card">
              <h3>10 Meals</h3>
              <p>Perfect for 1 week</p>
              <p class="shipping-info">Flat Rate Shipping<br>$29.99</p>
            </div>
          </button>

          <!-- 2️⃣0️⃣ 20-Meal Option -->
          <button
            class="box-option <?php echo ($locked_box_size == 20) ? 'selected locked' : (($locked_box_size !== null) ? 'disabled' : ''); ?>"
            data-box-size="20"
            <?php echo ($locked_box_size !== null && $locked_box_size != 20) ? 'disabled' : ''; ?>>
            <div class="box-card">
              <h3>20 Meals</h3>
              <p>Perfect for 2 weeks</p>
              <p class="shipping-info">Free Shipping</p>
            </div>
          </button>

        </div>

        <!-- 🔒 Locked Message -->
        <div id="meal-box-message">
          <?php if ($locked_box_size): ?>
            <p class="locked-message">
              Your subscription includes a <?php echo $locked_box_size; ?>-meal box. This has been selected for you.
            </p>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- 📝 Legal & Technical Disclaimer -->
<?php include plugin_dir_path(__FILE__) . 'partials/disclaimer.php'; ?>
