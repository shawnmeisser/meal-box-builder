<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Meal_Box_Checkout_Manager {

    public function __construct() {
        // AJAX: Subscription & Coupon Handling
        add_action( 'wp_ajax_set_subscription_choice',        [ $this, 'handle_checkout_choice' ] );
        add_action( 'wp_ajax_nopriv_set_subscription_choice', [ $this, 'handle_checkout_choice' ] );
        add_action( 'wp_ajax_apply_custom_coupon',            [ $this, 'apply_custom_coupon' ] );
        add_action( 'wp_ajax_nopriv_apply_custom_coupon',     [ $this, 'apply_custom_coupon' ] );

        // AJAX: Load final step (Step 3)
        add_action( 'wp_ajax_load_review_template',           [ $this, 'load_review_template' ] );
        add_action( 'wp_ajax_nopriv_load_review_template',    [ $this, 'load_review_template' ] );

        // AJAX: Reset session
        add_action( 'wp_ajax_reset_meal_box_flow',            [ $this, 'reset_meal_box_flow' ] );
        add_action( 'wp_ajax_nopriv_reset_meal_box_flow',     [ $this, 'reset_meal_box_flow' ] );

        // AJAX (future): Remove or clear meals manually
        add_action( 'wp_ajax_remove_selected_meal',           [ $this, 'remove_selected_meal' ] );
        add_action( 'wp_ajax_clear_selected_meals',           [ $this, 'clear_selected_meals' ] );

        // WooCommerce Hooks
        add_action( 'woocommerce_checkout_create_order', [ $this, 'attach_meal_meta' ], 20 );
        add_action( 'woocommerce_checkout_order_processed',     [ $this, 'maybe_create_subscription' ],   20, 3 );
        add_action( 'woocommerce_cart_loaded_from_session',     [ $this, 'maybe_apply_coupons' ],         20 );
        add_action( 'woocommerce_checkout_update_order_review', [ $this, 'ensure_discount_flag' ] );
        add_action( 'woocommerce_subscription_validate_items',  [ $this, 'validate_subscription_item_changes' ], 10, 2 );

        // Suppress WooCommerce REST API coupon errors
        add_filter( 'woocommerce_rest_pre_dispatch',  [ $this, 'maybe_skip_coupons_on_store_api' ], 10, 3 );
        add_filter( 'woocommerce_coupon_error',       [ $this, 'suppress_duplicate_coupon_error' ], 10, 3 );
    }

    public function attach_meal_meta( $order ) {
        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) return;

        $order->update_meta_data( 'selected_meals', WC()->session->get( 'selected_meals', [] ) );
        $order->update_meta_data( 'box_size', WC()->session->get( 'selected_box_size', '' ) );
        $order->update_meta_data( 'subscription_type', WC()->session->get( 'subscription_type', '' ) );
        $order->update_meta_data( 'subscription_interval', WC()->session->get( 'subscription_interval', '' ) );
        $order->update_meta_data( 'first_time_discount', WC()->session->get( 'apply_first_time_discount', false ) );

        $category_ids = WC()->session->get( 'selected_categories', [] );
        if ( ! empty( $category_ids ) && is_array( $category_ids ) ) {
            $category_names = [];
            foreach ( $category_ids as $term_id ) {
                $term = get_term_by( 'id', $term_id, 'product_cat' );
                if ( $term ) {
                    $category_names[] = $term->name;
                }
            }
            $order->update_meta_data( 'selected_categories', $category_names );
        }

        $order->save();
    }

    public function maybe_create_subscription( $order_id, $posted_data, $order ) {
        if ( ! function_exists( 'wcs_create_subscription' ) ) return;
        if ( WC()->session->get( 'subscription_type' ) !== 'subscription' ) return;

        $subscription = wcs_create_subscription( [
            'order_id'           => $order_id,
            'customer_id'        => $order->get_customer_id(),
            'billing_period'     => WC()->session->get( 'subscription_interval', '2' ) === '1' ? 'month' : 'week',
            'billing_interval'   => WC()->session->get( 'subscription_interval', '2' ) === '1' ? 1 : 2,
        ] );

        if ( is_wp_error( $subscription ) ) return;

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product || ! $product->is_type( 'simple' ) ) continue;

            $subscription->add_product( $product, $item->get_quantity(), [
                'subtotal'     => $item->get_subtotal(),
                'total'        => $item->get_total(),
                'subtotal_tax' => $item->get_subtotal_tax(),
                'total_tax'    => $item->get_total_tax(),
                'taxes'        => $item->get_taxes(),
            ] );
        }

        $subscription->calculate_totals();
        $subscription->update_status( 'pending' );
    }

    public function validate_subscription_item_changes( $errors, $sub ) {}
    public function remove_selected_meal() {}
    public function clear_selected_meals() {}

    public function handle_checkout_choice() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'meal_box_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid security token.' ], 400 );
        }

        WC()->session->set( 'subscription_type', sanitize_text_field( $_POST['purchase_type'] ?? 'onetime' ) );
        WC()->session->set( 'subscription_interval', sanitize_text_field( $_POST['interval'] ?? '2' ) );
        WC()->session->set( 'apply_first_time_discount', filter_var( $_POST['first_time_discount'] ?? false, FILTER_VALIDATE_BOOLEAN ) );

        if ( WC()->cart ) {
            if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
                WC()->cart->get_cart();
            }

            if ( ! empty( $_POST['coupon_code'] ) && class_exists( 'Coupon_Manager' ) ) {
                Coupon_Manager::apply_manual_coupon( sanitize_text_field( $_POST['coupon_code'] ) );
            }

            WC()->cart->empty_cart();
            $meals = WC()->session->get( 'selected_meals', [] );

            if ( is_array( $meals ) ) {
                foreach ( $meals as $meal_id => $data ) {
                    $qty = is_array( $data ) ? intval( $data['qty'] ) : intval( $data );
                    WC()->cart->add_to_cart( intval( $meal_id ), $qty );
                }
            }

            if ( class_exists( 'Coupon_Manager' ) ) {
                Coupon_Manager::reapply_manual_coupons();
            }

            if ( class_exists( 'Coupon_Manager' ) && empty( Coupon_Manager::get_manual_coupons() ) ) {
                $this->maybe_apply_coupons();
            }

            WC()->cart->calculate_totals();
        }

        wp_send_json_success([
            'message'  => 'Meals saved. Redirecting…',
            'redirect' => wc_get_checkout_url(),
        ]);
    }

    public function apply_custom_coupon() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'meal_box_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid security token.' ], 400 );
        }

        if ( empty( $_POST['coupon_code'] ) ) {
            wp_send_json_error( [ 'message' => 'Coupon code missing.' ], 400 );
        }

        if ( WC()->cart && ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
            WC()->cart->get_cart();
        }

        $code = sanitize_text_field( $_POST['coupon_code'] );

        if ( class_exists( 'Coupon_Manager' ) ) {
            $result = Coupon_Manager::apply_manual_coupon( $code );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
            }
        }

        wp_send_json_success([
            'message' => 'Coupon applied successfully.',
            'coupon'  => $code,
        ]);
    }

    public function load_review_template() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        if ( WC()->cart ) {
            if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
                WC()->cart->get_cart();
            }

            if ( class_exists( 'Coupon_Manager' ) ) {
                Coupon_Manager::reapply_manual_coupons();
            }

            $this->maybe_apply_coupons();
            WC()->cart->calculate_totals();
        }

        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/step3-review-submit.php';
        wp_send_json_success([ 'html' => ob_get_clean() ]);
    }

    public function maybe_apply_coupons() {
        if ( ! WC()->cart ) return;

        $manual = class_exists( 'Coupon_Manager' ) ? Coupon_Manager::get_manual_coupons() : [];

        if ( empty( $manual ) ) {
            $has_first_time = WC()->session->get( 'apply_first_time_discount', false );
            $is_subscribing = WC()->session->get( 'subscription_type' ) === 'subscription';

            if ( $has_first_time && ! WC()->cart->has_discount( 'First50' ) ) {
                WC()->cart->apply_coupon( 'First50' );
            }

            if ( $is_subscribing && ! WC()->cart->has_discount( 'Subscribe10' ) ) {
                WC()->cart->apply_coupon( 'Subscribe10' );
            }
        }
    }

    public function ensure_discount_flag( $posted_data ) {}

    public function maybe_skip_coupons_on_store_api( $response, $server, $request ) {
        if ( 0 === strpos( $request->get_route(), '/wc/store/v1/checkout' ) ) {
            remove_action( 'woocommerce_cart_loaded_from_session', [ $this, 'maybe_apply_coupons' ], 20 );

            if ( is_wp_error( $response ) ) {
                $msg = $response->get_error_message();
                if ( false !== stripos( $msg, 'already applied' ) ) {
                    return new WP_REST_Response( [], 200 );
                }
            }
        }

        return $response;
    }

    public function suppress_duplicate_coupon_error( $error, $err_code, $coupon ) {
        if ( 'already_applied' === $err_code ) {
            $code = $coupon instanceof WC_Coupon ? $coupon->get_code() : '';
            if ( in_array( $code, [ 'First50', 'Subscribe10' ], true ) ) {
                return '';
            }
        }

        return $error;
    }

    public function reset_meal_box_flow() {
        if ( WC()->cart ) {
            WC()->cart->empty_cart();
        }

        if ( class_exists( 'Coupon_Manager' ) ) {
            Coupon_Manager::clear_manual_coupons();
        }

        $keys_to_clear = [
            'selected_meals',
            'selected_box_size',
            'subscription_type',
            'subscription_interval',
            'apply_first_time_discount',
            'selected_categories',
        ];

        foreach ( $keys_to_clear as $key ) {
            WC()->session->__unset( $key );
        }

        wp_send_json_success([ 'message' => 'Flow reset.' ]);
    }
}
