<?php
/**
 * Klarna Checkout remarketing second email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/klarna-remarketing-second.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p>Hi,</p>
	<p>Your shopping cart at <?php echo wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ); ?> has been reserved and is waiting for your return.</p>

	<p>Your cart:</p>

	<ul>
		<?php
		$incomplete_order = wc_get_order( $order_id );
		$order_items = $incomplete_order->get_items();
		foreach ( $order_items as $order_item ) {
			echo '<li>' . $order_item['quantity'] . ' * ' . $order_item['name'] . ' - ' . wc_price( $order_item['total'] / $order_item['quantity'] ) . '</li>';
		}
		?>
	</ul>

	<?php
	$postal_code = $incomplete_order->get_billing_postcode();
	$email = $incomplete_order->get_billing_email();
	$remarketing_url = add_query_arg(
		array(
			'cart_id' => $order_id,
			'postal_code' => $postal_code,
			'email' => $email,
		),
		wc_get_checkout_url()
	);
	?>
	<p><a href="<?php echo $remarketing_url; ?>">Go to checkout</a></p>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
