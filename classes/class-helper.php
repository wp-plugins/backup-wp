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
                    <input disabled="disabled" type="checkbox"> <?php _e( 'FTP' , 'sns-backup'); ?>
                </label>
            </div>
            <div class="form-group checkbox-containter">
                <label class="checkbox-inline  sns-tooltip">
                    <input disabled="disabled" type="checkbox"> <?php _e( 'Dropbox' , 'sns-backup'); ?>
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