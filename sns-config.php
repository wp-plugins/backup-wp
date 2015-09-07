<?php
    define( 'SNS_VERSION' , '2.7.6' );
    define( 'SNS_DS'   , DIRECTORY_SEPARATOR );
    define( 'SNS_BACKUP_ROOT'   , plugin_dir_path( __FILE__ ) );
    define( 'SNS_BACKUP_ROOT_FOLDER_NAME',  'backup-wp');

    define( 'SNS_CLASSES_PATH'  , SNS_BACKUP_ROOT.'classes'.SNS_DS );
    define( 'SNS_LIB_PATH'  , SNS_BACKUP_ROOT.'lib'.SNS_DS );
    define( 'SNS_BACKUPS_PATH'  , WP_CONTENT_DIR.SNS_DS.SNS_BACKUP_ROOT_FOLDER_NAME.SNS_DS );
    define( 'SNS_BACKUPS_URL'   , content_url( '/'.SNS_BACKUP_ROOT_FOLDER_NAME.'/' ) );

    define( 'SNS_BACKUPS_MAX_COUNT'   , 1 );

    define( 'SNS_FTP_DEF_PORT'   , 21 );
    define( 'SNS_FTP_BACKUPS_FOLDER'   , 'sns_backups' );

    define( 'SNS_BACKUP_URL'    , plugins_url( SNS_BACKUP_ROOT_FOLDER_NAME ) );
    define( 'SNS_CSS_URL'       , SNS_BACKUP_URL.'/css' );
    define( 'SNS_JS_URL'        , SNS_BACKUP_URL.'/js' );

    define( 'SNS_PRO_URL' , 'http://sygnoos.com/wpbackup/' );
    define( 'SNS_API_URL' , 'https://sygnoos.com/tms/api/' );

    define( 'SNS_EMAIL_FILE_MAX_SIZE' , 18*1024*1024 ); // 18Mb

    define( 'SNS_ERR_LOG_FILE' , SNS_BACKUP_ROOT.'log.txt' );
    define( 'SNS_LOG_FILE' , SNS_BACKUP_ROOT.'log.txt' );
    define( 'SNS_LOG_START' , 'start' );
    define( 'SNS_LOG_END' , 'end' );

    define( 'SNS_PROCESS_DURATION' , 10*60*1000 );// ms
    define( 'SNS_PROCESS_STEP_COUNT' , 1000 );

    global $wpdb;
    define( 'SNS_DB_PREFIX'     , $wpdb->prefix.'sns_' );

    define('SNS_PRO_TOOLTIP' , '<span style="color:rgb(255,90,0);">(PRO)</span>');
?>