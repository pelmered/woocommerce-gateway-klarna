<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Klarna remarketing first email
 *
 * @class 	WC_Gateway_Klarna_Checkout_Remarketing
 * @extends WC_Email
 */
class WC_Klarna_Remarketing_First_Email extends WC_Email {

	/**
	 * Create an instance of the class.
	 */
	function __construct() {
		$this->id             = 'klarna_remarketing_first';
		$this->customer_email = true;
		$this->title          = __( 'Klarna Remarketing First Email', 'woocommerce-gateway-klarna' );
		$this->description    = __( 'Klarna Remarketing First Email.', 'woocommerce-gateway-klarna' );

		$this->heading     = __( 'Klarna Remarketing First Email', 'woocommerce-gateway-klarna' );
		// translators: placeholder is {blogname}, a variable that will be substituted when email is sent out
		$this->subject     = sprintf( _x( '[%s] Klarna Remarketing first email', 'default email subject for Klarna Remarketing first email', 'woocommerce-gateway-klarna' ), '{blogname}' );

		$this->template_html  = 'klarna-remarketing-first.php';
		$this->template_plain = 'plain-class-klarna-remarketing-first.php';
		$this->template_base  = KLARNA_DIR . 'classes/remarketing-emails/templates/';

		add_action( 'choose_good_action_for_this', array( $this, 'trigger' ) );

		parent::__construct();
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $order_id ) {
		if ( $order_id ) {
			// @TODO: Check if order was completed

			$order           = wc_get_order( $order_id );
			$this->recipient = $order->billing_email;

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}
}
