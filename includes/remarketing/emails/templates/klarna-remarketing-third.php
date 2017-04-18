<?php
/**
 * Klarna Checkout remarketing third email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/klarna-remarketing-third.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p>Hi,</p>
	<p>Your shopping cart at <?php echo wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ); ?> is still available!</p>

	<p>Your cart:</p>

	<?php $incomplete_order = wc_get_order( $order_id ); ?>

	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 16px; border: none; border-collapse:collapse" border="0">
		<tbody>
		<?php echo wc_get_email_order_items( $incomplete_order, array( 'show_image' => true ) ); ?>
		</tbody>
	</table>

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
