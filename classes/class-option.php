<?php
    require_once(dirname(__FILE__) . '/../sns-config.php');
    require_once(SNS_CLASSES_PATH.'class-settings.php');

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

        public static function save( $new_options ){

            global $wpdb;
            $table = SNS_DB_PREFIX.'options';
            $options_list = Option::get_options_list();

            foreach( $options_list as $option ){
                if(!isset( $new_options[$option] )){
                    $new_options[$option] = self::NOT_SET;
                }elseif( $option != self::COUNT ){
                    $new_options[$option] = self::SET;
                }
                $wpdb->query(
                    "  UPDATE {$table}
                       SET `value` = ".intval($new_options[$option])."
                       WHERE `option` = '{$option}'
                    "
                );
            }
            return true;

        }

        public static function draw(){
            $options = self::get_options();
?>
            <span class="menu-title"><?php _e('Select items to backup'); ?></span>
            <div class="menu-content">
                <form role="form" autocomplete="off" class="options-form" action="">
                    <div class="form-group checkbox-containter">
                        <label class="checkbox-inline">
                            <input name="options[full]" class="option-full" type="checkbox" value="<?php echo self::SET; ?>" <?php echo ($options['full']->value == self::SET)?' checked ':'';?>> <?php _e('Full backup'); ?>
                        </label>
                        <label class="checkbox-inline">
                            <input name="options[plugins]" class="option" type="checkbox" value="<?php echo self::SET; ?>" <?php echo ($options['plugins']->value == self::SET)?' checked ':'';?>> <?php _e('plugins folder'); ?>
                        </label>
                        <label class="checkbox-inline">
                            <input name="options[wp_content]" class="option" type="checkbox" value="<?php echo self::SET; ?>"  <?php echo ($options['wp_content']->value == self::SET)?' checked ':'';?>> <?php _e('Any folder inside wp-content'); ?>
                        </label>
                    </div>
                    <div class="form-group checkbox-containter">
                        <label class="checkbox-inline">
                            <input name="options[database]" class="option" type="checkbox" value="<?php echo self::SET; ?>"  <?php echo ($options['database']->value == self::SET)?' checked ':'';?>> <?php _e('Database backup'); ?>
                        </label>
                        <label class="checkbox-inline">
                            <input name="options[themes]" class="option" type="checkbox" value="<?php echo self::SET; ?>"  <?php echo ($options['themes']->value == self::SET)?' checked ':'';?>> <?php _e('themes folder'); ?>
                        </label>
                    </div>
                    <div class="separator"></div>
                    <div class="form-group">
                        <label class="control-label col-sm-4"><?php _e('Local backups count:'); ?></label>
                        <select name="options[count]" class="form-control">
                            <?php
                                for( $i=1; $i<=5; $i++ ){
                                    $selected = ( $options['count']->value == $i )?' selected="selected" ':'';
                                    echo '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="separator"></div>
                    <div class="form-group">
                        <label class="control-label col-sm-4"><?php _e('Email for notifications:'); ?></label>
                        <?php
                            $settings_data = Settings::get_settings_data();
                            $email_data = $settings_data[Settings::SETTINGS_EMAIL];
                        ?>
                        <input name="settings[<?php echo Settings::SETTINGS_EMAIL; ?>][email]" value="<?php echo json_decode($email_data->data)->email; ?>" id="sns-settings-email" type="text" class="form-control" data-action="<?php echo plugins_url( 'sns-backup/request-handler.php?act=settings&type='.Settings::SETTINGS_EMAIL );?>"/>
                    </div>
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-primary"><?php _e('Save'); ?></button>
                </form>
            </div>
        <?php
        }

    }