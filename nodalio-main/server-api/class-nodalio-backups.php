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
 * Returns the main instance of Nodalio_Site_Backups_Class to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Nodalio_Site_Backups_Class
 */
function Nodalio_Site_Backups_Class() {
	return Nodalio_Site_Backups_Class::instance();
} // End Nodalio_Site_Backups_Class()

Nodalio_Site_Backups_Class();

/**
 * Main Nodalio_Site_Backups_Class Class
 *
 * @class Nodalio_Site_Backups_Class
 * @version	1.0.0
 * @since 1.0.0
 * @package	Nodalio_Site_Backups_Class
 */
final class Nodalio_Site_Backups_Class {
	/**
	 * Nodalio_Site_Backups_Class The single instance of Nodalio_Site_Backups_Class.
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
		add_action( 'init', array( $this, 'nodalio_setup_site_backups' ), 501 );
	}

	/**
	 * Main Nodalio_Site_Backups_Class Instance
	 *
	 * Ensures only one instance of Nodalio_Site_Backups_Class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Nodalio_Site_Backups_Class()
	 * @return Main Nodalio_Site_Backups_Class instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	public function nodalio_setup_site_backups() {
		//add_action( 'admin_menu', array( $this, 'nodalio_main_add_site_backups_pages' ) );
		//require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-commands.php' );
	}
	
	public function nodalio_main_add_site_backups_pages() {
		add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( 'Backups', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$menu_title		= __( 'Backups', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-site-backups',
			$function		= array( $this, 'nodalio_main_add_site_backups_actions' )
        );
        add_submenu_page(
			$parent_slug	= 'nodalio-main-info',
			$page_title		= __( 'OnClick Backups', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$menu_title		= __( 'OnClick Backups', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ),
			$capability		= 'update_core',
			$menu_slug		= 'nodalio-main-site-onclick-backups',
			$function		= array( $this, 'nodalio_main_add_site_onclick_backups_actions' )
		);
    }
    
    private function nodalio_get_backup_list() {
        $currect_day = date("mdy");
        if ( get_option( 'nodalio_main_backup_list_date_populated', "" ) !== $currect_day ) {
            $backups = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'backuplist', '', 'GET' );
            $backups = $backups->runCommand();
            //var_dump($backups);
            if ( ! is_wp_error( $backups ) ) {
                $backups = json_decode(wp_remote_retrieve_body( $backups ));
                $backups = json_decode($backups->data);
                update_option( 'nodalio_main_backup_list_date_populated', $currect_day );
                update_option( 'nodalio_main_backup_list', $backups );
            }
        } else {
            $backups = get_option( 'nodalio_main_backup_list' );
        }
        return $backups;
    }

	public function nodalio_main_add_site_backups_actions() {
        global $wp;
        $current_page = esc_url( home_url( $wp->request ) );
        $backup_counter = 0;
        $onclick_counter = 0;
        $messages = array();
        $backups = $this->nodalio_get_backup_list();
        if ( is_wp_error( $backups ) ) {
            foreach ( $backups->get_error_messages() as $message ) {
                $message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
                array_push( $messages, $message );
            }
        }
        
		if ( isset( $_GET['action'] ) && isset($_GET['backup-name']) ) {
            if ( ($_GET['action'] === "restore") || ($_GET['action'] === "restore-full") && ( ! empty($_GET['backup-name']) ) ){
                require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-command.php' );
                $restore_backup = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, $_GET['action'], $_GET['backup-name'] );
                $restore_backup = $restore_backup->runCommand();
                if ( is_wp_error( $restore_backup ) ) {
                    foreach ( $restore_backup->get_error_messages() as $message ) {
                        $message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
                        array_push( $messages, $message );
                    }
                } else {
                    $restore_backup = json_decode(wp_remote_retrieve_body( $restore_backup ));
                    //$restore_backup = json_decode($restore_backup->data);
                    if ( $restore_backup->result == "success" ) {
                        $message = '<div class="notice notice-success"><p>' . __( 'Backup has been restored.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
                        array_push( $messages, $message );
                        update_option( 'nodalio_main_backup_list_date_populated', '' );
                    } else {
                        $message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to restore the backup: ' . $restore_backup->data .'.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
                        array_push( $messages, $message );
                    }
                }
            }
        }
		?>
		<div class="wrap">
			<h1><?php _e( 'Daily Backups', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></h1>
			<p><?php _e( "Welcome to the Nodalio backups page, here you can restore your site from daily backups.", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></p>
			<?php 
				if ( ! empty( $messages ) ) {
					foreach ( $messages as $message ) {
						echo $message;
					}
                }
			if ( ! empty( $backups ) && ! is_wp_error( $backups ) && $backups->result != "failure" ) {
                ?>
                <form method="post" class="nodalio-main-cache-settings">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <th>
                                <?php _e('#', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Name', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Date Created', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Size', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Actions', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                        </thead><?php
                        foreach ( $backups as $backup ) {
                            if ( ! is_numeric( $backup->filename ) ) {
                                ?>
                                <tr>
                                    <td><?php
                                        $backup_counter++;
                                        echo $backup_counter;
                                    ?></td>
                                    <td><?php
                                        echo $backup->filename;
                                    ?></td>
                                    <td><?php
                                        //var_dump($backup->crTime);
                                        $date = new DateTime($backup->crTime);
                                        echo $date->format('Y-m-d H:i');
                                        //var_dump( date('Y-m-d h:M', strtotime($backup->crTime)) );
                                        //var_dump(date(DATE_ISO8601, $backup->crTime));
                                        //echo str_replace('T', ' ', $backup->crTime);;
                                    ?></td>
                                    <td><?php
                                        echo $backup->size;
                                    ?></td>
                                    <td class="row-actions visible">
                                        <span>
                                            <a href="<?php echo esc_url(add_query_arg(array(
                                                'action' => 'restore',
                                                'backup-name' => $backup->filename
                                            ),$current_page)); ?>" target="_blank"><?php _e('Restore', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></a> |
                                            <a href="<?php echo esc_url(add_query_arg(array(
                                                'action' => 'restore-full',
                                                'backup-name' => $backup->filename
                                            ),$current_page)); ?>" target="_blank"><?php _e('Restore with files', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></a>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </form>
                <?php
            } else {
                ?>
                <p class="no-backups-found"><?php _e('No backups found.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></p>
                <?php
            }
            ?>

        </div>
		<?php
    }
    
    public function nodalio_main_add_site_onclick_backups_actions() {
        global $wp;
        $current_page = esc_url( home_url( $wp->request ) );
        $onclick_counter = 0;
        $messages = array();
        $backups = $this->nodalio_get_backup_list();
        if ( is_wp_error( $backups ) ) {
            foreach ( $backups->get_error_messages() as $message ) {
                $message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
                array_push( $messages, $message );
            }
        }
        
		if ( isset( $_GET['action'] ) && isset($_GET['backup-name']) ) {
            if ( ($_GET['action'] === "restoreoc") || ($_GET['action'] === "restoreoc-full") || ($_GET['action'] === "oc") && ( ! empty($_GET['backup-name']) ) ){
                require( NODALIO_MAIN_PLUGIN_DIR . 'server-api/class-nodalio-command.php' );
                $restore_backup = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, $_GET['action'], $_GET['backup-name'] );
                $restore_backup = $restore_backup->runCommand();
                if ( is_wp_error( $restore_backup ) ) {
                    foreach ( $restore_backup->get_error_messages() as $message ) {
                        $message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
                        array_push( $messages, $message );
                    }
                } else {
                    $restore_backup = json_decode(wp_remote_retrieve_body( $restore_backup ));
                    //$restore_backup = json_decode($restore_backup->data);
                    if ( $restore_backup->result == "success" ) {
                        $message = '<div class="notice notice-success"><p>' . __( 'Backup has been restored.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
                        array_push( $messages, $message );
                        update_option( 'nodalio_main_backup_list_date_populated', '' );
                    } else {
                        $message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to restore the backup: ' . $restore_backup->data .'.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
                        array_push( $messages, $message );
                    }
                }
            }
        }

        if ( isset( $_POST['create-new-onclick'] ) ) {
            if ( defined('NODALIO_PLAN') ) {
                $server_plan = NODALIO_PLAN;
            } else {
                $server_plan = "enterprise";
            }
            $backup_completed = false;
            if ( $server_plan == "enterprise" ) {
                $backup_num = 10;
            } else if ( $server_plan == "business" ) {
                $backup_num = 5;
            } else if ( $server_plan == "professional" ) {
                $backup_num = 3;
            }
            if ( ! $backup_completed ) {
                for( $i= 0 ; $i <= 10 ; $i++ ) {
                    $backup_here = true;
                    foreach ( $$backups as $backup ) {
                        if ($backup->filename == $i) {
                            $backup_here = false;
                        }
                    }
                    if ($backup_here) {
                        $oc_backup = new Nodalio_API_Command( NODALIO_PRIVATE_KEY, 'oc', $i );
                        $oc_backup = $oc_backup->runCommand();
                        if ( is_wp_error( $oc_backup ) ) {
                            foreach ( $oc_backup->get_error_messages() as $message ) {
                                $message = '<div class="notice notice-error"><p>' . $message . '</p></div>';
                                array_push( $messages, $message );
                            }
                        } else {
                            $oc_backup = json_decode(wp_remote_retrieve_body( $oc_backup ));
                            //$restore_backup = json_decode($restore_backup->data);
                            if ( $oc_backup->result == "success" ) {
                                $message = '<div class="notice notice-success"><p>' . __( 'Backup has been created.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
                                array_push( $messages, $message );
                                update_option( 'nodalio_main_backup_list_date_populated', '' );
                            } else {
                                $message = '<div class="notice notice-error"><p>' . __( 'An error has occured when attempting to create an OnClick backup: ' . $oc_backup->data .'.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) . '</p></div>';
                                array_push( $messages, $message );
                            }
                            $backup_completed = true;
                        }
                    }
                }
            }
        }
		?>
        <div class="wrap">
        <h1><?php _e( 'OnClick Backups', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?><button name="create-new-onclick" id="create-new-onclick" type="create-new-onclick" class="page-title-action"><?php _e('Create New Onclick', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></button></h1>
			<p><?php _e( "Welcome to the Nodalio backups page, here you can restore your site from OnClick Backups. You can also create a new backup on an already exisitng OnClick backup.", NODALIO_MAIN_PLUGIN_TEXTDOMAIN ) ?></p>
			<?php 
				if ( ! empty( $messages ) ) {
					foreach ( $messages as $message ) {
						echo $message;
					}
                }
            //var_dump("Type: " . gettype($backups));
			if ( ! empty( $backups ) && ! is_wp_error( $backups ) ) {
                ?>
                <form method="post" class="nodalio-main-cache-settings">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <th>
                                <?php _e('#', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Name', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Date Created', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Size', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                            <th>
                                <?php _e('Actions', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?>
                            </th>
                        </thead><?php
                        foreach ( $backups as $backup ) {
                            if ( is_numeric( $backup->filename ) ) {
                                ?>
                                <tr>
                                    <td><?php
                                        $onclick_counter++;
                                        echo $onclick_counter;
                                    ?></td>
                                    <td><?php
                                        echo $backup->filename;
                                    ?></td>
                                    <td><?php
                                        $date = new DateTime($backup->crTime);
                                        echo $date->format('Y-m-d H:i');
                                    ?></td>
                                    <td><?php
                                        echo $backup->size;
                                    ?></td>
                                    <td class="row-actions visible">
                                        <span>
                                            <a href="<?php echo esc_url(add_query_arg(array(
                                                'page'  => 'nodalio-main-site-onclick-backups',
                                                'action' => 'restoreoc',
                                                'backup-name' => $backup->filename
                                            ),admin_url())); ?>" target="_blank"><?php _e('Restore', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></a> |
                                            <a href="<?php echo esc_url(add_query_arg(array(
                                                'page'  => 'nodalio-main-site-onclick-backups',
                                                'action' => 'restoreoc-full',
                                                'backup-name' => $backup->filename
                                            ),admin_url())); ?>" target="_blank"><?php _e('Restore with files', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></a> |
                                            <a href="<?php echo esc_url(add_query_arg(array(
                                                'page'  => 'nodalio-main-site-onclick-backups',
                                                'action' => 'oc',
                                                'backup-name' => $backup->filename
                                            ),admin_url())); ?>" target="_blank"><?php _e('Backup', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></a>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </form>
                <?php
            } else {
                ?>
                <p class="no-backups-found"><?php _e('No OnClick backups found.', NODALIO_MAIN_PLUGIN_TEXTDOMAIN ); ?></p>
                <?php
            }
            ?>

		</div>
        <?php
    }
    

}
