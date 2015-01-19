<?php
    class Sns_Backup {

        const BACKUP_MODE_MANUAL = 'manual';
        const BACKUP_MODE_SCHEDULE = 'schedule';

        private $type; //manual or schedule
        private $hash;
        private $filename;

        public function __construct( $type ){
            $this->type = $type;
        }

        public function backup(){

            $options = Sns_Option::get_options();
            $backupItems = array();
            self::configureCount( $options[Sns_Option::COUNT]->value );
            if( $options[Sns_Option::WP_CONTENT]->value == Sns_Option::SET ){
                $backupItems[Sns_Option::WP_CONTENT] = WP_CONTENT_DIR;
            }else{
                if( $options[Sns_Option::PLUGINS]->value == Sns_Option::SET ){
                    $backupItems[Sns_Option::PLUGINS] = WP_PLUGIN_DIR;
                }
                if( $options[Sns_Option::THEMES]->value == Sns_Option::SET ){
                    $backupItems[Sns_Option::THEMES] = get_theme_root();
                }
            }
            if( $options[Sns_Option::DB]->value == Sns_Option::SET ){
                $backupItems[Sns_Option::DB] = Sns_Option::SET;
            }

            try{
                $this->backup_items( $backupItems );

                Sns_Log::log_msg('[SUCCEED Backup]'.PHP_EOL);

                $destinations = new Sns_Destination( $this->type );
                $locations = $destinations->get_destinations();
                $filePath = SNS_BACKUPS_PATH.$this->filename.'.tar';
                if( $locations[Sns_Destination::SETTINGS_FTP]->status == Sns_Destination::SET ){
                    Sns_Log::log_action('Uploading to FTP server');
                    try{
                        $ftp = new Sns_Ftp();
                        $ftp->upload( $filePath , $this->filename.'.tar' );
                    }catch( Exception $e ){
                        Sns_Exception_Handler::log( $e );
                    }
                    Sns_Log::log_action('Uploading to FTP server' , SNS_LOG_END);
                }

            }catch( Exception $e ){
                Sns_Log::log_msg('[FAILED Backup]');
                Sns_History::delete_by_hash( $this->hash , $this->filename );
                throw $e;
            }
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
                                                        `hash`,
                                                        `filename`
                                                    FROM {$table}
                                                    ORDER BY `backup_date` ASC
                                                    LIMIT {$limit}"
                );

                foreach( $backups as $backup ){
                    $query = "    DELETE FROM `{$table}`
                                  WHERE `id` = {$backup->id}
                              ";
                    if( $wpdb->query($query) === false ){
                        throw new Sns_Exception_DB_Error( $query );
                    }
                    $file = SNS_BACKUPS_PATH.$backup->filename.'.tar';
                    if( unlink( $file ) === false ){
                        throw new Sns_Exception_Unavailable_Operation('Cannot delete the file '. $file);
                    }
                }

            }


        }

        public function backup_items( $items ){
            $hash = $this->get_new_hash();
            $filename = 'sns_'.get_bloginfo('name').'_'.date('Y.m.d_H:i').'_'.$hash;
            $dir =  SNS_BACKUPS_PATH.$filename;
            if( !mkdir( $dir ) ){
                throw new Sns_Exception_Unavailable_Operation( 'Cannot create the directory '.$dir );
            }
            chmod($dir , 0777);
            foreach( $items as $name => $path ){
                if( $name == Sns_Option::DB ){
                    $sql_file = $dir.'/wp_dump.sql';
                    $fp = fopen($sql_file, 'a+');
                    if( $fp === false ){
                        throw new Sns_Exception_Unavailable_Operation( 'Cannot open the file '.$sql_file );
                    }
                    if( fclose($fp) === false ){
                        throw new Sns_Exception_Unavailable_Operation( 'Cannot close the file '.$sql_file );
                    }
                    Sns_Log::log_action('Exporting DB');
                    Sns_Backup::export_db( $sql_file );
                    Sns_Log::log_action('Exporting DB' , SNS_LOG_END);
                    continue;
                }
                $path = realpath( $path );
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path , FilesystemIterator::SKIP_DOTS)
                );
                $exclude = array(
                    realpath( SNS_BACKUP_ROOT ),
                    realpath( SNS_BACKUPS_PATH ),
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
            Sns_History::delete_dir($dir);

            $this->hash = $hash;
            $this->filename = $filename;
            $this->save();
        }

        public static function save_restore( $backup_id ){

            global $wpdb;
            $table = SNS_DB_PREFIX.'backups';

            $backup_id = intval( $backup_id );
            $now = date('Y-m-d H:i:s');
            $query =  "   UPDATE `{$table}`
                          SET    `restore_date` = '{$now}'
                          WHERE  `id` = {$backup_id}
                      ";
            $wpdb->query( $query );

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
            $hash_esc = esc_sql( $hash );
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
            $options = Sns_Option::get_options( true );
            $option_list = array();
            foreach( $options as $option => $data ){
                if( $option == Sns_Option::FULL ){
                    $option_list = array(
                        $option
                    );
                    break;
                }
                if( $option != Sns_Option::COUNT  ){
                    $option_list []= $option;
                }
            }
            $destination = new Sns_Destination( $this->type );
            $destinations = $destination->get_destinations();
            $destination_list = array();
            foreach( $destinations as $name=>$dst ){
                if( $dst->status == Sns_Destination::SET ){
                    $destination_list[]= $name;
                }
            }
            $info = json_encode( array(
                'options' => $option_list,
                'destinations' => $destination_list
            ) );
            $data =   array(
                'type' => $this->type,
                'info' => $info,
                'backup_date' => date('Y-m-d H:i:s'),
                'hash' => $this->hash,
                'filename' => $this->filename
            );
            $r = $wpdb->insert(
                $table_name,
                $data
            );
            if( $r === false ){
                throw new Sns_Exception_DB_Error( 'Error on inserting into '.$table_name.' data: '.json_encode( $data ) );
            }

        }

        public static function get_locations(){

            global $wpdb;
            $table = SNS_DB_PREFIX.'settings';
            $config = $wpdb->get_results( "SELECT `name`, `manual_status` AS `status` FROM {$table}" , OBJECT_K );
            return $config;

        }

        public static function export_db( $sql_file ) {
            global $wpdb;

            $export_str = "-- Wordpress database dump --\n";

            $query = "SHOW TABLES";
            $tables = $wpdb->get_results( $query , ARRAY_A );
            if( $tables === false ){
                throw new Sns_Exception_DB_Error( $query );
            }
            foreach ( $tables as $db_item ) {
                $table = current( $db_item );
                $query = "SHOW CREATE TABLE " . $table;
                $create = $wpdb->get_var( $query , 1 );
                if( $create === false ){
                    throw new Sns_Exception_DB_Error( $query );
                }
                $myisam = strpos( $create, 'MyISAM' );

                if( $table == SNS_DB_PREFIX.'backups'  ||
                    $table == SNS_DB_PREFIX.'settings'  ||
                    $table == SNS_DB_PREFIX.'options' ||
                    $table == SNS_DB_PREFIX.'settings_destinations' ||
                    $table == SNS_DB_PREFIX.'settings_ftp' ||
                    $table == SNS_DB_PREFIX.'state' ||
                    $table == $wpdb->prefix.'options' ||
                    $table == $wpdb->prefix.'terms' ||
                    $table == $wpdb->prefix.'term_relationships' ||
                    $table == $wpdb->prefix.'term_taxonomy' ||
                    $table == $wpdb->prefix.'users' ||
                    $table == $wpdb->prefix.'usermeta' ) continue;

                $export_str .= "-- ----------------------------------------------------------\n";
                $export_str .= "-- Dump of table `" . $table . "` --\n";
                $export_str .= "-- ----------------------------------------------------------\n\n";

                $export_str .= "DROP TABLE IF EXISTS `" . $table . "`;\n\n" . $create . ";\n\n";

                $query = "SELECT * FROM `" . $table . "` LIMIT 1000";
                $data = $wpdb->get_results($query, ARRAY_A );
                if( $query === false ){
                    throw new Sns_Exception_DB_Error( $query );
                }
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
                                    $entry[$key] = "'" . esc_sql($value) . "'";
                                $cols .= ", `".$key."` ";
                            }
                            $cols = substr( $cols , 1 );
                            $export_str .= "INSERT INTO `" . $table . "` ( " . $cols . " ) VALUES ( " . implode( ", ", $entry ) . " );\n";
                        }

                        $offset += 1000;
                        $query = "SELECT * FROM `" . $table . "` LIMIT " . $offset . ",1000";
                        $data = $wpdb->get_results($query, ARRAY_A );
                        if( $data === false ){
                            throw new Sns_Exception_DB_Error( $query );
                        }
                    }

                    if ( false !== $myisam )
                        $export_str .=  "\n --!40000 ALTER TABLE `".$table."` ENABLE KEYS ;";
                }
            }

            if ( file_put_contents($sql_file ,  $export_str) === false ){
                throw new Sns_Exception_Unavailable_Operation( 'Cannot write in '.$sql_file );
            }
        }

        public static function import_db( $sql_file,  $clear=false ){

            if (!file_exists($sql_file)){
                throw new Sns_Exception_Not_Found( 'File not found '.$sql_file );
            }
            global $wpdb;

//            $wpdb->query('SET AUTOCOMMIT=0');
            $wpdb->query('START TRANSACTION');
            $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

            if($clear){
                $query = "SHOW TABLES";
                $tables = $wpdb->get_results( $query , ARRAY_A );
                if( $tables === false ){
                    throw new Sns_Exception_DB_Error( $query );
                }
                foreach ( $tables as $db_item ) {
                    $table = current( $db_item );
                    if( $table == SNS_DB_PREFIX.'backups'  ||
                        $table == SNS_DB_PREFIX.'settings'  ||
                        $table == SNS_DB_PREFIX.'options' ||
                        $table == SNS_DB_PREFIX.'settings_destinations' ||
                        $table == SNS_DB_PREFIX.'settings_ftp' ||
                        $table == SNS_DB_PREFIX.'state' ||
                        $table == $wpdb->prefix.'options' ||
                        $table == $wpdb->prefix.'terms' ||
                        $table == $wpdb->prefix.'term_relationships' ||
                        $table == $wpdb->prefix.'term_taxonomy' ||
                        $table == $wpdb->prefix.'users' ||
                        $table == $wpdb->prefix.'usermeta' ){
                        continue;
                    }
                    $query = 'DROP TABLE IF EXISTS '.$table;
                    if( $wpdb->query($query) === false ){
                        $wpdb->query('ROLLBACK');
                        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
                        throw new Sns_Exception_DB_Error( $query );

                    }
                }
            }

            $templine = '';
            $lines = file($sql_file);
            if( $lines === false ){
                throw new Sns_Exception_Unavailable_Operation( 'Cannot read the file '.$sql_file );
            }
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
                        throw new Sns_Exception_DB_Error( $templine );
                    }
                    $templine = '';
                }
            }
            $wpdb->query('COMMIT');
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

        }

    }
?>