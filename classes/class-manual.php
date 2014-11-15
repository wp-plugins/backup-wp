<?php
    require_once(dirname(__FILE__).'/../sns-config.php');
    require_once(SNS_CLASSES_PATH.'class-backup.php');

    class Manual {

        public static function backup(){

            $backup = new Sns_Backup('manual');
            return $backup->backup();
        }

        public static function draw(){

?>
            <span class="menu-title"><?php _e( 'Backup destination', 'sns-backup' ); ?></span>
            <div class="menu-content">
                <form class="manual-form" autocomplete="off" role="form" action="">
                    <?php Helper::locations(); ?>
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-primary"><?php _e( 'Backup', 'sns-backup' ); ?></button>
                </form>
            </div>
<?php

        }

    }
?>