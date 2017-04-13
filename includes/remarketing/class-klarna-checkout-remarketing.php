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
		// Add kill switch setting to KCO settings page
		add_filter( 'klarna_checkout_form_fields', array( $this, 'kill_switch' ) );

		// Use hooks if the option is checked.
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( 'yes' === $checkout_settings['enable_remarketing_emails'] ) {
			// https://www.store.com/checkout?cartid=1a2b3c4d&email=fornamn.efternamn@klarna.com&postal_code=12345
			add_action( 'template_redirect', array( $this, 'prefill_cart' ) );

			add_action( 'kco_create_order', array( $this, 'add_customer_data_kco' ) );
			add_action( 'kco_update_order', array( $this, 'add_customer_data_kco' ) );

			// Register emails
			add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );

			// Schedule emails when KCO Incomplete with a valid email is created.
			add_action( 'kco_after_prepare_wc_order', array( $this, 'schedule_first_email' ) );
			add_action( 'klarna_remarketing_first_email_sent', array( $this, 'schedule_second_email' ) );
			add_action( 'klarna_remarketing_second_email_sent', array( $this, 'schedule_third_email' ) );

			// @TODO: Schedule email 10 minutes after user email address is captured.
			add_action( 'kco_remarketing_email_1', array( $this, 'trigger_first_email' ) );
			add_action( 'kco_remarketing_email_2', array( $this, 'trigger_second_email' ) );
			add_action( 'kco_remarketing_email_3', array( $this, 'trigger_third_email' ) );

			add_action( 'woocommerce_order_status_kco-incomplete_to_pending', array( $this, 'unschedule_emails_on_completion' ) );
			add_action( 'klarna_before_kco_confirmation', array( $this, 'unschedule_emails_on_completion' ) );
		}
	}

	public function trigger_first_email( $order_id ) {
		$wc_email = WC_Emails::instance();
		$email    = $wc_email->emails['WC_Klarna_Remarketing_First_Email'];

		return $email->trigger( $order_id );
	}

	public function trigger_second_email( $order_id ) {
		$wc_email = WC_Emails::instance();
		$email    = $wc_email->emails['WC_Klarna_Remarketing_Second_Email'];

		return $email->trigger( $order_id );
	}

	public function trigger_third_email( $order_id ) {
		$wc_email = WC_Emails::instance();
		$email    = $wc_email->emails['WC_Klarna_Remarketing_Third_Email'];

		return $email->trigger( $order_id );
	}

	public function unschedule_emails_on_completion( $order_id ) {
		if ( $timestamp_1 = wp_next_scheduled( 'kco_remarketing_email_1', array( $order_id ) ) ) {
			wp_unschedule_event( $timestamp_1, 'kco_remarketing_email_1', array( $order_id ) );
		}

		if ( $timestamp_2 = wp_next_scheduled( 'kco_remarketing_email_1', array( $order_id ) ) ) {
			wp_unschedule_event( $timestamp_2, 'kco_remarketing_email_1', array( $order_id ) );
		}

		if ( $timestamp_3 = wp_next_scheduled( 'kco_remarketing_email_1', array( $order_id ) ) ) {
			wp_unschedule_event( $timestamp_3, 'kco_remarketing_email_1', array( $order_id ) );
		}
	}

	public function kill_switch( $kco_settings ) {
		$kco_settings['enable_remarketing_emails'] = array(
			'title'   => __( 'Enable Klarna Checkout remarketing emails', 'woocommerce-gateway-klarna' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Klarna Checkout remarketing emails', 'woocommerce-gateway-klarna' ),
			'default' => 'no',
		);

		return $kco_settings;
	}

	public function register_emails( $email_classes ) {
		// include our custom email class
		require_once KLARNA_DIR . '/includes/remarketing/emails/class-klarna-remarketing-first.php';
		require_once KLARNA_DIR . '/includes/remarketing/emails/class-klarna-remarketing-second.php';
		require_once KLARNA_DIR . '/includes/remarketing/emails/class-klarna-remarketing-third.php';

		// add the email class to the list of email classes that WooCommerce loads
		$email_classes['WC_Klarna_Remarketing_First_Email'] = new WC_Klarna_Remarketing_First_Email();
		$email_classes['WC_Klarna_Remarketing_Second_Email'] = new WC_Klarna_Remarketing_Second_Email();
		$email_classes['WC_Klarna_Remarketing_Third_Email'] = new WC_Klarna_Remarketing_Third_Email();

		return $email_classes;
	}

	/**
	 * Pre-fill WooCommerce cart when visiting remarketing URL.
	 *
	 * 1. Check if cart_id exists
	 * 2. Check is cart_id matches a KCO Incomplete order
	 * 3. Empty current cart
	 * 4. Add all order items from KCO Incomplete order to cart
	 */
	public function prefill_cart() {
		// @TODO: REMOVE
		// wp_schedule_single_event( time() + 3600, 'kco_remarketing_email_1', array( 1137 ) );

		if ( ! is_admin() ) {
			if ( ! is_klarna_checkout() ) {
				return;
			}

			// Do nothing if cart_id, postal_code or email parameter doesn't exist.
			if ( ! $_GET['email'] || ! $_GET['postal_code'] || ! $_GET['cart_id'] ) {
				return;
			}

			// Get the order from URL parameters.
			$incomplete_order_id = intval( $_GET['cart_id'] );
			$incomplete_order = wc_get_order( $incomplete_order_id );

			// Only proceed if WooCommerce order has KCO Incomplete status
			if ( 'kco-incomplete' !== $incomplete_order->get_status() ) {
				return;
			}

			if ( $incomplete_order ) {
				// Use the KCO Incomplete order to complete checkout.
				WC()->session->set( 'ongoing_klarna_order', $incomplete_order_id );

				$order_items = $incomplete_order->get_items();
				if ( ! empty( $order_items ) ) {
					// If cart has anything in it, empty it.
					if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
						WC()->cart->empty_cart();
					}

					foreach ( $order_items as $order_item ) {
						WC()->cart->add_to_cart(
							$order_item['product_id'],
							$order_item['quantity'],
							$order_item['variation_id']
						);
					}
				}
			}
		}
	}

	/**
	 * Add email and postal code to KCO iframe.
	 *
	 * @param $kco_data
	 *
	 * @return mixed
	 */
	public function add_customer_data_kco( $kco_data ) {
		// Only do this if email contains all three parameters
		if ( ! $_GET['email'] || ! $_GET['postal_code'] || ! $_GET['cart_id'] ) {
			return $kco_data;
		}

		if ( $_GET['email'] ) {
			if ( is_email( $_GET['email'] ) ) {
				$kco_data['shipping_address']['email'] = $_GET['email'];
			}
		}

		if ( $_GET['postal_code'] ) {
			$kco_data['shipping_address']['postal_code'] = sanitize_text_field( $_GET['postal_code'] );
		}

		return $kco_data;
	}

	/**
	 * Schedule first email.
	 *
	 * @param $order_id
	 */
	public function schedule_first_email( $order_id ) {
		$order = wc_get_order( $order_id );

		// We need to have both email and postcode before we can proceed.
		if ( ! $order->get_billing_postcode() || ! $order->get_billing_email() ) {
			return;
		}

		// Only schedule if we have a real email.
		if ( 'guest_checkout@klarna.com' === $order->get_billing_email() ) {
			return;
		}

		$wc_email        = WC_Emails::instance();
		$email           = $wc_email->emails['WC_Klarna_Remarketing_First_Email'];
		$send_time_hours = $email->settings['send_time'];

		// Check if already scheduled
		if ( ! wp_next_scheduled( 'kco_remarketing_email_1', array( $order_id ) ) ) {
			wp_schedule_single_event( time() + $send_time_hours * 3600, 'kco_remarketing_email_1', array( $order_id ) );
		}
	}

	/**
	 * Schedule first email.
	 *
	 * @param $order_id
	 */
	public function schedule_second_email( $order_id ) {
		$order = wc_get_order( $order_id );

		// We need to have both email and postcode before we can proceed.
		if ( ! $order->get_billing_postcode() || ! $order->get_billing_email() ) {
			return;
		}

		$wc_email       = WC_Emails::instance();
		$email          = $wc_email->emails['WC_Klarna_Remarketing_Second_Email'];
		$send_time_days = $email->settings['send_time'];

		// Check if already scheduled
		if ( ! wp_next_scheduled( 'kco_remarketing_email_2', array( $order_id ) ) ) {
			wp_schedule_single_event( time() + $send_time_days * 24 * 3600, 'kco_remarketing_email_2', array( $order_id ) );
		}
	}

	/**
	 * Schedule first email.
	 *
	 * @param $order_id
	 */
	public function schedule_third_email( $order_id ) {
		$order = wc_get_order( $order_id );

		// We need to have both email and postcode before we can proceed.
		if ( ! $order->get_billing_postcode() || ! $order->get_billing_email() ) {
			return;
		}

		$wc_email       = WC_Emails::instance();
		$email          = $wc_email->emails['WC_Klarna_Remarketing_Third_Email'];
		$send_time_days = $email->settings['send_time'];

		// Check if already scheduled
		if ( ! wp_next_scheduled( 'kco_remarketing_email_3', array( $order_id ) ) ) {
			wp_schedule_single_event( time() + $send_time_days * 24 * 3600, 'kco_remarketing_email_3', array( $order_id ) );
		}
	}

}
new WC_Gateway_Klarna_Checkout_Remarketing();
