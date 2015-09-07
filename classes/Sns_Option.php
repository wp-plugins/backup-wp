<?php
    class Sns_Option {

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
                self::PLUGINS => WP_PLUGIN_DIR.SNS_DS,
                self::THEMES => get_theme_root().SNS_DS,
                self::WP_CONTENT => WP_CONTENT_DIR.SNS_DS
            );
        }

        public static function get_options( $actives = false ){
            global $wpdb;
            $table = SNS_DB_PREFIX.'options';
            $query_str = "SELECT `option`, `value` FROM {$table}";
            if( $actives ){
                $query_str .= " WHERE `value` = ".self::SET;
            }
            $options = $wpdb->get_results( $query_str , OBJECT_K );
            return $options;
        }

        public static function draw(){
            $options = self::get_options();
?>
            <div class="menu-content">
                <form role="form" autocomplete="off" class="options-form" action="">
                    <div class="form-group checkbox-containter">
                        <label class="checkbox-inline">
                            <input class="option-full" type="checkbox" disabled="disabled" <?php echo ($options['full']->value == self::SET)?' checked ':'';?>>Full backup
                        </label>
                        <label class="checkbox-inline">
                            <input class="option" type="checkbox" disabled="disabled" <?php echo ($options['plugins']->value == self::SET)?' checked ':'';?>>plugins folder
                        </label>
                        <label class="checkbox-inline">
                            <input class="option" type="checkbox" disabled="disabled" <?php echo ($options['wp_content']->value == self::SET)?' checked ':'';?>>Any folder inside wp-content
                        </label>
                    </div>
                    <div class="form-group checkbox-containter">
                        <label class="checkbox-inline">
                            <input class="option" disabled="disabled" type="checkbox" <?php echo ($options['database']->value == self::SET)?' checked ':'';?>>Database backup
                        </label>
                        <label class="checkbox-inline">
                            <input disabled="disabled" class="option" type="checkbox" <?php echo ($options['themes']->value == self::SET)?' checked ':'';?>>themes folder
                        </label>
                    </div>
                    <div class="separator"></div>
                    <div class="form-group">
                        <label class="control-label col-sm-4">Local backups count:</label>
                        <select class="form-control" disabled="disabled">
                            <?php
                                 echo '<option selected="selected">'.SNS_BACKUPS_MAX_COUNT.'</option>';
                            ?>
                        </select>
                    </div>
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-primary sns-action disabled">Save</button>
                </form>
            </div>
        <?php
        }

    }