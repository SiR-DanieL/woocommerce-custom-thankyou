<?php
/**
 * Plugin Name: WC Custom Thank You
 * Plugin URI: http://nicolamustone.it/projects/woocommerce-custom-thankyou/
 * Description: A WooCommerce extension that allows you to define e custom Thank you page.
 * Version: 1.0.0
 * Author: Nicola Mustone
 * Author URI: http://nicolamustone.it
 * Requires at least: 4.1
 * Tested up to: 4.3
 *
 * Text Domain: woocommerce-custom-thankyou
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Required functions
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Check if WooCommerce is active, and if it isn't, disable the plugin.
 */
if ( ! is_woocommerce_active() ) {
	return;
}

class WC_Custom_Thankyou {
	/**
	 * Class instance
	 *
	 * @static
	 * @access protected
	 * @var WC_Custom_Thankyou
	 */
	protected static $instance;

	/**
	 * Thank you page ID
	 *
	 * @var int
	 */
	public $page_id;

	/**
	 * Main WC_Custom_Thankyou Instance
	 *
	 * Ensures only one instance of WC_Custom_Thankyou is loaded or can be loaded.
	 *
	 * @static
	 * @see wc_custom_thankyou()
	 * @return WC_Custom_Thankyou - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-custom-thankyou' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-custom-thankyou' ), '1.0.0' );
	}

	/**
	 * __construct
	 */
	public function __construct() {
		// Set up localization
		$this->load_plugin_textdomain();

		$this->page_id = get_option( 'woocommerce_custom_thankyou_page_id' );

		add_action( 'template_redirect', array( $this, 'custom_redirect_after_purchase' ) );
		add_filter( 'the_content', array( $this, 'custom_thankyou_page_content' ) );

		// Templates
		add_action( 'woocommerce_custom_thankyou_failed', array( $this, 'failed' ), 10 );
		add_action( 'woocommerce_custom_thankyou_successful', array( $this, 'header' ), 10 );
		add_action( 'woocommerce_custom_thankyou_successful', array( $this, 'table' ), 20 );
		add_action( 'woocommerce_custom_thankyou_successful', array( $this, 'customer_details' ), 30 );

		// Admin Settings
		add_filter( 'woocommerce_payment_gateways_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * Redirects the customer to the custom Thank you page
	 */
	public function custom_redirect_after_purchase() {
		global $wp;

		if ( is_checkout() && ! empty( $wp->query_vars['order-received'] ) ) {
			$order_id  = absint( $wp->query_vars['order-received'] );
			$order_key = wc_clean( $_GET['key'] );

			$redirect  = get_permalink( $this->page_id );
			$redirect .= get_option( 'permalink_structure' ) === '' ? '&' : '?';
			$redirect .= 'order=' . $order_id . '&key=' . $order_key;

			wp_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Prints the custom Thank you page content before the templates
	 *
	 * @param string $content
	 * @return string
	 */
	public function custom_thankyou_page_content( $content ) {
		// Check if is the correct page
		if ( ! is_page( $this->page_id ) ) {
			return $content;
		}

		// check if the order ID exists
		if ( ! isset( $_GET['key'] ) || ! isset( $_GET['order'] ) ) {
			return $content;
		}

		$order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $_GET['order'] ) );
		$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( $_GET['key'] ) );
		$order     = wc_get_order( $order_id );


		if ( $order->id != $order_id || $order->order_key != $order_key ) {
			return $content;
		}

		ob_start();

		// Check that the order is valid
		if ( ! $order ) {
			// The order can't be returned by WooCommerce - Just say thank you
			?><p><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce-custom-thankyou' ), null ); ?></p><?php
		} else {
			if ( $order->has_status( 'failed' ) ) {
				// Order failed - Print error messages and ask to pay again

				/**
				 * @hooked wc_custom_thankyou_failed - 10
				 */
				do_action( 'woocommerce_custom_thankyou_failed', $order );
			} else {
				// The order is successfull - print the complete order review

				/**
				 * @hooked wc_custom_thankyou_header - 10
				 * @hooked wc_custom_thankyou_table - 20
				 * @hooked wc_custom_thankyou_customer_details - 30
				 */
				do_action( 'woocommerce_custom_thankyou_successful', $order );
			}
		}

		$content .= ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Loads the failed.php template
	 */
	public function failed( $order ) {
		wc_get_template( 'thankyou/failed.php', array( 'order' => $order ), '', $this->plugin_path() . '/templates/' );
	}

	/**
	 * Loads the header.php template
	 */
	public function header( $order ) {
		wc_get_template( 'thankyou/header.php', array( 'order' => $order ), '', $this->plugin_path() . '/templates/' );
	}

	/**
	 * Loads the table.php template
	 */
	public function table( $order ) {
		wc_get_template( 'thankyou/table.php', array( 'order' => $order ), '', $this->plugin_path() . '/templates/' );
	}

	/**
	 * Loads the customer-details.php template
	 */
	public function customer_details( $order ) {
		wc_get_template( 'thankyou/customer-details.php', array( 'order' => $order ), '', $this->plugin_path() . '/templates/' );
	}

	/**
	 * Add the Thank you page dropdown in Settings > Checkout
	 *
	 * @param array $settings
	 * @return array
	 */
	public function add_settings( $settings ) {
		$settings[] = array( 'title' => __( 'Custom Thank You', 'woocommerce-custom-thankyou' ), 'type' => 'title', 'id' => 'custom_thankyou_options' );

		$settings[] = array(
			'title'    => __( 'Thank You Page', 'woocommerce-custom-thankyou' ),
			'id'       => 'woocommerce_custom_thankyou_page_id',
			'type'     => 'single_select_page',
			'default'  => '',
			'class'    => 'wc-enhanced-select-nostd',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		);

		$settings[] = array( 'type' => 'sectionend', 'id' => 'custom_thankyou_options' );

		return $settings;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales are found in:
	 *        WP_LANG_DIR/woocommerce-custom-thankyou/woocommerce-custom-thankyou-LOCALE.mo
	 *        woocommerce-custom-thankyou/languages/woocommerce-custom-thankyou-LOCALE.mo (which if not found falls back to:)
	 *        WP_LANG_DIR/plugins/woocommerce-custom-thankyou-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-custom-thankyou' );

		load_textdomain( 'woocommerce-custom-thankyou', WP_LANG_DIR . '/woocommerce-custom-thankyou/woocommerce-custom-thankyou-' . $locale . '.mo' );
		load_textdomain( 'woocommerce-custom-thankyou', WP_LANG_DIR . '/plugins/woocommerce-custom-thankyou-' . $locale . '.mo' );

		load_plugin_textdomain( 'woocommerce-custom-thankyou', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

/**
 * Returns the main instance of WC Customer Messages to prevent the need to use globals.
 *
 * @return WooCommerce_Customer_Messages
 */
function wc_custom_thankyou() {
	return WC_Custom_Thankyou::instance();
}

// Let's start the game!
wc_custom_thankyou();
