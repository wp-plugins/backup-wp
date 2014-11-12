<?php
    define( 'SNS_BACKUP_ROOT'   , plugin_dir_path( __FILE__ ) );

    define( 'SNS_CLASSES_PATH'  , SNS_BACKUP_ROOT.'classes/' );
    define( 'SNS_BACKUPS_PATH'  , WP_CONTENT_DIR.'/backup-wordpress/' );
    define( 'SNS_BACKUPS_URL'   , content_url( '/backup-wordpress/' ) );

    define( 'SNS_BACKUP_URL'    , plugins_url( 'backup-wordpress' ) );
    define( 'SNS_CSS_URL'       , SNS_BACKUP_URL.'/css' );
    define( 'SNS_JS_URL'        , SNS_BACKUP_URL.'/js' );

    define( 'SNS_PRO_URL' , 'http://wpbackup.sygnoos.com' );

    define( 'SNS_EMAIL_FILE_MAX_SIZE' , 18*1024*1024 ); // 18Mb

    global $wpdb;
    define( 'SNS_DB_PREFIX'     , $wpdb->prefix.'sns_' );
?>
