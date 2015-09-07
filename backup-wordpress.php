<?php
/**
 * Plugin Name: Backup
 * Plugin URI: http://sygnoos.com/wpbackup/
 * Description: The BEST FREE backup and restoration plugin for WordPress. Create manual or scheduled fully customized backups on FTP, Dropbox ...
 * Version: 2.7.6
 * Author: Sygnoos
 * Author URI: http://www.sygnoos.com
 * License: GPLv2
 */

    @set_time_limit(0);

    require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'sns-config.php');
    function sns_autoloader( $class ){
        if( strpos( $class , 'Sns_' ) !== false ){
            if( strpos( $class , 'Sns_Exception' ) !== false && $class != 'Sns_Exception_Handler' ){
                require_once( SNS_CLASSES_PATH.'Sns_Exception.php');
            }else{
                require_once( SNS_CLASSES_PATH.$class.'.php');
            }
        }elseif( strpos( $class , 'Dropbox' ) !== false ){
            $class = str_replace('\\', SNS_DS, $class);
            require_once(SNS_BACKUP_ROOT . $class . '.php');
        }

    }

    spl_autoload_register('sns_autoloader');

    Sns_Error_Handler::init();
    Sns_Exception_Handler::init();

    require_once(SNS_BACKUP_ROOT.'request-handler.php');
    require_once(SNS_BACKUP_ROOT.'db-configuration.php');

    register_activation_hook( __FILE__, 'sns_backup_initial_check' );
    register_activation_hook( __FILE__, 'sns_configure_backup_db' );
    register_activation_hook( __FILE__, 'sns_configure_backup_db_data' );

    register_deactivation_hook( __FILE__, 'sns_backup_deactivate' );

    register_uninstall_hook( __FILE__, 'sns_backup_uninstall' );

    add_action( 'admin_menu', 'register_sns_backup_menu_page' );
    add_action( 'admin_enqueue_scripts', 'sns_backup_adding_scripts' );

    // adding actions to handle ajax requests
    add_action( 'wp_ajax_sns_history_update', 'sns_backup_update_history' );
    add_action( 'wp_ajax_sns_manual_backup', 'sns_backup_manual_backup' );
    add_action( 'wp_ajax_sns_backup_delete', 'sns_backup_backup_delete' );
    add_action( 'wp_ajax_sns_backup_restore', 'sns_backup_backup_restore' );
    add_action( 'wp_ajax_sns_external_upload', 'sns_backup_external_upload' );
    add_action( 'wp_ajax_sns_external_restore', 'sns_backup_external_restore' );
    add_action( 'wp_ajax_sns_save_options', 'sns_backup_save_options' );
    add_action( 'wp_ajax_sns_save_notifications', 'sns_backup_save_notifications' );
    add_action( 'wp_ajax_sns_save_schedule', 'sns_backup_save_schedule' );
    add_action( 'wp_ajax_sns_save_ftp', 'sns_backup_save_ftp' );
    add_action( 'wp_ajax_sns_unlink_dropbox', 'sns_backup_unlink_dropbox' );
    add_action( 'wp_ajax_sns_check_ftp', 'sns_backup_check_ftp' );
    add_action( 'wp_ajax_sns_unlink_ftp', 'sns_backup_unlink_ftp' );
    add_action( 'wp_ajax_sns_check_dropbox', 'sns_backup_check_dropbox' );
    add_action( 'wp_ajax_sns_log_refresh', 'sns_backup_log_refresh' );
    add_action( 'wp_ajax_sns_log_empty', 'sns_backup_log_empty' );
    add_action( 'wp_ajax_sns_state_get_status', 'sns_backup_state_get_status' );
    add_action( 'wp_ajax_sns_state_reset_status', 'sns_backup_state_reset_status' );
    add_action( 'wp_ajax_sns_prepare_process', 'sns_backup_prepare_process' );
    add_action( 'wp_ajax_sns_review_off', 'sns_backup_sns_review_off' );
    add_action('admin_action_sns_link_dropbox' , 'sns_backup_link_dropbox');

    add_action( 'wp_loaded','sns_check_for_restore');
    function sns_check_for_restore(){
        if( isset($_GET['sns_restore']) && isset($_GET['sns_backup_id']) ){
            sns_backup_backup_restore($_GET['sns_backup_id']);
        } if( isset($_GET['sns_ex_restore']) && isset($_GET['sns_uname']) ) {
            sns_backup_external_restore($_GET['sns_uname']);
        }
    }

    function sns_backup_initial_check(){
        Sns_Checker::initialCheck();
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
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DB_PREFIX.'settings_notifications';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DB_PREFIX.'backups';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DB_PREFIX.'options';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DB_PREFIX.'schedule_config';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DB_PREFIX.'settings_ftp';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DB_PREFIX.'state';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        $table = SNS_DROPBOX_TABLE;
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

        //delete backup files
        if( is_dir( SNS_BACKUPS_PATH ) ){
            Sns_History::delete_dir( SNS_BACKUPS_PATH );
        }

        delete_option('sns_backup_version');
        delete_option('sns_backup_review_off');

    }

    /*
     * Adds once weekly to the existing schedules.
     */
    add_filter( 'cron_schedules', 'cron_add_weekly' );
    function cron_add_weekly( $schedules ) {
        $schedules['weekly'] = array(
            'interval' => 60*60*24*7,
            'display' => 'Once weekly'
        );
        return $schedules;
    }

    /*
     * Adds once monthly to the existing schedules.
     */
    add_filter( 'cron_schedules', 'cron_add_monthly' );
    function cron_add_monthly( $schedules ) {
        $schedules['monthly'] = array(
            'interval' => 60*60*24*30,
            'display' => 'Once monthly'
        );
        return $schedules;
    }

    add_action( 'sns_schedule_run_backup_hourly', 'sns_backup_action' );
    add_action( 'sns_schedule_run_backup_daily', 'sns_backup_action' );
    add_action( 'sns_schedule_run_backup_weekly', 'sns_backup_action' );
    add_action( 'sns_schedule_run_backup_monthly', 'sns_backup_action' );

    function sns_backup_action() {

        $proc = Sns_State::get_status();
        $scheduleState = Sns_State::get_status( Sns_Backup::BACKUP_MODE_SCHEDULE );
        if( $proc['status'] != Sns_State::STATUS_ACTIVE && empty($scheduleState) ){
            $backup = new Sns_Backup('schedule');
            $data = array( 'status'    =>  Sns_State::STATUS_ACTIVE );
            Sns_State::update( $data );
            try{
                $backup->backup();
            }catch ( Exception $e ){
                Sns_Log::log_exception_obj( $e );
            }
            $data = array( 'status'    =>  Sns_State::STATUS_FINISHED );
            Sns_State::update( $data );
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

        wp_enqueue_script( 'sns-raty-script', SNS_JS_URL.'/jquery.raty.min.js', array('jquery') );
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

    function sns_return_bytes ($size_str)
    {
        $size_str = strtolower($size_str);
        switch (substr ($size_str, -1))
        {
            case 'mb': case 'm': return (int)$size_str * 1048576;
            case 'kb': case 'k': return (int)$size_str * 1024;
            case 'gb': case 'g': return (int)$size_str * 1073741824;
            default: return $size_str;
        }
    }

?>