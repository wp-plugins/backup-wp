<?php
    require_once(dirname(__FILE__) . '/../sns-config.php');
    require_once(SNS_CLASSES_PATH.'class-history.php');
    require_once(SNS_CLASSES_PATH.'class-option.php');

    class Sns_Backup {

        private $type; //manual or schedule
        private $hash;

        public function __construct( $type ){
            $this->type = $type;
        }

        public function backup(){

            $options = Option::get_options();
            $backupItems = array();
            self::configureCount( $options[Option::COUNT]->value );
            if( $options[Option::WP_CONTENT]->value == Option::SET ){
                $backupItems[Option::WP_CONTENT] = WP_CONTENT_DIR;
            }else{
                if( $options[Option::PLUGINS]->value == Option::SET ){
                    $backupItems[Option::PLUGINS] = WP_PLUGIN_DIR;
                }
                if( $options[Option::THEMES]->value == Option::SET ){
                    $backupItems[Option::THEMES] = get_theme_root();
                }
            }
            if( $options[Option::DB]->value == Option::SET ){
                $backupItems[Option::DB] = Option::SET;
            }

            $settings = Settings::get_settings( $this->type );
            $userEmail = null;
            if( isset( $settings[Settings::SETTINGS_EMAIL] ) ){
                $data = json_decode( $settings[Settings::SETTINGS_EMAIL]->data , true );
                $userEmail = $data['email'];
            }

            if( $this->backup_items( $backupItems ) ){
                if( !is_null( $userEmail ) ){
                    $backup_file = SNS_BACKUPS_PATH.'sns_backup-'.$this->hash.'.tar';

                    $file_size = filesize( $backup_file );
                    if( $file_size !== false && $file_size < SNS_EMAIL_FILE_MAX_SIZE ){
                        wp_mail($userEmail , __('Backup of your Wordpress site!', 'sns-backup') , __('Your backup is ready. Attached you can find the backup file.', 'sns-backup') , '' , $backup_file);
                    }else{
                        $link = SNS_BACKUPS_URL.'sns_backup-'.$this->hash.'.tar';
                        wp_mail($userEmail , __('Backup of your Wordpress site!', 'sns-backup') , __('Your backup is ready. You can download it <a href="'.$link.'">here</a>') ,  'Content-type: text/html', 'sns-backup' );
                    }
                }
                return true;
            }
            if( !is_null( $userEmail ) ){
                wp_mail($userEmail , __('Backup of your Wordpress site!', 'sns-backup') , __('Your backup couldn’t be created!', 'sns-backup'));
            }
            return false;

        }

        public static function configureCount( $count ){
            global $wpdb;
            $table = SNS_DB_PREFIX.'backups';
            $backups = $wpdb->get_results( "SELECT
                                                COUNT(*) as `count`
                                           FROM {$table}
                                           LIMIT 1"
            );
            $backup_cnt = intval($backups[0]->count);

            if( $backup_cnt >= $count ){
                $limit = $backup_cnt - $count + 1;
                $backups = $wpdb->get_results( "SELECT
                                                    `id`,
                                                    `hash`
                                                FROM {$table}
                                                ORDER BY `backup_date` ASC
                                                LIMIT {$limit}"
                );
                foreach( $backups as $backup ){
                    $wpdb->query(
                        "   DELETE FROM {$table}
                            WHERE `id` = {$backup->id}
                        "
                    );
                    @unlink(SNS_BACKUPS_PATH.'sns_backup-'.$backup->hash.'.tar');
                }

            }


        }

        public function backup_items( $items ){
            $hash = $this->get_new_hash();
            $dir =  SNS_BACKUPS_PATH.'sns_backup-'.$hash;
            if( mkdir( $dir ) ){
                chmod($dir , 0777);
                foreach( $items as $name => $path ){
                    if( $name == Option::DB ){
                        $sql_file = $dir.'/wp_dump.sql';
                        $fp = fopen($sql_file, 'a+');
                        fclose($fp);
                        Sns_Backup::export_db( $sql_file );
                        continue;
                    }
                    $path = realpath( $path );
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path , FilesystemIterator::SKIP_DOTS)
                    );
                    $exclude = array(
                        realpath( SNS_BACKUP_ROOT ),
                        realpath( WP_CONTENT_DIR.'/debug.log' )
                    );
                    $filterIterator = new CallbackFilterIterator($iterator , function ($file) use ($exclude) {
                        foreach( $exclude as $excludeFile ){
                            if( strpos( $file, $excludeFile ) !== false ){
                                return false;
                            }
                        }
                        return true;
                    });
                    $phar = new PharData($dir.'/'.$name.'.tar');
                    $phar->buildFromIterator($filterIterator , $path);
                }
                $phar = new PharData($dir.'.tar');
                $phar->buildFromDirectory($dir);
                History::delete_dir($dir);

                $this->hash = $hash;
                $this->save();

                Settings::get_settings( $this->type );

                return true;
            }
            return false;

        }

        public static function save_restore( $backup_id ){

            global $wpdb;
            $table = SNS_DB_PREFIX.'backups';

            $backup_id = intval( $backup_id );
            $now = date('Y-m-d H:i:s');
            $wpdb->query(
                "  UPDATE {$table}
                   SET    `restore_date` = '{$now}'
                   WHERE  `id` = {$backup_id}
                "
            );
        }

        public function get_new_hash(){

            global $wpdb;
            $hash = '';
            $symbols = array(
                'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P',
                'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z',
                'X', 'C', 'V', 'B', 'N', 'M',
                '0', '1', '2' , '3', '4', '5', '6', '7', '8', '9'
            );
            $symbols_max_index = count( $symbols ) - 1;
            for( $i = 0; $i < 8; $i++ ) {
                $hash .= $symbols[ rand( 0, $symbols_max_index )  ];
            }
            $hash_esc = mysql_real_escape_string( $hash );
            $table = SNS_DB_PREFIX.'backups';
            $backup = $wpdb->get_results( "SELECT
                                                `id`
                                           FROM {$table}
                                           WHERE `hash` = '{$hash_esc}'"
                                         );

            if( empty( $backup ) ){
                return $hash;
            }
            return $this->get_new_hash();

        }


        public function save(){

            global $wpdb;
            $table_name = SNS_DB_PREFIX.'backups';
            $options = Option::get_options( true );
            $option_list = array();
            foreach( $options as $option => $data ){
                if( $option == Option::FULL ){
                    $option_list = array(
                        $option
                    );
                    break;
                }
                if( $option != Option::COUNT  ){
                    $option_list []= $option;
                }
            }
            $info = json_encode( array(
                'options' => $option_list
            ) );
            $wpdb->insert(
                $table_name,
                array(
                    'type' => $this->type,
                    'info' => $info,
                    'backup_date' => date('Y-m-d H:i:s'),
                    'hash' => $this->hash
                )
            );

        }

        public static function get_locations(){

            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $config = $wpdb->get_results( "SELECT `name`, `manual_status` AS `status` FROM {$table}" , OBJECT_K );
            return $config;

        }

        public static function export_db( $sql_file ) {
            global $wpdb;

            $timer_start = microtime( true );

            $export_str = "-- Wordpress database dump --\n";

            $tables = $wpdb->get_results( "SHOW TABLES", ARRAY_A );
            foreach ( $tables as $db_item ) {
                $table = current( $db_item );
                $create = $wpdb->get_var( "SHOW CREATE TABLE " . $table, 1 );
                $myisam = strpos( $create, 'MyISAM' );

                if( $table == SNS_DB_PREFIX.'backups'  ||
                    $table == SNS_DB_PREFIX.'settings'  ||
                    $table == SNS_DB_PREFIX.'schedule_config'  ||
                    $table == SNS_DB_PREFIX.'options' ||
                    $table == $wpdb->prefix.'options' ||
                    $table == $wpdb->prefix.'terms' ||
                    $table == $wpdb->prefix.'term_relationships' ||
                    $table == $wpdb->prefix.'term_taxonomy' ||
                    $table == $wpdb->prefix.'users' ||
                    $table == $wpdb->prefix.'usermeta' ) continue;

                $export_str .= "------------------------------------------------------------\n";
                $export_str .= "-- Dump of table `" . $table . "` --\n";
                $export_str .= "------------------------------------------------------------\n\n";

                $export_str .= "DROP TABLE IF EXISTS `" . $table . "`;\n\n" . $create . ";\n\n";

                $data = $wpdb->get_results("SELECT * FROM `" . $table . "` LIMIT 1000", ARRAY_A );
                if ( ! empty( $data ) ) {
                    if ( false !== $myisam )
                        $export_str .=  "-- !40000 ALTER TABLE `".$table."` DISABLE KEYS ;\n\n";

                    $offset = 0;
                    while ( ! empty( $data ) ) {
                        foreach ( $data as $entry ) {
                            $cols = '';
                            foreach ( $entry as $key => $value ) {
                                if ( NULL === $value )
                                    $entry[$key] = "NULL";
                                elseif ( "" === $value || false === $value )
                                    $entry[$key] = "''";
                                elseif ( !is_numeric( $value ) )
                                    $entry[$key] = "'" . mysql_real_escape_string($value) . "'";
                                $cols .= ", `".$key."` ";
                            }
                            $cols = substr( $cols , 1 );
                            $export_str .= "INSERT INTO `" . $table . "` ( " . $cols . " ) VALUES ( " . implode( ", ", $entry ) . " );\n";
                        }

                        $offset += 1000;
                        $data = $wpdb->get_results("SELECT * FROM `" . $table . "` LIMIT " . $offset . ",1000", ARRAY_A );
                    }

                    if ( false !== $myisam )
                        $export_str .=  "\n --!40000 ALTER TABLE `".$table."` ENABLE KEYS ;";
                }
            }

            file_put_contents($sql_file ,  $export_str);
            return ( microtime( true ) - $timer_start );
        }

        public static function import_db( $sql_file,  $clear=false ){

            if (!file_exists($sql_file)){
                return false;
            }
            global $wpdb;

            $wpdb->query('SET AUTOCOMMIT=0');
            $wpdb->query('START TRANSACTION');
            $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

            if($clear){
                $tables = $wpdb->get_results( "SHOW TABLES", ARRAY_A );
                foreach ( $tables as $db_item ) {
                    $table = current( $db_item );
                    if( $table == SNS_DB_PREFIX.'backups'  ||
                        $table == SNS_DB_PREFIX.'settings'  ||
                        $table == SNS_DB_PREFIX.'schedule_config'  ||
                        $table == SNS_DB_PREFIX.'options' ||
                        $table == $wpdb->prefix.'options' ||
                        $table == $wpdb->prefix.'terms' ||
                        $table == $wpdb->prefix.'term_relationships' ||
                        $table == $wpdb->prefix.'term_taxonomy' ||
                        $table == $wpdb->prefix.'users' ||
                        $table == $wpdb->prefix.'usermeta' ){
                        continue;
                    }
                    if( $wpdb->query('DROP TABLE IF EXISTS '.$table) === false ){
                        $wpdb->query('ROLLBACK');
                        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
                        return false;

                    }
                }
            }

            $templine = '';
            $lines = file($sql_file);
            foreach ($lines as $line){
                // ignore comments
                if (substr(trim($line), 0, 2) == '--' || $line == '' || substr(trim($line) , 0 , 1) == '#'){
                    continue;
                }
                $templine .= $line;
                // semicolon is the end of query
                if (substr(trim($line), -1, 1) == ';'){
                    if( $wpdb->query($templine) === false ){
                        $wpdb->query('ROLLBACK');
                        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
                        return false;
                    }
                    $templine = '';
                }
            }
            $wpdb->query('COMMIT');
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
            return true;

        }

    }
?>