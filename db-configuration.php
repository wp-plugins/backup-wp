<?php
require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'sns-config.php' );

function sns_configure_backup_db() {
    global $wpdb;

    $table_backups = SNS_DB_PREFIX.'backups';
    $table_options = SNS_DB_PREFIX.'options';
    $table_ftp = SNS_DB_PREFIX.'settings_ftp';
    $table_state = SNS_DB_PREFIX.'state';
    $table_destinations = SNS_DB_PREFIX.'settings_destinations';

    delete_option('sns_bakcup_db_version');
    if(false === get_option('sns_backup_version')){
        add_option('sns_backup_version', SNS_VERSION);
        $wpdb->query('DROP TABLE IF EXISTS '.$table_backups);
        $wpdb->query('DROP TABLE IF EXISTS '.$table_options);
        $wpdb->query('DROP TABLE IF EXISTS '.$table_ftp);
        $wpdb->query('DROP TABLE IF EXISTS '.$table_state);
        $wpdb->query('DROP TABLE IF EXISTS '.$table_destinations);
    }else{
        update_option('sns_backup_version', SNS_VERSION);
    }

    $charset_collate = '';

    if ( ! empty( $wpdb->charset ) ) {
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    }

    if ( ! empty( $wpdb->collate ) ) {
        $charset_collate .= " COLLATE {$wpdb->collate}";
    }

    $options_list = '';
    foreach( Sns_Option::get_options_list() as $option ){
        $options_list .= ',"'.$option.'"';
    }
    $options_list = substr( $options_list , 1 );

    $destinations_list = '';
    foreach( Sns_Destination::get_destinations_list() as $destination ){
        $destinations_list .= ',"'.$destination.'"';
    }
    $destinations_list = substr( $destinations_list , 1 );

    $sql = "CREATE TABLE IF NOT EXISTS `{$table_destinations}` (
                  `name` enum({$destinations_list}),
                  `manual_status` enum('0','1') NOT NULL DEFAULT '0',
                  `schedule_status` enum('0','1') NOT NULL DEFAULT '0',
                  PRIMARY KEY (`name`)
                )ENGINE=InnoDB $charset_collate;
        ";

    $wpdb->query( $sql );

    $sql = "    CREATE TABLE IF NOT EXISTS $table_backups (
                            `id` mediumint NOT NULL AUTO_INCREMENT,
                            `type` enum('manual','schedule') NOT NULL DEFAULT 'manual',
                            `info` varchar(1024) DEFAULT '' NOT NULL,
                            `backup_date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                            `restore_date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                            `hash` varchar(1024) DEFAULT '' NOT NULL,
                            `filename` varchar(1024) DEFAULT '' NOT NULL,
                            PRIMARY KEY (`id`)
                    )ENGINE=InnoDB $charset_collate;
              ";

    $wpdb->query( $sql );

    $sql = "    CREATE TABLE IF NOT EXISTS {$table_options} (
                            `option` enum({$options_list}) NOT NULL,
                            `value` tinyint  NOT NULL ,
                            PRIMARY KEY (`option`)
                    )ENGINE=InnoDB $charset_collate;
               ";
    $wpdb->query( $sql );

    $sql = "
                    CREATE TABLE IF NOT EXISTS {$table_ftp} (
                         `server` varchar(255) NOT NULL DEFAULT '',
                         `username` varchar(255) NOT NULL DEFAULT '',
                         `password` varchar(255) NOT NULL DEFAULT '',
                         `port` varchar(255) NOT NULL DEFAULT '',
                         PRIMARY KEY (`server`)
                    ) ENGINE=InnoDB $charset_collate;

                 ";
    $wpdb->query( $sql );

    $sql = "
                   CREATE TABLE IF NOT EXISTS {$table_state} (
                        `type` enum('backup','restore') NOT NULL,
                        `status` enum('finished','failed','active','none','ready_to_start') NOT NULL DEFAULT 'none',
                        `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `msg` VARCHAR(1024) NOT NULL DEFAULT '',
                        PRIMARY KEY (`type`)
                   ) ENGINE=InnoDB $charset_collate;

                 ";
    $wpdb->query( $sql );
    sns_configure_backup_db_data();

}

function sns_configure_backup_db_data(){
    global $wpdb;

    $table_name = SNS_DB_PREFIX.'options';
    $options = $wpdb->get_results( "SELECT COUNT(*) as `cnt` FROM {$table_name}" , ARRAY_A );
    if( $options[0]['cnt'] == 0 ){
        foreach( Sns_Option::get_options_list() as $option ){
            if( $option == Sns_Option::COUNT ){
                $val = intval( SNS_BACKUPS_MAX_COUNT);
            }else{
                $val = intval(Sns_Option::SET);
            }
            $wpdb->insert(
                $table_name,
                array(
                    'option' => $option,
                    'value' => $val
                )
            );
        }
    }

    $table_name = SNS_DB_PREFIX.'settings_destinations';
    $destinations = $wpdb->get_results( "SELECT COUNT(*) as `cnt` FROM {$table_name}" , ARRAY_A );
    if( $destinations[0]['cnt'] == 0 ){
        foreach(Sns_Destination::get_destinations_list() as $destination ){
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $destination
                )
            );
        }
    }

    $table_name = SNS_DB_PREFIX.'settings_ftp';
    $ftp = $wpdb->get_results( "SELECT COUNT(*) as `cnt` FROM {$table_name}" , ARRAY_A );
    if( $ftp[0]['cnt'] == 0 ){
        $wpdb->insert(
            $table_name,
            array(
                'server' => '',
                'port'  =>  SNS_FTP_DEF_PORT
            )
        );
    }
    $table_name = SNS_DB_PREFIX.'state';
    $state = $wpdb->get_results( "SELECT `status` FROM {$table_name}" , ARRAY_A );
    if( empty( $state ) ){
        $wpdb->insert(
            $table_name,
            array(
                'type'  => Sns_State::TYPE_BACKUP,
                'status'    => Sns_State::STATUS_NONE
            )
        );
    }else{
        $data = array( 'status' => Sns_State::STATUS_NONE );
        Sns_State::update( $data );
    }
}
?>