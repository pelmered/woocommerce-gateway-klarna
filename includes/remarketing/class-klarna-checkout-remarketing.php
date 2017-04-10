<?php
/**
 * Klarna Checkout remarketing
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 2.4.0
 *
 * @package WC_Gateway_Klarna
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 *
 */

/**
 * Class that handles Klarna Checkout remarketing.
 * @property WC_Logger log
 */
class WC_Gateway_Klarna_Checkout_Remarketing {

	/**
	 * WC_Gateway_Klarna_Checkout_Remarketing constructor.
	 */
	public function __construct() {
		// @TODO: Pre-fill the cart and iframe based on the URL
		// https://www.store.com/checkout?cartid=1a2b3c4d&email=fornamn.efternamn@klarna.com&postal_code=12345
		add_action( 'template_redirect', array( $this, 'prefill_cart' ) );
		add_action( 'template_redirect', array( $this, 'add_customer_data' ) );

		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
	}

	public function register_emails( $email_classes ) {
		// include our custom email class
		require_once KLARNA_DIR . '/includes/remarketing/emails/class-klarna-remarketing-first.php';

		// add the email class to the list of email classes that WooCommerce loads
		$email_classes['WC_Klarna_Remarketing_First_Email'] = new WC_Klarna_Remarketing_First_Email();

		return $email_classes;
	}

	public function prefill_cart() {
		if ( ! is_admin() ) {
			// Do nothing if cart_id parameter doesn't exist.
			if ( ! $_GET['cart_id'] ) {
				return;
			}

			// If cart has anything in it, empty it.
			if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
				WC()->cart->empty_cart();
			}

			// Get the order from URL parameters.
			$incomplete_order_id = intval( $_GET['cart_id'] );
			$incomplete_order = wc_get_order( $incomplete_order_id );

			if ( $incomplete_order ) {
				$order_items = $incomplete_order->get_items();
			}


			/*
			$product_id = 99;
			$found = false;
			// Check if product already in cart
			if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $_product->id === $product_id ) {
						$found = true;
					}
				}
				// if product not found, add it
				if ( ! $found ) {
					WC()->cart->add_to_cart( $product_id );
				}
			} else {
				// if no products in cart, add it
				WC()->cart->add_to_cart( $product_id );
			}
			*/
		}
	}

	public function add_customer_data() {

	}
}
new WC_Gateway_Klarna_Checkout_Remarketing();
