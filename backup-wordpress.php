<?php
/**
 * Plugin Name: Backup
 * Plugin URI: http://sygnoos.com/wpbackup/
 * Description: Fully functional FREE Wordpress backup plugin which helps you to create manually/automatically custo​m​ized backups of your Wordpress based web site.
 * Version: 2.7
 * Author: Sygnoos
 * Author URI: http://www.sygnoos.com
 * License: GPLv2
 */

    set_time_limit(0);

    require_once( dirname(__FILE__).'/sns-config.php');
    function sns_autoloader( $class ){
        if( strpos( $class , 'Sns_' ) !== false ){
            if( strpos( $class , 'Sns_Exception' ) !== false && $class != 'Sns_Exception_Handler' ){
                require_once( SNS_CLASSES_PATH.'Sns_Exception.php');
            }else{
                require_once( SNS_CLASSES_PATH.$class.'.php');
            }
        }
    }

    spl_autoload_register('sns_autoloader');

    Sns_Error_Handler::init();
    Sns_Exception_Handler::init();

    register_activation_hook( __FILE__, 'sns_configure_requirements');

    require_once(SNS_BACKUP_ROOT.'request-handler.php');
    require_once(SNS_BACKUP_ROOT.'db-configuration.php');

    register_activation_hook( __FILE__, 'sns_configure_backup_db' );
    register_activation_hook( __FILE__, 'sns_configure_backup_db_data' );
    register_activation_hook( __FILE__, 'sns_configure_backups_store' );

    register_deactivation_hook( __FILE__, 'sns_backup_deactivate' );

    register_uninstall_hook( __FILE__, 'sns_backup_uninstall' );

    add_action( 'admin_menu', 'register_sns_backup_menu_page' );
    add_action( 'admin_enqueue_scripts', 'sns_backup_adding_scripts' );

    // adding actions to handle ajax requests
    add_action( 'wp_ajax_sns_history_update', 'sns_backup_update_history' );
    add_action( 'wp_ajax_sns_manual_backup', 'sns_backup_manual_backup' );
    add_action( 'wp_ajax_sns_backup_delete', 'sns_backup_backup_delete' );
    add_action( 'wp_ajax_sns_backup_restore', 'sns_backup_backup_restore' );
    add_action( 'wp_ajax_sns_external_restore', 'sns_backup_external_restore' );
    add_action( 'wp_ajax_sns_save_ftp', 'sns_backup_save_ftp' );
    add_action( 'wp_ajax_sns_check_ftp', 'sns_backup_check_ftp' );
    add_action( 'wp_ajax_sns_unlink_ftp', 'sns_backup_unlink_ftp' );
    add_action( 'wp_ajax_sns_log_refresh', 'sns_backup_log_refresh' );
    add_action( 'wp_ajax_sns_log_empty', 'sns_backup_log_empty' );
    add_action( 'wp_ajax_sns_state_get_status', 'sns_backup_state_get_status' );
    add_action( 'wp_ajax_sns_state_reset_status', 'sns_backup_state_reset_status' );

    function sns_configure_requirements() {
        if ( version_compare(PHP_VERSION, '5.4.0', '<') ) {
            wp_die('PHP >=5.4.0 version required.');
        }
    }

    function sns_configure_backups_store(){
        if( !is_dir( SNS_BACKUPS_PATH ) ){
            if( !mkdir( SNS_BACKUPS_PATH ) ){
                trigger_error('Cannot create folder '.SNS_BACKUPS_PATH, E_USER_ERROR);
            }
        }
    }

    /*
     * Things to do on plugin deactivate
     */
    function sns_backup_deactivate(){
        $data = array( 'status' => Sns_State::STATUS_NONE );
        Sns_State::update( $data );
    }

    function sns_backup_uninstall(){
        //drop sns backup plugins tables
        global $wpdb;
        $table = SNS_DB_PREFIX.'settings_destinations';
        $wpdb->query( "DROP TABLE `{$table}`" );
        $table = SNS_DB_PREFIX.'backups';
        $wpdb->query( "DROP TABLE `{$table}`" );
        $table = SNS_DB_PREFIX.'options';
        $wpdb->query( "DROP TABLE `{$table}`" );
        $table = SNS_DB_PREFIX.'settings_ftp';
        $wpdb->query( "DROP TABLE `{$table}`" );
        $table = SNS_DB_PREFIX.'state';
        $wpdb->query( "DROP TABLE `{$table}`" );

        //delete backup files
        if( is_dir( SNS_BACKUPS_PATH ) ){
            Sns_History::delete_dir( SNS_BACKUPS_PATH );
        }

    }

    function register_sns_backup_menu_page(){
        add_menu_page( 'Backup', 'Backup', 'manage_options', SNS_BACKUP_ROOT_FOLDER_NAME.'/sns-backup-admin.php', '', 'dashicons-backup', 76 );
    }

    function my_style_loader_tag_function($tag){
        return preg_replace("/='stylesheet' id='less-css'/", "='stylesheet/less' id='less-css'", $tag);
    }

    function sns_backup_adding_scripts( $hook ) {

        if( $hook != SNS_BACKUP_ROOT_FOLDER_NAME.'/sns-backup-admin.php'){
            return;
        }
        wp_enqueue_style('less', SNS_CSS_URL.'/wordpress-bootstrap.less');
        add_filter('style_loader_tag', 'my_style_loader_tag_function');

        wp_register_style('sns-backup-style', SNS_CSS_URL.'/sns-backup-general.css');
        wp_enqueue_style('sns-backup-style');

        wp_register_script('sns-backup-less-js-script', SNS_JS_URL.'/less-1.7.5.min.js');
        wp_enqueue_script('sns-backup-less-js-script');

        wp_register_style('sns-backup-jquery-ui-style', SNS_CSS_URL.'/jquery-ui.min.css');
        wp_enqueue_style('sns-backup-jquery-ui-style');

        wp_enqueue_script( 'sns-general-script', SNS_JS_URL.'/sns-backup-general.js', array('jquery') );

        wp_register_script('jquery-ui-tabs', includes_url('js/jquery/ui/jquery.ui.tabs.min.js'));
        wp_enqueue_script('jquery-ui-tabs');

        wp_register_script('plupload', includes_url('js/plupload/plupload.js'));
        wp_enqueue_script('plupload');

        wp_register_script('jquery-ui-progressbar', includes_url('js/jquery/ui/jquery.ui.progressbar.min.js'));
        wp_enqueue_script('jquery-ui-progressbar');

        wp_register_script('sns-backup-bootstrap-script', SNS_CSS_URL.'/bootstrap/js/bootstrap.min.js');
        wp_enqueue_script('sns-backup-bootstrap-script');

    }

?>