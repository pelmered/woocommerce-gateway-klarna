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
		add_action( '', array( $this, 'prefill_cart' ) );
		add_action( '', array( $this, 'add_customer_data' ) );

		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
	}

	public function register_emails( $email_classes ) {
		// include our custom email class
		require_once KLARNA_DIR . '/classes/remarketing-emails/class-klarna-remarketing-first.php';

		// add the email class to the list of email classes that WooCommerce loads
		$email_classes['WC_Klarna_Remarketing_First_Email'] = new WC_Klarna_Remarketing_First_Email();

		return $email_classes;
	}
}
new WC_Gateway_Klarna_Checkout_Remarketing();