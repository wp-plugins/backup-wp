<?php
    require_once(dirname(__FILE__) . '/../sns-config.php');

    class Settings {

        const SETTINGS_DROPBOX = 'dropbox';
        const SETTINGS_GOOGLE_DRIVE = 'google_drive';
        const SETTINGS_AMAZON_S3 = 'amazon_s3';
        const SETTINGS_EMAIL = 'email';
        const SETTINGS_ONE_DRIVE = 'one_drive';

        const SET = '1';
        const NOT_SET = '0';

        public static function get_settings_list(){
            return array(
                self::SETTINGS_DROPBOX,
                self::SETTINGS_GOOGLE_DRIVE,
                self::SETTINGS_AMAZON_S3,
                self::SETTINGS_EMAIL,
                self::SETTINGS_ONE_DRIVE
            );
        }

        public static function save( $type , $settings ){

            global $wpdb;

            $table = SNS_DB_PREFIX.'settings';
            $data = json_encode($settings);

            return $wpdb->query(
                "  UPDATE {$table}
                       SET    `data` = '{$data}'
                       WHERE  `name` = '{$type}'
                    "
            );

        }

        public static function validate( $settings ){
            if( isset ( $settings[Settings::SETTINGS_EMAIL]['email'] ) ){
                $email = $settings[Settings::SETTINGS_EMAIL]['email'];
                return filter_var ( $email , FILTER_VALIDATE_EMAIL ) && preg_match ( '/@.+\./', $email );
            }
            return true;
        }

        public static function get_email(){
            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $email = mysql_real_escape_string( Settings::SETTINGS_EMAIL );
            $settings = $wpdb->get_results( "SELECT
                                                `name`,
                                                `data`
                                             FROM {$table}
                                             WHERE `name` = '{$email}'" , ARRAY_A );
            $data = json_decode( $settings[0]['data'] , true );
            return $data['email'];
        }

        public static function get_settings( $for = 'manual' ){
            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $status = ($for == 'manual')?'manual_status':'schedule_status';
            $st = self::SET;
            $settings = $wpdb->get_results( "SELECT
                                                `name`,
                                                `data`
                                             FROM {$table}
                                             WHERE `{$status}` = '{$st}'" , OBJECT_K );
            return $settings;
        }

        public static function get_settings_data(){
            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $settings = $wpdb->get_results( "SELECT
                                                `name`,
                                                `data`
                                             FROM {$table}
                                             "
                                             , OBJECT_K );
            return $settings;
        }

        public static function save_locations( $locations , $for = 'manual' ){

            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $settings_list = self::get_settings_list();

            $status_col = 'manual_status';
            if( $for == 'schedule' ){
                $status_col = 'schedule_status';
            }

            foreach( $settings_list as $settings ){
                if( isset( $locations[$settings] ) ){
                    $status_val = '1';
                }else{
                    $status_val = '0';
                }
                $wpdb->query(
                    "  UPDATE {$table}
                       SET    `{$status_col}` = '{$status_val}'
                       WHERE  `name` = '{$settings}'
                    "
                );
            }

        }

    }