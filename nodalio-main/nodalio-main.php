<?php
/**
 * Plugin Name: Nodalio Main
 * Plugin URI: https://nodalio.com/
 * Description: Nodalio site control, features general information and other controls
 * Author: Nodalio
 * Author URI: http://nodalio.com/
 * Version: 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Returns the main instance of Nodalio_Main_Class to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Avoori_Product_Social_Sharing
 */
function Nodalio_Main_Class() {
	return Nodalio_Main_Class::instance();
} // End Avoori_Product_Social_Sharing()

Nodalio_Main_Class();

/**
 * Main Nodalio_Main_Class Class
 *
 * @class Nodalio_Main_Class
 * @version	1.0.0
 * @since 1.0.0
 * @package	Avoori Base
 */
final class Nodalio_Main_Class {
	/**
	 * Nodalio_Main_Class The single instance of Nodalio_Main_Class.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The text domain.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $textdomain;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token 				= 'nodalio_main_plugin';
		$this->plugin_url 			= plugin_dir_url( __FILE__ );
		$this->plugin_path 			= plugin_dir_path( __FILE__ );
		$this->version 				= '1.0.0';
		$this->testdomain 			= 'nodalio-main';

		register_activation_hook( __FILE__, array( $this, 'install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

		add_action( 'init', array( $this, 'avoori_base_load_textdomain' ) );

		add_action( 'plugins_loaded', array( $this, 'nodalio_setup_main_plugin' ) );
	}

	/**
	 * Main Nodalio_Main_Class Instance
	 *
	 * Ensures only one instance of Nodalio_Main_Class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Nodalio_Main_Class()
	 * @return Main Nodalio_Main_Class instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Return the text domain for other independent classes
	 *
	 * @since 1.0.0
	 */
	public function textdomain() {
		return $this->textdomain;
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();
	}

	/**
	 * Uninstallation.
	 * Use to clean any settings saved by the plugin
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function uninstall() {
		// Your code here
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	public function nodalio_setup_main_plugin() {

		// Include helper functions
		require_once( $this->plugin_path . 'helper.php' );

		// Combine any number of plugins or helper files
		require_once( $this->plugin_path . 'purge-cache/class-purge-cache.php' );

		add_action( 'admin_menu', array ($this, 'nodalio_admin_menu' ) );

	}

	public function nodalio_admin_menu() {
		global $menu;
		$menu['148.1'] = array(
			0 => '',
			1 => 'update_core',
			2 => 'separator-pre-nodalio',
			3 => '',
			4 => 'wp-menu-separator'
		);
		add_menu_page(
			$page_title		= __( "Nodalio", 'avoori-main' ),
			$menu_title		= __( "Nodalio", 'avoori-main' ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-info',
			$function		= '',
			$icon_url		= '',
			$position		= '148.5'
		);
		add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( "Site Info", 'avoori-main' ),
			$menu_title		= __( "Site Information", 'avoori-main' ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-info',
			$function		= array( $this, 'nodalio_main_info_page' )
		);
		add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( 'Cache', 'avoori-main' ),
			$menu_title		= __( 'Cache', 'avoori-main' ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-cache',
			$function		= array( $this, 'nodalio_main_cache_page' )
		);
		$menu['148.8'] = array(
			0 => '',
			1 => 'update_core',
			2 => 'separator-post-nodalio',
			3 => '',
			4 => 'wp-menu-separator'
		);
	}
	function nodalio_main_info_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Site Information', $this->textdomain ) ?></h1>
			<p><?php _e( "Welcome to your site's control panel, here you can view different essential information.", $this->textdomain ) ?></p>
			<table class="form-table">
				<tr valign="top" class="site-primary-domain">
					<th scope="row"><label><?php _e('Site Primary Domain', $this->textdomain ); ?></label></th>
					<td>
						<code><?php print esc_html( NODALIO_PRIMARY_DOMAIN ) ?></code>
					</td>
				</tr>
				<tr valign="top" class="server-ip">
					<th scope="row"><label><?php _e('Server IP:', $this->textdomain ); ?></label></th>
					<td>
						<code><?php print esc_html( NODALIO_SERVER_IP ) ?></code>
					</td>
				</tr>
				<tr valign="top" class="sftp-access">
					<th scope="row"><label><?php _e('SFTP Access to your site is at:', $this->textdomain ); ?></label></th>
					<td>
						<p class="sftp-hostname"><?php _e('Hostname: ', $this->textdomain ); ?></p><code><?php print esc_html( NODALIO_PRIMARY_DOMAIN ) ?></code>
						<p class="sftp-ip"><?php _e('Or IP: ', $this->textdomain ); ?></p><code><?php print esc_html( NODALIO_SERVER_IP ) ?></code>
						<p class="sftp-port"><?php _e('Port: ', $this->textdomain ); ?></p><code><?php print esc_html( NODALIO_SFTP_PORT ) ?></code>
						<p class="sftp-extra"><?php _e('To access the site via SFTP you may use the initial credentials sent during site creation or obtain new credentials via ', $this->textdomain ); ?><a href="<?php print esc_html( NODALIO_WHITELABEL_DOCS_URL ) ?>"><?php print esc_html( NODALIO_WHITELABEL_DOCS_URL ) ?></a></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	public function nodalio_main_cache_page() {
		$messages = array();
		if ( isset( $_POST['clear_cache'] ) ) {
			$cache = Nodalio_Purge_Cache_Class()->purge();
			if ( is_wp_error( $cache ) ) {
				foreach ( $cache->get_error_messages() as $message ) {
					$message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
					array_push( $messages, $message );
				}
			} else {
				$message = '<div class="notice notice-success"><p>' . __( 'Cache has been cleared.', $this->textdomain ) . '</p></div>';
				array_push( $messages, $message );
			}
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Cache', $this->textdomain ) ?></h1>
			<p><?php _e( "Welcome to the Nodalio clear cache page, here you can clear your site's cache. Our plugin clears cache on most occasions, if for some reason cache has not been cleared, feel free to use the Clear Cache button. ", $this->textdomain ) ?></p>
			<?php 
				if ( ! empty( $messages ) ) {
					foreach ( $messages as $message ) {
						echo $message;
					}
				}
			?>
			<form method="post" class="nodalio-main-cache">
			<table class="form-table">
				<tr class="site-primary-domain">
					<th valign="top"><label><?php _e('Clear Site Cache', $this->textdomain ); ?></label></th>
					<td>
						<button name="clear_cache" id="clear_cache" type="clear_cache" class="button button-primary button-large menu-save"><?php _e('Clear Cache', 'avoori-main' ); ?></button>
					</td>
				</tr>
				</tr>
			</table>
			</form>
		</div>
		<?php
	}

	/**
	 * Load text domain
	 *
	 * @since 1.0.0
	 */
	public function avoori_base_load_textdomain() {

		// Register Translation Files
		load_plugin_textdomain( $this->textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
	}
	
}