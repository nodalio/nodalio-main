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
 * Returns the main instance of Nodalio_Site_Cache_Class to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Nodalio_Site_Cache_Class
 */
function Nodalio_Site_Cache_Class() {
	return Nodalio_Site_Cache_Class::instance();
} // End Nodalio_Site_Cache_Class()

Nodalio_Site_Cache_Class();

/**
 * Main Nodalio_Site_Cache_Class Class
 *
 * @class Nodalio_Site_Cache_Class
 * @version	1.0.0
 * @since 1.0.0
 * @package	Nodalio_Site_Cache_Class
 */
final class Nodalio_Site_Cache_Class {
	/**
	 * Nodalio_Site_Cache_Class The single instance of Nodalio_Site_Cache_Class.
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
		add_action( 'init', array( $this, 'nodalio_setup_site_cache' ), 500 );
	}

	/**
	 * Main Nodalio_Site_Cache_Class Instance
	 *
	 * Ensures only one instance of Nodalio_Site_Cache_Class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Nodalio_Site_Cache_Class()
	 * @return Main Nodalio_Site_Cache_Class instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	public function nodalio_setup_site_cache() {
		add_action( 'admin_menu', array( $this, 'nodalio_main_add_site_cache_pages' ) );
		add_action( 'wp_ajax_nodalio_change_caching_tool', array( $this, 'nodalio_ajax_set_cache' ) );
		add_action( 'wp_ajax_nodalio_clear_site_cache', array( $this, 'nodalio_clear_site_cache_ajax' ) );
		//require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-commands.php' );
	}
	
	public function nodalio_main_add_site_cache_pages() {
		add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( 'Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$menu_title		= __( 'Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-site-caching',
			$function		= array( $this, 'nodalio_main_add_site_cache_actions' )
		);
	}

	private function filter_caching_tools($tool, $serverplan) {
		$featues = array(
			'microcache' => array( 'business', 'enterprise' ),
			'opticache' => array( 'enterprise' ),
			'onecache' => array( 'professional', 'business', 'enterprise' ),
			'servercache' => array( 'personal', 'professional', 'business', 'enterprise' )
		);
		if ( (empty($server_plan)) && defined('NODALIO_PLAN') ) {
			$server_plan = NODALIO_PLAN;
		} else if ( (empty($server_plan)) && !defined('NODALIO_PLAN') ) {
			$server_plan = "enterprise";
		}

		if ( array_key_exists( $tool, $featues ) ) {
			if ( in_array( $server_plan, $featues[$tool] ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function disable_not_available_cache_tools($tool, $serverplan) {
		if ( ! $this->filter_caching_tools($tool, $serverplan) ) {
			echo "disabled";
		}
	}

	public function nodalio_ajax_set_cache() {
		if ( isset( $_POST['cache_tool'] ) ) {
			if ( $_POST['cache_tool'] === "disabled" ) {
				$cache = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'cacheoff-noreload' );
				$cache = $cache->runCommand();
			} else if ( $_POST['cache_tool'] === "microcache" ) {
				if ( isset( $_POST['microcache_minutes'] ) && is_numeric( intval($_POST['microcache_minutes']) ) ) {
					$cache = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'microcache', $_POST['microcache_minutes'] );
					$cache = $cache->runCommand();
					//var_dump($cache);
				} else {
					$cache = new WP_Error( 'site_api_request_microcache_minutes_missing', __( "Please enter the number of minutes for MicroCache" ) );
				}
			} else if ( ( $_POST['cache_tool'] === "onecache" ) || ( $_POST['cache_tool'] === "opticache" ) || ( $_POST['cache_tool'] === "servercache" ) ) {
				$cache = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'cacheon-noreload', $_POST['cache_tool'] );
				$cache = $cache->runCommand();
			}
		} else {
			$cache = new WP_Error('no_cache_tool_selected', 'Error! No cache tool has been selected');
		}
		
		if ( is_wp_error( $cache ) ) {
			foreach ( $cache->get_error_messages() as $message ) {
				$message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
				//array_push( $messages, $message );
				echo $message;
			}
		} else {
			$cache = json_decode( wp_remote_retrieve_body( $cache ) );
			if ( $cache->result == "success" ) {
				$message = '<div class="notice notice-success"><p>' . __( 'Caching settings changed. The changes will be active in a couple of seconds.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
				//array_push( $messages, $message );
				echo $message;
				update_option( 'nodalio_main_active_cache', $_POST['cache_tool'] );
				if ( $_POST['cache_tool'] ) {
					update_option( 'nodalio_main_microcache_minutes', $_POST['microcache_minutes'] );
				}
			} else {
				if ( $cache->data == "exitcode: 5" ) {
					$message = '<div class="notice notice-error"><p>' . __( 'The chosen caching tool is not available for your plan, please consider upgrading your plan.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					//array_push( $messages, $message );
					echo $message;
				} else {
					$message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to change the caching tool: ' . $cache->data , NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					//array_push( $messages, $message );
					echo $message;
				}
			}
		}
		nodalio_refresh_site_info(true);
		wp_die();
	}

	public function nodalio_clear_site_cache_ajax() {
		if ( isset( $_POST['action'] ) ) {
			$clearcache = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'clearsitecache' );
			$clearcache = $cache->runCommand();
			if ( is_wp_error( $clearcache ) ) {
				foreach ( $clearcache->get_error_messages() as $message ) {
					$message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
					//array_push( $messages, $message );
					echo $message;
				}
			} else {
				$clearcache = json_decode( wp_remote_retrieve_body( $clearcache ) );
				if ( $clearcache->result == "success" ) {
					$message = '<div class="notice notice-success"><p>' . __( 'Cleared Site Cache.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					echo $message;
				} else {
					$message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to clear the site cache: ' . $clearcache->data , NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
					echo $message;
				}
			}
			wp_die();
		}
	}

	public function nodalio_main_add_site_cache_actions() {
		$cache_selected = get_option( 'nodalio_main_active_cache', 'servercache' );
		$siteinfo = get_option( 'nodalio_main_site_info', '' );
		if ( ! empty($siteinfo) ) {
			if ( !empty($siteinfo->cache_tool) ) {
				$cache_selected = $siteinfo->cache_tool;
			}
		}
		$messages = array();
		//var_dump($_POST);
		if ( isset( $_POST['save_cache_settings'] ) ) {
			// Do Nothing
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></h1>
			<p id="nodalio_cache_description"><?php _e( "Welcome to the Nodalio clear cache page, here you can clear your site's cache. Our plugin clears cache on most occasions, if for some reason cache has not been cleared, feel free to use the Clear Cache button. ", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></p>
			<?php 
				if ( ! empty( $messages ) ) {
					foreach ( $messages as $message ) {
						echo $message;
					}
				}
			?>
			<form method="post" class="nodalio-main-cache-settings">
			<table class="form-table widefat striped">
				<thead>
					<tr>
						<td colspan="2" data-export-label="<?php _e('Site Caching', $textdomain ); ?>">
							<h2><?php _e('Site Caching', $textdomain ); ?></h2>
						</td>
					</tr>
				</thead>
				<tr class="site-primary-caching">
					<td valign="top" style="padding-top: 15px"><label for="nodalio_cache_selection" ><?php _e('Set Site Caching Tool', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></label></td>
					<td>
						<select name="nodalio_cache_selection" id="nodalio_cache_selection">
							<option value="disabled" <?php selected( $cache_selected, 'disabled' ); ?>><?php _e('Disabled', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></option>
							<option value="servercache" <?php selected( $cache_selected, 'servercache' ); ?>><?php _e('Server Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></option>
							<option value="onecache" <?php selected( $cache_selected, 'onecache' ); ?>><?php _e('OneCache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></option>
							<option value="microcache" <?php selected( $cache_selected, 'microcache' ); ?>><?php _e('MicroCache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></option>
							<option value="opticache" <?php selected( $cache_selected, 'opticache' ); ?>><?php _e('OptiCache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></option>
						</select>
					</td>
				</tr>
				<tr name="nodalio_microcache_minutes_section" <?php if ( $cache_selected != "microcache" ) echo 'style="display: none"' ?>>
					<td valign="top" style="padding-top: 15px"><label for="nodalio_cache_selection_microcache_minutes" ><?php _e('Set MicroCache Duration', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></label></td>
					<td>
						<input type="text" value="<?php echo get_option( 'nodalio_main_microcache_minutes', '10' ) ?>" id="nodalio_cache_selection_microcache_minutes" name="nodalio_cache_selection_microcache_minutes">
					</td>
				</tr>
			</table>
			<!-- <button name="save_cache_settings" id="save_cache_settings" type="save_cache_settings" class="button button-primary button-large menu-save"><?php //_e('Save Site Caching', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></button> -->
			<p class="submit">
				<a href="#" id="save_cache_settings" class="save_cache_settings button button-primary button-large menu-save"><?php _e( 'Save Site Caching', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></a>
				<a href="#" id="nodalio_clear_cache" class="nodalio_clear_cache button button-secondary button-large menu-save"><?php _e( 'Clear Site Cache', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></a>
			</p>
			</form>
			<script type="application/javascript">
			jQuery( function($) {
				"use strict";
				$('[name="nodalio_cache_selection"]').change( function (e) {
					if ($('#nodalio_cache_selection').find(":selected").val() == "microcache") {
						$('[name="nodalio_microcache_minutes_section"]').css("display","block");
					} else {
						$('[name="nodalio_microcache_minutes_section"]').css("display","none");
					}
				});

				$('#save_cache_settings').on('click', function(e) {
					e.preventDefault();
					var data = {
						action: 'nodalio_change_caching_tool',
						cache_tool: $('#nodalio_cache_selection').find(":selected").val(),
						microcache_minutes: $('#nodalio_cache_selection_microcache_minutes').val()
					};
					$.post(ajaxurl, data, function(response) {
						$(response).insertAfter( $( "#nodalio_cache_description" ) );
						setTimeout( function () {
							$("div.notice").fadeOut(300, function() { $(this).remove(); });
							$('#save_cache_settings').prop('disabled', false);
						}, 10000 );
						$('#save_cache_settings').prop('disabled', true);
					});
				});
				$('#nodalio_clear_cache').on('click', function(e) {
					e.preventDefault();
					var data = {
						action: 'nodalio_clear_site_cache',
					};
					$.post(ajaxurl, data, function(response) {
						$(response).insertAfter( $( "#nodalio_cache_description" ) );
						setTimeout( function () {
							$("div.notice").fadeOut(300, function() { $(this).remove(); });
							$('#save_cache_settings').prop('disabled', false);
						}, 10000 );
						$('#save_cache_settings').prop('disabled', true);
					});
				});
			});
		</script>
		</div>
		<?php
	}
    

}