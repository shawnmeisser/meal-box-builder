<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Coupon_Manager {

    /**
     * Apply a manual coupon to the cart and persist it in session.
     *
     * @param string $code The coupon code to apply.
     * @return true|WP_Error True if applied, or a WP_Error if failed.
     */
    public static function apply_manual_coupon( string $code ) {
        if ( ! WC()->cart ) {
            return new WP_Error( 'no_cart', 'Cart not initialized.' );
        }

        $coupon = new WC_Coupon( $code );
        if ( ! $coupon->get_id() ) {
            return new WP_Error( 'invalid_coupon', 'Coupon not found.' );
        }

        $result = WC()->cart->apply_coupon( $code );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Store coupon in session under custom key
        $list = (array) WC()->session->get( 'custom_coupons', [] );
        $list[] = $code;
        WC()->session->set( 'custom_coupons', array_unique( $list ) );

        WC()->cart->calculate_totals();
        return true;
    }

    /**
     * Get all manually applied coupons stored in session.
     *
     * @return string[] List of coupon codes
     */
    public static function get_manual_coupons(): array {
        return (array) WC()->session->get( 'custom_coupons', [] );
    }

    /**
     * Re-apply every manual coupon to the current cart.
     */
    public static function reapply_manual_coupons() {
        foreach ( self::get_manual_coupons() as $code ) {
            if ( ! in_array( $code, WC()->cart->get_applied_coupons(), true ) ) {
                WC()->cart->apply_coupon( $code );
            }
        }
    }

    /**
     * Clear all manual coupons from the session.
     */
    public static function clear_manual_coupons() {
        WC()->session->set( 'custom_coupons', [] );
    }

    /**
     * Remove a specific manual coupon from the session list.
     *
     * @param string $code The coupon code to remove.
     */
    public static function remove_manual_coupon( string $code ) {
        $list = self::get_manual_coupons();
        $list = array_filter( $list, fn( $c ) => $c !== $code );
        WC()->session->set( 'custom_coupons', $list );
    }
}
