<?php
    require_once(dirname(__FILE__).'/../sns-config.php');
    require_once(SNS_CLASSES_PATH.'class-backup.php');
    require_once(SNS_CLASSES_PATH.'class-settings.php');

    class Manual {

        public static function backup( $locations ){

            Settings::save_locations( $locations );
            $backup = new Sns_Backup('manual');
            return $backup->backup();
        }

        public static function get_locations(){

            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $config = $wpdb->get_results( "SELECT `name`, `data` , `manual_status` AS `status` FROM {$table}" , OBJECT_K );
            return $config;

        }

        public static function draw(){

?>
            <span class="menu-title"><?php _e( 'Backup destination' ); ?></span>
            <div class="menu-content">
                <form class="manual-form" autocomplete="off" role="form" action="">
                    <?php Helper::locations( self::get_locations() ); ?>
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-primary"><?php _e( 'Backup' ); ?></button>
                </form>
            </div>
<?php

        }

    }
?>