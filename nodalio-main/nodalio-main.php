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
	 * The plugin path.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	/**
	 * The plugin url.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

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

		define( 'NODALIO_MAIN_PLUGIN_DIR', $this->plugin_path );
		define( 'NODALIO_MAIN_PLUGIN_URI', $this->plugin_url );
		define( 'NODALIO_MAIN_PLUGIN_TEXTDOMAIN', $this->testdomain );
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

		//require_once( $this->plugin_path . 'purge-cache/class-purge-cache.php' );

		require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-command.php' );

		require_once( $this->plugin_path . 'server-api/class-nodalio-cache.php' );

		require_once( $this->plugin_path . 'server-api/class-nodalio-backups.php' );

		require_once( $this->plugin_path . 'server-api/class-nodalio-staging.php' );

		add_action( 'admin_menu', array ($this, 'nodalio_admin_menu' ) );

		add_action( 'wp_ajax_nodalio_get_site_info', array( $this, 'get_site_info_ajax' ) );

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
			$page_title		= __( "Nodalio", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$menu_title		= __( "Nodalio", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-info',
			$function		= '',
			$icon_url		= '',
			$position		= '148.5'
		);
		add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( "General", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$menu_title		= __( "General", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-info',
			$function		= array( $this, 'nodalio_main_info_page' )
		);
		/* add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( 'Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$menu_title		= __( 'Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-cache',
			$function		= array( $this, 'nodalio_main_cache_page' )
		); */
		$menu['148.8'] = array(
			0 => '',
			1 => 'update_core',
			2 => 'separator-post-nodalio',
			3 => '',
			4 => 'wp-menu-separator'
		);
	}

	/**
	 * Try to figure out on what plan is this site installed
	 * if non are found, sets the plan as enterprise and opens all features (only in the plugin)
	 */
	private function nodalio_filter_feature_by_plan($type, $server_plan) {
		$featues = array(
			//'keepalive' => array( 'extension' ),
			'wildcard' => array( 'professional', 'business', 'enterprise' ),
			//'appcl' => array( 'extension' ),
			'trafficcontrol' => array( 'enterprise' )
		);
		if ( (!$server_plan) && defined('NODALIO_PLAN') ) {
			$server_plan = NODALIO_PLAN;
		} else if ( (!$server_plan) && !defined('NODALIO_PLAN') ) {
			$server_plan = "enterprise";
		}
		if ( array_key_exists( $type, $featues ) ) {
			if ( in_array( $server_plan, $featues[$type] ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function nodalio_get_site_info() {
		$siteinfo = $this->get_site_info();
		return apply_filters( 'nodalio_get_site_info', $siteinfo );
	}

	private function get_site_info() {
        $currect_day = date("mdy");
        if ( ( get_option( 'nodalio_main_site_info_date_populated', "" ) != $currect_day ) || ( ! get_option( 'nodalio_main_site_info' ) ) ){
			$siteinfo = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'siteinfo', '', 'GET' );
			$siteinfo = $siteinfo->runCommand();
            if ( ! is_wp_error( $siteinfo ) ) {
				$siteinfo = json_decode(wp_remote_retrieve_body( $siteinfo ));
				$siteinfo = json_decode($siteinfo->data);
                update_option( 'nodalio_main_site_info_date_populated', $currect_day );
                update_option( 'nodalio_main_site_info', $siteinfo );
            }
        } else {
            $siteinfo = get_option( 'nodalio_main_site_info' );
        }
        return $siteinfo;
	}

	public function get_site_info_ajax() {
        $currect_day = date("mdy");
		$siteinfo = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'siteinfo', '', 'GET' );
		$siteinfo = $siteinfo->runCommand();
		if ( ! is_wp_error( $siteinfo ) ) {
			$siteinfo = json_decode(wp_remote_retrieve_body( $siteinfo ));
			$siteinfo = json_decode($siteinfo->data);
			update_option( 'nodalio_main_site_info_date_populated', $currect_day );
			update_option( 'nodalio_main_site_info', $siteinfo );
		}
		echo json_encode($siteinfo, JSON_FORCE_OBJECT);
		wp_die();
	}
	
	public function nodalio_main_info_page() {
		$messages = array();
		$siteinfo = $this->get_site_info();
		$ajaxurl = admin_url( 'admin-ajax.php' );
		$success_message = __( 'Refreshed Information.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN );
		//wp_localize_script( 'ajaxscript', 'ajax_object', array( 
		//	"ajaxurl" => $ajaxurl,
		//	"success_message" => __( 'Refreshed Information.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN )
		// ));

		if ( is_wp_error( $siteinfo ) ) {
            foreach ( $siteinfo->get_error_messages() as $message ) {
                $message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
                array_push( $messages, $message );
            }
        }
		?>
		<div class="wrap">
			<h1><?php _e( 'Site Information', $this->textdomain ) ?></h1>
			<a href="#" id="refresh_site_info" class="refresh_site_info page-title-action"><?php _e( 'Refresh Information', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></a>
			<p><?php _e( "Welcome to your site's control panel, here you can view different essential information.", $this->textdomain ) ?></p>
			<?php 
				if ( ! empty( $messages ) ) {
					foreach ( $messages as $message ) {
						echo $message;
					}
                }
            ?>
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
						<p class="sftp-hostname" id="nodalio_info_hostname"><?php _e('Hostname: ', $this->textdomain ); ?></p><code><?php print esc_html( NODALIO_PRIMARY_DOMAIN ) ?></code>
						<p class="sftp-ip" id="nodalio_info_server_ip"><?php _e('Or IP: ', $this->textdomain ); ?></p><code><?php print esc_html( NODALIO_SERVER_IP ) ?></code>
						<p class="sftp-port" id="nodalio_info_port"><?php _e('Port: ', $this->textdomain ); ?></p><code><?php print esc_html( NODALIO_SFTP_PORT ) ?></code>
						<p class="sftp-extra" id="nodalio_info_doc_url"><?php _e('To access the site via SFTP you may use the initial credentials sent during site creation or obtain new credentials via ', $this->textdomain ); ?><a href="<?php print esc_html( NODALIO_WHITELABEL_DOCS_URL ) ?>"><?php print esc_html( NODALIO_WHITELABEL_DOCS_URL ) ?></a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php _e('PHP Version: ', $this->textdomain ); ?></label></th>
					<td><code><?php echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION; ?></code></td>
				</tr><?php
				if ( ! empty( $siteinfo ) && ! is_wp_error( $siteinfo ) && $siteinfo->result != "failure" ) {
					if ( $siteinfo ) {
						?>
						<tr>
							<th scope="row"><label><?php _e('Active Caching tool: ', $this->textdomain ); ?></label></th>
							<td><code id="nodalio_info_cache_tool"><?php echo $siteinfo->cache_tool ?></code></td>
						</tr>
						<tr>
							<th scope="row"><label><?php _e('Site Owner Email: ', $this->textdomain ); ?></label></th>
							<td><code id="nodalio_info_owner_email"><?php echo $siteinfo->owner_email ?></code></td>
						</tr>
						<tr>
							<th scope="row"><label><?php _e('Site Creation Date: ', $this->textdomain ); ?></label></th>
							<td><code id="nodalio_info_creation_date"><?php echo $siteinfo->creation_date ?></code></td>
						</tr>
						<tr>
							<th scope="row"><label><?php _e('Primary Domain SSL Certificate: ', $this->textdomain ); ?></label></th>
							<td><code id="nodalio_info_ssl_certificatel"><?php ($siteinfo->ssl_certificate != "none") ? $SSL = $siteinfo->ssl_certificate . " certificate." : $SSL = "Not installed"; echo $SSL; ?></code></td>
						</tr>
						<?php
						if ( $this->nodalio_filter_feature_by_plan('wildcard', $siteinfo->server_plan) && $siteinfo->domain_wildcard) {
							?>
							<tr>
								<th scope="row"><label><?php _e('Domain Wildcard: ', $this->textdomain ); ?></label></th>
								<td><code id="nodalio_info_domain_wildcard"><?php (($siteinfo->domain_wildcard) && ($siteinfo->domain_wildcard == "enabled") ) ? $domain_wildcard = "Enabled" : $domain_wildcard = "Disabled"; echo $domain_wildcard; ?></code></td>
							</tr><?php
							if (($siteinfo->domain_wildcard) && ($siteinfo->domain_wildcard == "enabled") ) {
								?>
								<tr>
									<th scope="row"><label><?php _e('Domain Wildcard SSL Certificate: ', $this->textdomain ); ?></label></th>
									<td><code id="nodalio_info_domain_wildcard_ssl_certificate"><?php (($siteinfo->domain_wildcard_ssl) && ($siteinfo->domain_wildcard_ssl != "none") ) ? $domain_wildcard_ssl = "Enabled" : $domain_wildcard_ssl = "Disabled"; echo $domain_wildcard_ssl; ?></code></td>
								</tr>
								<?php
							}
						}
						if ( $this->nodalio_filter_feature_by_plan('trafficcontrol', $siteinfo->server_plan) && $siteinfo->traffic_control ) {
							?>
							<tr>
								<th scope="row"><label><?php _e('Traffic Control: ', $this->textdomain ); ?></label></th>
								<td><code id="nodalio_info_traffic_control"><?php ($siteinfo->traffic_control == "true") ? $keepalive = "Enabled" : $keepalive = "Disabled"; echo $keepalive; ?></code></td>
							</tr>
							<?php
						}
						if ($siteinfo->keepalive) {
							?>
							<tr>
								<th scope="row"><label><?php _e('KeepAlive: ', $this->textdomain ); ?></label></th>
								<td><code id="nodalio_info_keepalive"><?php ($siteinfo->keepalive == "true") ? $keepalive = "Enabled" : $keepalive = "Disabled"; echo $keepalive; ?></code></td>
							</tr>
							<?php
						}
						if ($siteinfo->appcl) {
							?>
							<tr>
								<th scope="row"><label><?php _e('Application Cache Lock: ', $this->textdomain ); ?></label></th>
								<td><code id="nodalio_info_appcl"><?php ($siteinfo->appcl == "true") ? $appcl = "Enabled" : $appcl = "Disabled"; echo $appcl; ?></code></td>
							</tr>
							<?php
						}
						?>

						<?php
					}
				}
				?>
			</table>
			<script type="application/javascript">
			jQuery( function($) {
				"use strict";

				$('#refresh_site_info').on('click', function(e) {
					e.preventDefault();
					var data = {
						action: 'nodalio_get_site_info'
					};

					var dataType = "json";
					var ajaxurl = "<?php echo $ajaxurl; ?>";
					var success_message = "<?php echo $success_message; ?>"

					$.post(ajaxurl, data, function(response) {
						$('<div class="notice notice-success"><p>' + success_message + '</p></div>').insertAfter( $( "#nodalio_cache_description" ) );
						setTimeout( function () {
							$("div.notice").fadeOut(300, function() { $(this).remove(); });
							$('#refresh_site_info').prop('disabled', false);
						}, 10000 );
						$('#refresh_site_info').prop('disabled', true);
						try {
							var siteinfo = JSON.parse(response);
							$('#nodalio_info_cache_tool').text(siteinfo.cache_tool);
							$('#nodalio_info_owner_email').text(siteinfo.owner_email);
							$('#nodalio_info_creation_date').text(siteinfo.creation_date);
							if (siteinfo.ssl_certificate != "none") {
								$('#nodalio_info_ssl_certificatel').text(siteinfo.ssl_certificate + <?php _e( ' certificate', $this->textdomain ) ?>);
							} else {
								$('#nodalio_info_ssl_certificatel').text("<?php _e( 'Not Installed', $this->textdomain ) ?>");
							}
							$('#nodalio_info_domain_wildcard').text(siteinfo.domain_wildcard);
							if (siteinfo.domain_wildcard_ssl != "none") {
								$('#nodalio_info_domain_wildcard_ssl_certificate').text(siteinfo.domain_wildcard_ssl + <?php _e( ' certificate', $this->textdomain ) ?>);
							} else {
								$('#nodalio_info_domain_wildcard_ssl_certificate').text("<?php _e( 'Not Installed', $this->textdomain ) ?>");
							}
							if (siteinfo.traffic_control == "true") {
								$('#nodalio_info_traffic_control').text("<?php _e( 'Enabled', $this->textdomain ) ?>");
							} else {
								$('#nodalio_info_traffic_control').text("<?php _e( 'Disabled', $this->textdomain ) ?>");
							}
							if (siteinfo.keepalive == "true") {
								$('#nodalio_info_keepalive').text("<?php _e( 'Enabled', $this->textdomain ) ?>");
							} else {
								$('#nodalio_info_keepalive').text("<?php _e( 'Disabled', $this->textdomain ) ?>");
							}
							if (siteinfo.appcl == "true") {
								$('#nodalio_info_appcl').text("<?php _e( 'Enabled', $this->textdomain ) ?>");
							} else {
								$('#nodalio_info_appcl').text("<?php _e( 'Disabled', $this->textdomain ) ?>");
							}

						} catch (e) {
							console.log(e);
						}
					});
				});
			});
		</script>
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
						<button name="clear_cache" id="clear_cache" type="clear_cache" class="button button-primary button-large menu-save"><?php _e('Clear Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></button>
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

function nodalio_refresh_site_info($hard) {
	if ( empty($hard) ) {
		$siteinfo = Nodalio_Main_Class()->nodalio_get_site_info();
	} else if ( $hard ){
		update_option( 'nodalio_main_site_info_date_populated', '' );
		$siteinfo = Nodalio_Main_Class()->nodalio_get_site_info();
	}
}