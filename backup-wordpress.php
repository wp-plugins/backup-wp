<?php
/**
 * Plugin Name: Backup
 * Plugin URI: http://sygnoos.com/wpbackup/
 * Description: Fully functional FREE Wordpress backup plugin which helps you to create manually/automatically custo​m​ized backups of your Wordpress based web site.
 * Version: 2.6.1
 * Author: Sygnoos
 * Author URI: http://www.sygnoos.com
 * License: GPLv2
 */

    require_once( dirname(__FILE__).'/sns-config.php');
    require_once( SNS_BACKUP_ROOT.'/db-configuration.php');
    require_once( SNS_CLASSES_PATH.'class-backup.php'   );
    require_once( SNS_BACKUP_ROOT.'/request-handler.php');

    register_activation_hook( __FILE__, 'sns_configure_backup_db' );
    register_activation_hook( __FILE__, 'sns_configure_backup_db_data' );
    register_activation_hook( __FILE__, 'sns_configure_backups_store' );

    register_uninstall_hook( __FILE__, 'sns_backup_uninstall' );

    add_action( 'admin_menu', 'register_sns_backup_menu_page' );
    add_action( 'admin_enqueue_scripts', 'sns_backup_adding_scripts' );

    // adding actions to handle ajax requests
    add_action( 'wp_ajax_sns_history_update', 'sns_backup_update_history' );
    add_action( 'wp_ajax_sns_manual_backup', 'sns_backup_manual_backup' );
    add_action( 'wp_ajax_sns_backup_delete', 'sns_backup_backup_delete' );
    add_action( 'wp_ajax_sns_backup_restore', 'sns_backup_backup_restore' );
    add_action( 'wp_ajax_sns_external_restore', 'sns_backup_external_restore' );

    function sns_configure_backups_store(){
        if( !is_dir( SNS_BACKUPS_PATH ) ){
            if( !mkdir( SNS_BACKUPS_PATH ) ){
                trigger_error('Cannot create folder '.SNS_BACKUPS_PATH, E_USER_ERROR);
            }
        }
    }

    function sns_backup_uninstall(){

        //drop sns backup plugins tables
        global $wpdb;
        $table = SNS_DB_PREFIX.'backups';
        $wpdb->query( "DROP TABLE `{$table}`" );
        $table = SNS_DB_PREFIX.'options';
        $wpdb->query( "DROP TABLE `{$table}`" );

        History::delete_dir( SNS_BACKUPS_PATH );

    }

    function register_sns_backup_menu_page(){
        global $sns_settings;
        $sns_settings = add_menu_page( __('Backup', 'sns-backup'), __('Backup', 'sns-backup'), 'manage_options', SNS_BACKUP_ROOT_FOLDER_NAME.'/sns-backup-admin.php', '', 'dashicons-backup', 76 );
    }

    function my_style_loader_tag_function($tag){
        return preg_replace("/='stylesheet' id='less-css'/", "='stylesheet/less' id='less-css'", $tag);
    }

    function sns_backup_adding_scripts() {

        wp_enqueue_style('less', SNS_CSS_URL.'/wordpress-bootstrap.less');
        add_filter('style_loader_tag', 'my_style_loader_tag_function');

        wp_register_style('sns-backup-style', SNS_CSS_URL.'/sns-backup-general.css');
        wp_enqueue_style('sns-backup-style');

        wp_register_style('sns-backup-tooltipster-style', SNS_CSS_URL.'/tooltipster-master/css/tooltipster.css');
        wp_enqueue_style('sns-backup-tooltipster-style');

        wp_register_script('sns-backup-tooltipster-script', SNS_CSS_URL.'/tooltipster-master/js/jquery.tooltipster.min.js');
        wp_enqueue_script('sns-backup-tooltipster-script');

        wp_register_script('sns-backup-less-js-script', SNS_JS_URL.'/less-1.7.5.min.js');
        wp_enqueue_script('sns-backup-less-js-script');

        wp_register_style('sns-backup-jquery-ui-style', SNS_CSS_URL.'/jquery-ui.min.css');
        wp_enqueue_style('sns-backup-jquery-ui-style');

        wp_enqueue_script( 'sns-general-script', SNS_JS_URL.'/sns-backup-general.js', array('jquery') );

        wp_register_script('jquery-ui-tabs', includes_url('js/jquery/ui/jquery.ui.tabs.min.js'));
        wp_enqueue_script('jquery-ui-tabs');

        wp_register_script('plupload', includes_url('js/plupload/plupload.js'));
        wp_enqueue_script('plupload');

        wp_register_script('sns-backup-bootstrap-script', SNS_CSS_URL.'/bootstrap/js/bootstrap.min.js');
        wp_enqueue_script('sns-backup-bootstrap-script');

    }

?>