<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Klarna remarketing second email
 *
 * @class 	WC_Gateway_Klarna_Checkout_Remarketing
 * @extends WC_Email
 */
class WC_Klarna_Remarketing_Second_Email extends WC_Email {

	/**
	 * Create an instance of the class.
	 */
	function __construct() {
		$this->id             = 'klarna_remarketing_second';
		$this->customer_email = true;
		$this->description    = __( 'Klarna Remarketing Second Email.', 'woocommerce-gateway-klarna' );

		$this->heading     = __( 'We’re still holding the cart for you!', 'woocommerce-gateway-klarna' );
		// translators: placeholder is {blogname}, a variable that will be substituted when email is sent out
		$this->subject     = sprintf( _x( '[%s] We’re still holding the cart for you!', 'default email subject for Klarna Remarketing second email', 'woocommerce-gateway-klarna' ), '{blogname}' );

		$this->template_html  = 'klarna-remarketing-second.php';
		$this->template_base  = KLARNA_DIR . 'includes/remarketing/emails/templates/';

		parent::__construct();
	}

	/**
	 * Email settings.
	 */
	function init_form_fields() {
		$this->form_fields    = array(
			'enabled'         => array(
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable this email', 'woocommerce' ),
				'default'     => 'yes',
			),
			'subject'         => array(
				'title'       => __( 'Email subject', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->subject . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
			'heading'         => array(
				'title'       => __( 'Email heading', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default heading */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->heading . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
			'send_time'       => array(
				'title'       => __( 'How many days after first email should this email be sent?', 'woocommerce' ),
				'type'        => 'number',
				'default'     => 3,
			),
		);
	}

	/**
	 * trigger function.
	 *
	 * @param  $order_id WooCommerce order ID.
	 * @access public
	 * @return void
	 */
	public function trigger( $order_id ) {
		if ( $order_id ) {
			$order = wc_get_order( $order_id );

			// If order status has changed, do nothing.
			if ( 'kco-incomplete' !== $order->get_status() ) {
				return;
			}

			$this->recipient = $order->get_billing_email();

			if ( ! $this->is_enabled() || ! $this->recipient ) {
				return;
			}

			// Don't send the email if order has no items.
			$order = wc_get_order( $order_id );
			$order_items = $order->get_items();
			if ( empty( $order_items ) ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_email_content( $order_id ), $this->get_headers(), $this->get_attachments() );

			do_action( 'klarna_remarketing_second_email_sent', $order_id );
		}
	}

	/**
	 * get_content_html function.
	 *
	 * @param  $order_id WooCommerce order ID.
	 * @access public
	 * @return string
	 */
	function get_email_content( $order_id ) {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
				'order_id'      => $order_id,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Get email content type. Always text/html for remarketing emails.
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'text/html';
	}

	/**
	 * Schedule second email.
	 *
	 * @param $order_id
	 */
	public function schedule_second_email( $order_id ) {
		$order = wc_get_order( $order_id );

		// We need to have both email and postcode before we can proceed.
		if ( ! $order->get_billing_postcode() || ! $order->get_billing_email() ) {
			return;
		}

		// Check if already scheduled
		if ( ! wp_next_scheduled( 'kco_remarketing_email_1', $order_id ) ) {
			wp_schedule_single_event( time() + 3600, 'kco_remarketing_email_1', array( $order_id ) );
		}
	}

}
