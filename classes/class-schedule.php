<?php
    require_once(dirname(__FILE__) . '/../sns-config.php');
    require_once( SNS_CLASSES_PATH.'class-backup.php' );

    class Schedule {

        const CONFIG_ENABLED = '1';
        const CONFIG_DISABLED = '0';

        const CONFIG_HOURLY = 'hourly';
        const CONFIG_DAILY = 'daily';
        const CONFIG_WEEKLY = 'weekly';
        const CONFIG_MONTHLY = 'monthly';

        public static function get_config_options(){
            return array(
                self::CONFIG_HOURLY,
                self::CONFIG_DAILY,
                self::CONFIG_WEEKLY,
                self::CONFIG_MONTHLY
            );
        }

        public static function get_locations(){
            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $config = $wpdb->get_results( "SELECT `name`, `schedule_status` AS `status` FROM {$table}" , OBJECT_K );
            return $config;
        }

        public static function get_config(){
            global $wpdb;
            $table = SNS_DB_PREFIX.'schedule_config';
            $config = $wpdb->get_results( "SELECT `enabled`, `periodicity` FROM {$table} LIMIT 1" , ARRAY_A );
            return $config[0];
        }

        public static function save( $new_config ){
            global $wpdb;
            $table = SNS_DB_PREFIX.'schedule_config';

            $options = self::get_config_options();

            if( in_array( $new_config['periodicity'] , $options ) ){
                $enabled = ( $new_config['enabled'] == self::CONFIG_ENABLED )?self::CONFIG_ENABLED:self::CONFIG_DISABLED;
                $wpdb->query(
                    "  UPDATE   {$table}
                       SET      `enabled` = '{$enabled}',
                                `periodicity` = '{$new_config['periodicity']}'
                    "
                );

                wp_clear_scheduled_hook( 'sns_schedule_run_backup_hourly' );
                wp_clear_scheduled_hook( 'sns_schedule_run_backup_daily' );
                wp_clear_scheduled_hook( 'sns_schedule_run_backup_weekly' );
                wp_clear_scheduled_hook( 'sns_schedule_run_backup_monthly' );

                if( $enabled == self::CONFIG_ENABLED){
                     switch( $new_config['periodicity'] ){
                         case self::CONFIG_HOURLY:
                             wp_schedule_event( time() + 60*60, 'hourly', 'sns_schedule_run_backup_hourly');
                             break;
                         case self::CONFIG_DAILY:
                             wp_schedule_event( time() + 60*60*24, 'daily', 'sns_schedule_run_backup_daily');
                             break;
                         case self::CONFIG_WEEKLY:
                             wp_schedule_event( time() + 60*60*24*7, 'weekly', 'sns_schedule_run_backup_weekly');
                             break;
                         case self::CONFIG_MONTHLY:
                             wp_schedule_event( time() + 60*60*24*30, 'monthly', 'sns_schedule_run_backup_monthly');
                             break;
                     }
                }
                return true;
            }
            return false;

        }

        public static function on_deactivate(){

            self::clearCrons();
            global $wpdb;
            $table = SNS_DB_PREFIX.'schedule_config';
            $status = self::CONFIG_DISABLED;
            $wpdb->query(
                "  UPDATE   {$table}
                   SET      `enabled` = '{$status}'
                    "
            );

        }

        public static function clearCrons(){

            wp_clear_scheduled_hook( 'sns_schedule_run_backup_hourly' );
            wp_clear_scheduled_hook( 'sns_schedule_run_backup_daily' );
            wp_clear_scheduled_hook( 'sns_schedule_run_backup_weekly' );
            wp_clear_scheduled_hook( 'sns_schedule_run_backup_monthly' );

        }

        public static function draw(){
            $config = self::get_config();
?>
            <span class="menu-title"><?php _e('Configure schedule'); ?></span>
            <div class="tab-content">
                <form role="form" autocomplete="off" class="schedule-form" action="">
                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input name="config[enabled]" type="checkbox" value="1" <?php echo ($config['enabled'] == self::CONFIG_ENABLED)?' checked ':'';?>> <?php _e('Enable' );?>
                        </label>
                    </div>

                    <label><?php _e('How often you want to backup?' );?></label>
                    <div class="periodicity-block">
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" name="config[periodicity]" value="<?php echo self::CONFIG_HOURLY;?>" <?php echo ($config['periodicity'] == self::CONFIG_HOURLY)?' checked ':'';?>> <?php _e('Each hour' );?>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" name="config[periodicity]" value="<?php echo self::CONFIG_DAILY;?>" <?php echo ($config['periodicity'] == self::CONFIG_DAILY)?' checked ':'';?>> <?php _e('Each day' );?>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" name="config[periodicity]" value="<?php echo self::CONFIG_WEEKLY;?>" <?php echo ($config['periodicity'] == self::CONFIG_WEEKLY)?' checked ':'';?>> <?php _e('Each week' );?>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" name="config[periodicity]" value="<?php echo self::CONFIG_MONTHLY;?>" <?php echo ($config['periodicity'] == self::CONFIG_MONTHLY)?' checked ':'';?>> <?php _e('Each month' );?>
                            </label>
                        </div>
                    </div>
                    <div class="separator"></div>
                    <label><?php _e('Where you want to save your backup?' );?></label>
                    <?php Helper::locations( self::get_locations() ); ?>
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-primary"><?php _e( 'Save' );?></button>
                </form>
            </div>
        <?php
        }

    }