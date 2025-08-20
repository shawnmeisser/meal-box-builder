<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Meal_Box_Builder {

    public function __construct() {
        // Frontend Shortcode
        add_shortcode( 'meal_box_builder', array( $this, 'render_box_selection' ) );

        // Enqueue CSS & JS
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX Handlers
        $this->register_ajax( 'select_box_size' );
        $this->register_ajax( 'load_meals_by_category' );
        $this->register_ajax( 'load_meal_selection_template' );
        $this->register_ajax( 'get_meal_categories' );
        $this->register_ajax( 'save_selected_meals' );
        $this->register_ajax( 'load_review_template' );
        $this->register_ajax( 'get_meal_details' );
        $this->register_ajax( 'reset_meal_box_flow' ); // ✅ Reset flow handler
    }

    protected function register_ajax( $action ) {
        add_action( "wp_ajax_$action", array( $this, $action ) );
        add_action( "wp_ajax_nopriv_$action", array( $this, $action ) );
    }

    public function enqueue_assets() {
        if ( ! is_admin() ) {
            wp_enqueue_style(
                'meal-box-builder-style',
                plugin_dir_url( __FILE__ ) . '../assets/css/style.css',
                array(),
                filemtime( plugin_dir_path( __FILE__ ) . '../assets/css/style.css' )
            );

            wp_enqueue_script(
                'meal-box-builder-script',
                plugin_dir_url( __FILE__ ) . '../assets/js/meal-box-builder.js',
                array( 'jquery' ),
                filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/meal-box-builder.js' ),
                true
            );

            wp_localize_script( 'meal-box-builder-script', 'mealBoxAjax', array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'meal_box_nonce' ),
                'checkout_url' => wc_get_checkout_url(),
            ));
        }
    }

    public function render_box_selection() {
        ob_start();

        // ✅ Preload from past order if editing subscription
        if ( isset($_GET['from_order']) ) {
            $order_id = absint($_GET['from_order']);
            $order = wc_get_order($order_id);

            if ($order) {
                $box_size = $order->get_meta('box_size');
                $meals = $order->get_meta('selected_meals'); // format: [meal_id => qty]
                ?>
                <script>
                    localStorage.setItem('mealBoxSelections', JSON.stringify({
                        meals: <?php echo json_encode($meals); ?>,
                        maxMeals: <?php echo intval($box_size); ?>
                    }));
                </script>
                <?php
            }
        }

        include plugin_dir_path( __FILE__ ) . '../templates/step1-box-selection.php';
        return ob_get_clean();
    }

    public function select_box_size() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );
        $box_size = isset( $_POST['box_size'] ) ? intval( $_POST['box_size'] ) : 0;

        if ( in_array( $box_size, [10, 20], true ) ) {
            WC()->session->set( 'selected_box_size', $box_size );
            WC()->session->set( 'selected_meals', [] );

            if ( WC()->cart ) {
                WC()->cart->empty_cart();
            }

            if ( class_exists( 'Coupon_Manager' ) ) {
                Coupon_Manager::clear_manual_coupons();
            }

            wp_send_json_success([
                'message'  => 'Box size selected.',
                'box_size' => $box_size
            ]);
        }

        wp_send_json_error([ 'message' => 'Invalid box size selected.' ]);
    }

    public function load_meal_selection_template() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/step2-meal-selection.php';

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

        echo '<script>';
        echo 'window.preloadedMeals = ' . wp_json_encode( $enriched_meals ) . ';';
        echo 'window.boxSize = ' . intval( $box_size ) . ';';
        echo '</script>';

        wp_send_json_success([ 'html' => ob_get_clean() ]);
    }

    public function get_meal_categories() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'exclude'    => [1],
        ]);

        ob_start();
        foreach ( $terms as $term ) {
            $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
            $image_url = wp_get_attachment_url( $thumbnail_id );

            echo '<button class="meal-category-filter" data-category="' . esc_attr( $term->term_id ) . '">';
            echo '<div class="category-thumb-wrap">';
            if ( $image_url ) {
                echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $term->name ) . '">';
            }
            echo '<span>' . esc_html( $term->name ) . '</span>';
            echo '</div></button>';
        }

        wp_send_json_success([ 'html' => ob_get_clean() ]);
    }

    public function load_meals_by_category() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        $category_ids = array_map( 'intval', $_POST['category_ids'] ?? [] );
        $tax_query = ['relation' => 'AND'];

        foreach ( $category_ids as $cat_id ) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [ $cat_id ],
                'operator' => 'IN',
            ];
        }

        $query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => $tax_query,
            'post_status'    => 'publish',
        ]);

        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="meal-grid">';
            while ( $query->have_posts() ) {
                $query->the_post();
                global $product;
                include plugin_dir_path( __FILE__ ) . '../templates/partials/meal-card.php';
            }
            echo '</div>';
        }

        wp_reset_postdata();
        wp_send_json_success([ 'html' => ob_get_clean() ]);
    }

    public function save_selected_meals() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'meal_box_nonce' ) ) {
            wp_send_json_error([ 'message' => 'Invalid nonce' ], 400);
        }

        if ( ! isset( $_POST['meals'] ) || ! is_array( $_POST['meals'] ) ) {
            wp_send_json_error([ 'message' => 'Invalid meal data.' ]);
        }

        $sanitized_meals = [];

        foreach ( $_POST['meals'] as $meal_id => $qty ) {
            $meal_id = intval( $meal_id );
            $qty = intval( $qty );

            if ( $meal_id > 0 && $qty > 0 ) {
                $sanitized_meals[ $meal_id ] = $qty;
            }
        }

        WC()->session->set( 'selected_meals', $sanitized_meals );
        wp_send_json_success([ 'message' => 'Meals saved.' ]);
    }

    public function load_review_template() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) && WC()->cart ) {
            WC()->cart->get_cart();
        }

        if ( WC()->cart ) {
            WC()->cart->empty_cart();

            $meals = WC()->session->get( 'selected_meals', [] );
            if ( is_array( $meals ) ) {
                foreach ( $meals as $meal_id => $data ) {
                    $qty = is_array( $data ) ? intval( $data['qty'] ) : intval( $data );
                    WC()->cart->add_to_cart( intval( $meal_id ), $qty );
                }
            }

            foreach ( WC()->cart->get_applied_coupons() as $code ) {
                WC()->cart->apply_coupon( sanitize_text_field( $code ) );
            }

            WC()->cart->calculate_totals();
        }

        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/step3-review-submit.php';
        wp_send_json_success([ 'html' => ob_get_clean() ]);
    }

    public function get_meal_details() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        $meal_ids = array_map( 'intval', $_POST['meal_ids'] ?? [] ); // ✅ FIXED HERE
        $results = [];

        foreach ( $meal_ids as $id ) {
            $product = wc_get_product( $id );
            if ( $product ) {
                $results[ $id ] = [
                    'name'  => $product->get_name(),
                    'price' => $product->get_price(),
                ];
            }
        }

        wp_send_json_success([ 'data' => $results ]);
    }

    public function maybe_start_session() {
        if ( class_exists( 'WooCommerce' ) && isset( WC()->session ) && ! WC()->session->has_session() ) {
            WC()->session->set_customer_session_cookie( true );
        }
    }

    public function reset_meal_box_flow() {
        check_ajax_referer( 'meal_box_nonce', 'nonce' );

        if ( WC()->cart ) {
            WC()->cart->empty_cart();
        }

        WC()->session->__unset( 'selected_meals' );
        WC()->session->__unset( 'selected_box_size' );
        WC()->session->__unset( 'apply_first_time_discount' );
        WC()->session->__unset( 'subscription_type' );
        WC()->session->__unset( 'subscription_interval' );

        if ( class_exists( 'Coupon_Manager' ) ) {
            Coupon_Manager::clear_manual_coupons();
        }

        wp_send_json_success([ 'message' => 'Session and cart cleared.' ]);
    }
}
