<?php
    class Helper {

        public static function locations( $locations ){
?>
            <div class="form-group checkbox-containter">
                <label class="checkbox-inline">
                    <input type="checkbox" disabled="disabled" checked value=""> <?php _e( 'Local' ); ?>
                </label>
            </div>
            <div class="form-group checkbox-containter">
                <label class="checkbox-inline">
                    <input name="locations[email]" class="settings-email" type="checkbox" value="1" <?php echo ($locations['email']->status != '0')?' checked ':'';?>> <?php _e( 'Email (send notifications)' ); ?>
                </label>
            </div>
<?php
        }
    }