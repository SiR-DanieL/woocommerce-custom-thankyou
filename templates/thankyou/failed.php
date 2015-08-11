<p><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'woocommerce-custom-thankyou' ); ?></p>

<p><?php
	if ( is_user_logged_in() )
		_e( 'Please attempt your purchase again or go to your account page.', 'woocommerce-custom-thankyou' );
	else
		_e( 'Please attempt your purchase again.', 'woocommerce-custom-thankyou' );
?></p>

<p>
	<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce-custom-thankyou' ) ?></a>
	<?php if ( is_user_logged_in() ) : ?>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php _e( 'My Account', 'woocommerce-custom-thankyou' ); ?></a>
	<?php endif; ?>
</p>
