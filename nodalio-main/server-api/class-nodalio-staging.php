<?php
/**
 * Nodalio site actions
 * Uses Nodalio Server API
 *
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Returns the main instance of Nodalio_Site_Staging_Class to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Nodalio_Site_Staging_Class
 */
function Nodalio_Site_Staging_Class() {
	return Nodalio_Site_Staging_Class::instance();
} // End Nodalio_Site_Staging_Class()

Nodalio_Site_Staging_Class();

/**
 * Main Nodalio_Site_Staging_Class Class
 *
 * @class Nodalio_Site_Staging_Class
 * @version	1.0.0
 * @since 1.0.0
 * @package	Nodalio_Site_Staging_Class
 */
final class Nodalio_Site_Staging_Class {
	/**
	 * Nodalio_Site_Staging_Class The single instance of Nodalio_Site_Staging_Class.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
    private static $_instance = null;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'nodalio_setup_site_staging' ), 502 );
	}

	/**
	 * Main Nodalio_Site_Staging_Class Instance
	 *
	 * Ensures only one instance of Nodalio_Site_Staging_Class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Nodalio_Site_Staging_Class()
	 * @return Main Nodalio_Site_Staging_Class instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	public function nodalio_setup_site_staging() {
		add_action( 'admin_menu', array( $this, 'nodalio_main_add_site_staging_pages' ) );
		//require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-commands.php' );
	}
	
	public function nodalio_main_add_site_staging_pages() {
		add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( 'Staging', 'avoori-main' ),
			$menu_title		= __( 'Staging', 'avoori-main' ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-site-staging',
			$function		= array( $this, 'nodalio_main_add_site_staging_actions' )
		);
	}

	public function nodalio_main_add_site_staging_actions() {
		$cache_selected = get_option( 'nodalio_main_active_cache', 'server-cache' );
		$messages = array();
		if ( isset( $_POST['nodalio_move_to_staging'] ) ) {
			//require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-command.php' );
			$staging = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'sitemovetostaging', '' );
			$staging = $staging->runCommand();
			if ( is_wp_error( $staging ) ) {
				foreach ( $staging->get_error_messages() as $message ) {
					$message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
					array_push( $messages, $message );
				}
			} else {
				$staging = json_decode( wp_remote_retrieve_body( $staging ) );
				if ( $staging->result == "success" ) {
					$message = '<div class="notice notice-success"><p>' . __( 'Successfully Moved the site to staging.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					array_push( $messages, $message );
				} else {
					$message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to move the site to staging. ' . $staging->data , NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					array_push( $messages, $message );
				}
			}
		} else if ( isset( $_POST['nodalio_move_to_live'] ) ) {
			//require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-command.php' );
			$live = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'sitemovetolive' );
			$live = $live->runCommand();
			if ( is_wp_error( $staging ) ) {
				foreach ( $live->get_error_messages() as $message ) {
					$message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
					array_push( $messages, $message );
				}
			} else {
				$live = json_decode( wp_remote_retrieve_body( $live ) );
				if ( $live->result == "success" ) {
					$message = '<div class="notice notice-success"><p>' . __( 'Successfully Moved the site to live.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					array_push( $messages, $message );
				} else {
					$message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to move the site to live. ' . $live->data , NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					array_push( $messages, $message );
				}
			}
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Staging', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></h1>
			<p><?php _e( "Welcome to the Nodalio clear cache page, here you can clear your site's cache. Our plugin clears cache on most occasions, if for some reason cache has not been cleared, feel free to use the Clear Cache button. ", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></p>
			<?php 
				if ( ! empty( $messages ) ) {
					foreach ( $messages as $message ) {
						echo $message;
					}
				}
			?>
			<form method="post" class="nodalio-main-cache-settings">
			<table class="form-table">
				<tr class="site-move-to-staging">
					<th valign="top"><label for="nodalio_move_to_staging" ><?php _e('Staging', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></label></th>
					<td><?php
                        if ( get_option( 'nodalio_last_staging_move', false ) ) {
                            ?>
                            <p class="nodalio-staging-last-move"><?php _e('Site has been moved to staging on: ', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); echo get_option( 'nodalio_last_staging_move', false );
                        }
                        ?>
                        <button name="nodalio_move_to_staging" id="nodalio_move_to_staging" type="nodalio_move_to_staging" class="button button-primary button-large menu-save"><?php _e('Move to Staging', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></button>
					</td>
				</tr>
				<tr class="site-move-to-live">
					<th valign="top"><label for="nodalio_move_to_live" ><?php _e('Live', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></label></th>
					<td>
                        <button name="nodalio_move_to_live" id="nodalio_move_to_live" type="nodalio_move_to_live" class="button button-primary button-large menu-save"><?php _e('Move to Live', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></button>
					</td>
				</tr>
			</table>
			</form>
		</div>
		<?php
	}
    

}