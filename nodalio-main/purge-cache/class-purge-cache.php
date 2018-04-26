<?php
/**
 * Nodalio Cache Purge
 * Uses WP FileSystem API
 *
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Returns the main instance of Nodalio_Purge_Cache_Class to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Nodalio_Purge_Cache_Class
 */
function Nodalio_Purge_Cache_Class() {
	return Nodalio_Purge_Cache_Class::instance();
} // End Nodalio_Purge_Cache_Class()

Nodalio_Purge_Cache_Class();

/**
 * Main Nodalio_Purge_Cache_Class Class
 *
 * @class Nodalio_Purge_Cache_Class
 * @version	1.0.0
 * @since 1.0.0
 * @package	Nodalio_Purge_Cache_Class
 */
final class Nodalio_Purge_Cache_Class {
	/**
	 * Nodalio_Purge_Cache_Class The single instance of Nodalio_Purge_Cache_Class.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
    private static $_instance = null;
    
	private $path = '/etc/nginx/cache/fastcgi/';
	
    public $textdomain;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->textdomain = Nodalio_Main_Class()->textdomain();
		$this->path .= trailingslashit( NODALIO_PRIMARY_DOMAIN );
		add_action( 'plugins_loaded', array( $this, 'nodalio_setup_cache_purge' ) );
	}

	/**
	 * Main Nodalio_Purge_Cache_Class Instance
	 *
	 * Ensures only one instance of Nodalio_Purge_Cache_Class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Nodalio_Purge_Cache_Class()
	 * @return Main Nodalio_Purge_Cache_Class instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	public function nodalio_setup_cache_purge() {
        // use `nginx_cache_purge_actions` filter to alter default purge actions
		$purge_actions = (array) apply_filters(
			'nodalio_cache_purge_actions',
			array(
				'publish_phone', 'save_post', 'edit_post', 'delete_post', 'wp_trash_post', 'clean_post_cache',
				'trackback_post', 'pingback_post', 'comment_post', 'edit_comment', 'delete_comment', 'wp_set_comment_status',
				'switch_theme', 'wp_update_nav_menu', 'edit_user_profile_update'
			)
		);

		foreach ( $purge_actions as $action ) {
			add_action( $action, array( $this, 'purge_zone_once' ) );
		}
    }

    public function is_valid_path() {

        global $wp_filesystem;

		if ( $this->initialize_filesystem() ) {

			if ( ! $wp_filesystem->exists( $this->path ) ) {
				return new WP_Error( 'fs', __( 'Cache Path does not exist.', $this->textdomain ) );
			}

			if ( ! $wp_filesystem->is_dir( $this->path ) ) {
				return new WP_Error( 'fs', __( 'Cache Path is not a directory.', $this->textdomain ) );
			}

			$list = $wp_filesystem->dirlist( $this->path, true, true );
			if ( ! $this->validate_dirlist( $list ) ) {
				return new WP_Error( 'fs', __( 'Cache Path does not appear to be a Nginx cache zone directory.', $this->textdomain ) );
			}

			if ( ! $wp_filesystem->is_writable( $this->path ) ) {
				return new WP_Error( 'fs', __( 'Cache Path is not writable.', $this->textdomain ) );
			}

			return true;

		}

		return new WP_Error( 'fs', __( 'Filesystem API could not be initialized.', $this->textdomain ) );

    }
    
    private function validate_dirlist( $list ) {

		foreach ( $list as $item ) {

			// abort if file is not a MD5 hash
			if ( $item[ 'type' ] === 'f' && ( strlen( $item[ 'name' ] ) !== 32 || ! ctype_xdigit( $item[ 'name' ] ) ) ) {
				return false;
			}

			// validate subdirectories recursively
			if ( $item[ 'type' ] === 'd' && ! $this->validate_dirlist( $item[ 'files' ] ) ) {
				return false;
			}

		}

		return true;

	}
    
    private function initialize_filesystem() {

		// if the cache directory doesn't exist, try to create it
		if ( ! file_exists( $this->path ) ) {
			mkdir( $this->path );
		}

		// load WordPress file API?
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		ob_start();
		$credentials = request_filesystem_credentials( '', '', false, $this->path, null, true );
		ob_end_clean();

		if ( $credentials === false ) {
			return false;
		}

		if ( ! WP_Filesystem( $credentials, $this->path, true ) ) {
			return false;
		}

		return true;

    }
    
    public function purge_zone_once() {

		static $completed = false;

		if ( ! $completed ) {
			$this->purge_zone();
			$completed = true;
		}

    }


	public function purge() {
		return $this->purge_zone();
	}
    
    private function purge_zone() {

		global $wp_filesystem;

		if ( ! $this->should_purge() ) {
			return false;
		}

		$path = $this->path;
		$path_error = $this->is_valid_path();

		// abort if cache zone path is not valid
		if ( is_wp_error( $path_error ) ) {
			return $path_error;
		}

		// delete cache directory (recursively)
		$wp_filesystem->rmdir( $path, true );

		// recreate empty cache directory
		$wp_filesystem->mkdir( $path );
		$wp_filesystem->chgrp( $path, 'root' );

		return true;

    }
    
    private function should_purge() {

		$post_type = get_post_type();

		if ( ! $post_type ) {
			return true;
		}

		if ( ! in_array( $post_type, (array) apply_filters( 'nodalio_cache_excluded_post_types', array() ) ) ) {
			return true;
		}

		return false;
	}

}