<?php
/**
 * Klarna Checkout remarketing first email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/klarna-remarketing-first.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p><?php _e( 'You have items in the cart', 'woocommerce' ); ?></p>

	<?php
	$incomplete_order = wc_get_order( $order_id );
	$order_items = $incomplete_order->get_items();
	foreach ( $order_items as $order_item ) {
		echo $order_item['product_id'];
	}
	?>

	<?php
	$remarketing_url = add_query_arg(
		array(
			'cart_id' => $order_id,
		),
		wc_get_checkout_url()
	);
	?>
	<p><a href="<?php echo $remarketing_url; ?>">Finalize your order</a></p>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
