<?php
    require_once( dirname(__FILE__).'/sns-config.php' );

    require_once( SNS_CLASSES_PATH.'class-option.php' );
    require_once( SNS_CLASSES_PATH.'class-settings.php' );
    require_once( SNS_CLASSES_PATH.'class-schedule.php' );

    global $sns_backup_db_version;
    $sns_backup_db_version = '1.0';

    function sns_configure_backup_db() {
        global $wpdb;
        global $sns_backup_db_version;

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $table_settings = SNS_DB_PREFIX.'settings';
        $settingsList = '';
        foreach( Settings::get_settings_list() as $setting ){
            $settingsList .= ',"'.$setting.'"';
        }
        $settingsList = substr( $settingsList , 1 );

        $table_backups = SNS_DB_PREFIX.'backups';

        $table_options = SNS_DB_PREFIX.'options';
        $optionsList = '';
        foreach( Option::get_options_list() as $option ){
            $optionsList .= ',"'.$option.'"';
        }
        $optionsList = substr( $optionsList , 1 );

        $table_config = SNS_DB_PREFIX.'schedule_config';

        $sql = "    CREATE TABLE IF NOT EXISTS {$table_settings} (
                            `name` enum({$settingsList}) NOT NULL,
                            `data` varchar(1024) NOT NULL DEFAULT '',
                            `manual_status` enum('0','1') NOT NULL DEFAULT '0',
                            `schedule_status` enum('0','1') NOT NULL DEFAULT '0',
                            PRIMARY KEY (`name`)
                    ) $charset_collate;
        ";
        $wpdb->query( $sql );
        $sql = "    CREATE TABLE IF NOT EXISTS $table_backups (
                            `id` mediumint NOT NULL AUTO_INCREMENT,
                            `type` enum('manual','schedule') NOT NULL DEFAULT 'manual',
                            `info` varchar(1024) DEFAULT '' NOT NULL,
                            `backup_date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                            `restore_date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                            `hash` varchar(1024) DEFAULT '' NOT NULL,
                            PRIMARY KEY (`id`)
                    ) $charset_collate;
              ";

        $wpdb->query( $sql );
        $sql = "    CREATE TABLE IF NOT EXISTS {$table_options} (
                            `option` enum({$optionsList}) NOT NULL,
                            `value` tinyint  NOT NULL ,
                            PRIMARY KEY (`option`)
                    ) $charset_collate;
               ";
        $wpdb->query( $sql );
        $sql = "
                    CREATE TABLE IF NOT EXISTS {$table_config} (
                            `periodicity` enum('hourly','daily','weekly','monthly') NOT NULL DEFAULT 'hourly',
                            `enabled` enum('0','1') NOT NULL DEFAULT '0',
                            PRIMARY KEY (`periodicity`)
                    ) $charset_collate;

                 ";
        $wpdb->query( $sql );
        sns_configure_backup_db_data();
        add_option( 'sns_bakcup_db_version', $sns_backup_db_version );
    }

    function sns_configure_backup_db_data(){
        global $wpdb;

        $table_name = SNS_DB_PREFIX.'options';
        $options = $wpdb->get_results( "SELECT COUNT(*) as `cnt` FROM {$table_name}" , ARRAY_A );
        if( $options[0]['cnt'] == 0 ){
            foreach( Option::get_options_list() as $option ){
                $val = intval(Option::SET);
                $wpdb->insert(
                    $table_name,
                    array(
                       'option' => $option,
                       'value' => $val
                    )
                );
            }
        }

        $table_name = SNS_DB_PREFIX.'settings';
        $settings = $wpdb->get_results( "SELECT COUNT(*) as `cnt` FROM {$table_name}" , ARRAY_A );
        if( $settings[0]['cnt'] == 0 ){
            foreach( Settings::get_settings_list() as $settings ){
                $wpdb->insert(
                    $table_name,
                    array(
                        'name' => $settings
                    )
                );
            }
        }
        $table_name = SNS_DB_PREFIX.'schedule_config';
        $configs = $wpdb->get_results( "SELECT COUNT(*) as `cnt` FROM {$table_name}" , ARRAY_A );
        if( $configs[0]['cnt'] == 0 ){
            $wpdb->insert(
                $table_name,
                array(
                    'periodicity'    => Schedule::CONFIG_HOURLY,
                    'enabled'        => Schedule::CONFIG_DISABLED
                )
            );
        }
    }
?>