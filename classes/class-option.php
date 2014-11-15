<?php
    require_once(dirname(__FILE__) . '/../sns-config.php');

    class Option {

        const FULL = 'full';
        const DB = 'database';
        const PLUGINS = 'plugins';
        const THEMES = 'themes';
        const WP_CONTENT = 'wp_content';
        const COUNT = 'count';

        const SET = 1;
        const NOT_SET = 0;

        public static function get_options_list(){
            return array(
                self::FULL,
                self::DB,
                self::PLUGINS,
                self::THEMES,
                self::WP_CONTENT,
                self::COUNT
            );
        }

        public static function get_locations(){
            return array(
                self::DB => '',
                self::PLUGINS => WP_PLUGIN_DIR.'/',
                self::THEMES => get_theme_root().'/',
                self::WP_CONTENT => WP_CONTENT_DIR.'/'
            );
        }

        public static function get_options( $actives = false ){
            global $wpdb;
            $table = SNS_DB_PREFIX.'options';
            $query_str = "SELECT `option`, `value` FROM {$table}";
            if( $actives ){
                $query_str .= " WHERE `value` = ".Option::SET;
            }
            $options = $wpdb->get_results( $query_str , OBJECT_K );
            return $options;
        }

    }