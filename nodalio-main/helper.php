<?php
/* Helper Functions for Avoori Base */
/* Since 1.0.0 */

 // Exit if accessed directly
 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WooCommerce is active
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'avoo_is_woocommerce_active' ) ) {

	function avoo_is_woocommerce_active() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

}