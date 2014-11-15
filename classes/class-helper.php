<?php
    require_once( dirname(__FILE__).'/../sns-config.php');

    class Helper {

        public static function locations( ){
?>
            <div class="form-group checkbox-containter">
                <label class="checkbox-inline">
                    <input type="checkbox" disabled="disabled" checked value=""> <?php _e( 'Local', 'sns-backup' ); ?>
                </label>
            </div>
            <div class="form-group checkbox-containter">
                <label class="checkbox-inline  sns-tooltip">
                    <input name="locations[email]" disabled="disabled" class="settings-email" type="checkbox" value="1"> <?php _e( 'Email (send notifications)' , 'sns-backup'); ?>
                </label>
            </div>
<?php
        }

        public static function showPROLabel(){
?>
            <a target="_blank" href="<?php echo SNS_PRO_URL; ?>" class="pro-v-label"><?php _e('PRO version', 'sns-backup'); ?></a>
<?php
        }

    }