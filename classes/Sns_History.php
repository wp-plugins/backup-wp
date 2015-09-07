<?php
    class Sns_History {

        public static function get_history(){

            global $wpdb;
			$options = Sns_Option::get_options();
			$limitBackups = $options[Sns_Option::COUNT]->value;
            $table = SNS_DB_PREFIX.'backups';
            $history = $wpdb->get_results( "SELECT
                                                `id`,
                                                `type`,
                                                `info`,
                                                `backup_date`,
                                                `hash`,
                                                `filename`
                                            FROM {$table}
                                            ORDER BY `backup_date` DESC
											LIMIT $limitBackups"
                                          , OBJECT_K );
            return $history;

        }

        public static function get_backup_by_id( $id ){

            global $wpdb;
            $id = intval( $id );
            $table = SNS_DB_PREFIX.'backups';
            $data =  $wpdb->get_results( "SELECT
                                          `id`,
                                          `hash`,
                                          `filename`
                                        FROM {$table}
                                        WHERE `id` = {$id}
                                        LIMIT 1"
            );
            if( empty( $data ) ){
                throw new Sns_Exception_Not_Found('Backup not found');
            }
            return $data[0];

        }

        public static function restore( $backup_id ){

            $backup_data = self::get_backup_by_id( $backup_id );

            $backup_dir = SNS_BACKUPS_PATH.$backup_data->filename;
            $backup_file =  $backup_dir.'.zip';
            if( !is_file( $backup_file ) ){
                throw new Sns_Exception_Not_Found( 'File not found '.$backup_file );
            }
            self::restore_from_file( $backup_dir , $backup_file );
            Sns_Backup::save_restore( $backup_id );

        }

        public static function restore_from_file( $backup_dir , $backup_file ){

            if( mkdir( $backup_dir ) === false ){
                throw new Sns_Exception_Unavailable_Operation( 'Cannot create the directory '.$backup_dir );
            }
            self::restore_item( $backup_file , $backup_dir );
            $options = Sns_Option::get_locations();

            foreach( $options as $option => $to ){
                $item = ( $option == Sns_Option::DB )?($backup_dir.SNS_DS.'wp_dump.sql'):($backup_dir.SNS_DS.$option.'.zip');
                if( file_exists( $item ) ){
                    if( $option == Sns_Option::DB ){
                        Sns_Log::log_action('Restoring database');
                        try{
                            Sns_Backup::import_db( $item , true );
                        } catch( Exception $e ) {
                            Sns_Log::log_exception_obj( $e );
                        }
                        Sns_Log::log_action('Restoring database' , SNS_LOG_END);
                    }else{
                        Sns_Log::log_action('Restoring item '.$item);
                        try{
                            self::restore_item( $item , $to );
                        } catch( Exception $e ){
                            Sns_Log::log_exception_obj( $e );
                        }
                        Sns_Log::log_action('Restoring item '.$item , SNS_LOG_END);
                    }
                }
            }
            self::delete_dir( $backup_dir );

        }

        public static function restore_item( $item_path , $restore_path ){

            if (!class_exists('PclZip', false)) {
                require_once SNS_LIB_PATH.'pclzip.lib.php';
            }
            $pcl = new PclZip($item_path);
            if( is_dir( $restore_path ) ){
                self::delete_dir( $restore_path , array(
                    realpath( SNS_BACKUP_ROOT ) ,
                    realpath( WP_CONTENT_DIR.SNS_DS.'debug.log' ),
                    realpath( SNS_BACKUPS_PATH )
                ) );
            }
            if( !is_dir( $restore_path ) ){
                if( mkdir( $restore_path ) === false ){
                    throw new Sns_Exception_Unavailable_Operation( 'Cannot create the directory '.$restore_path );
                }
            }
            if ($files = $pcl->extract(PCLZIP_OPT_PATH, $restore_path, PCLZIP_OPT_REPLACE_NEWER) === 0) {
                throw new Sns_Exception_Unavailable_Operation('Cannot extract the archive.');
            }

        }

        public static function delete( $backup_id ){

            global $wpdb;
            $backup_id = intval( $backup_id );
            $table = SNS_DB_PREFIX.'backups';
            $backup_data = self::get_backup_by_id( $backup_id );
            $query = " DELETE FROM `{$table}`
                       WHERE `id` = {$backup_id}
                      ";
            $response = $wpdb->query($query);
            if( $response === false ){
                throw new Sns_Exception_DB_Error();
            }
            $backupFile = SNS_BACKUPS_PATH.$backup_data->filename.'.zip';
            if( file_exists( $backupFile ) && !unlink( $backupFile ) ){
                Sns_Log::log_msg( 'Cannot delete the file '.$backupFile );
            }

        }

        public static function delete_by_hash( $hash , $filename ){

            global $wpdb;
            $table = SNS_DB_PREFIX.'backups';
            $query = " DELETE FROM `{$table}`
                       WHERE `hash` = '{$hash}'
                      ";
            $response = $wpdb->query($query);
            if( $response === false ){
                throw new Sns_Exception_DB_Error();
            }
            $backupFile = SNS_BACKUPS_PATH.$filename.'.zip';
            if( is_file( $backupFile ) && !unlink( $backupFile ) ){
                throw new Sns_Exception_Unavailable_Operation( 'Cannot delete the file '.$backupFile );
            }
            $backupDir = SNS_BACKUPS_PATH.$filename;
            if( is_dir( $backupDir ) ){
                Sns_History::delete_dir( $backupDir );
            }

        }

        public static function delete_dir($dirPath , $exclude = array()) {

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            if( !empty( $exclude ) ){
                $files = new Sns_Callback_Filter_Iterator($files , function ($file) use ($exclude) {
                    foreach( $exclude as $excludeFile ){
                        if( strpos( $file, $excludeFile ) !== false ){
                            return false;
                        }
                    }
                    return true;
                });
            }

            foreach ( $files as $fileinfo ) {
                if( $fileinfo->isDir() ){
                    if( self::is_dir_empty( $fileinfo ) ){
                        if( rmdir($fileinfo->getRealPath()) === false ){
                            throw new Sns_Exception_Unavailable_Operation( 'Cannot delete the directory '.$fileinfo->getRealPath() );
                        }
                    }
                }else{
                    if( unlink($fileinfo->getRealPath()) === false ){
                        throw new Sns_Exception_Unavailable_Operation( 'Cannot delete the file '.$fileinfo->getRealPath() );
                    }
                }
            }
            if( self::is_dir_empty( $dirPath ) ){
                if( rmdir($dirPath) === false ){
                    throw new Sns_Exception_Unavailable_Operation( 'Cannot delete the directory '.$dirPath );
                }
            }

        }

        public static function is_dir_empty( $dir ) {

            if ( !is_readable( $dir ) ) return null;
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    return false;
                }
            }
            return true;

        }
		public static function saveExternalZip() {
			$externalFolders = scandir( WP_CONTENT_DIR.SNS_DS.SNS_BACKUP_ROOT_FOLDER_NAME);
			$get_new_hash = Sns_Backup::get_new_hash();
			foreach ($externalFolders as $externalFolder ) {
				  if(substr($externalFolder, 0, 4) == 'sns_') {
						global $wpdb;
						$externalFolder = str_replace('.zip', '',$externalFolder);
						$table = SNS_DB_PREFIX.'backups';
						$query = $wpdb->prepare( "SELECT  id from {$table} WHERE filename = %s",$externalFolder);
						$fileNames = $wpdb->get_row($query);
						if(!$fileNames) {
							$sql = $wpdb->prepare( "INSERT INTO ". SNS_DB_PREFIX. "backups (type, info, backup_date, restore_date, hash, filename) VALUES (%s,%s,%s,%s,%s,%s)",'manual','{"options":[],"destinations":[]}', date('Y-m-d H:i:s'),'0000-00-00 00:00:00',$get_new_hash,$externalFolder);    
							$res = $wpdb->query($sql);
						}
					}
			}
		}
        public static function draw( $only_records = false ){
			self::saveExternalZip(); 
            $history = self::get_history();
            $state = Sns_State::get_status();
            $disabled = ( $only_records && ( $state['status'] == Sns_State::STATUS_ACTIVE ) )?' disabled="disabled" ':'';
            if( !$only_records ){
?>
            <span class="menu-title">Your Backup History</span>
            <div class="menu-content">
                <div class="external-restore">
                    <input type="text" placeholder="Browse a backup file to restore from." class="form-control external-backup-input">
                    <div id="external-container">
                        <button id="external-browse" type="button" class="btn btn-default sns-action">Browse</button>
                        <button id="external-restore" type="button" class="btn btn-primary sns-action">Restore</button>
                    </div>
                </div>

                <div class="separator"></div>
                <div id="progressbar-restore"><div class="progress-label"></div></div>
<?php       } ?>
            <div class="records">
                <table class="table">
                    <?php
                        if( empty( $history ) ){
                            echo 'Your history is empty';
                        }else{
                    ?>
                            <thead>
                                <th class="h-date">Backup date</th>
                                <th>Information</th>
                                <th class="h-actions">Actions</th>
                            </thead>
                            <tbody>
                    <?php
                            foreach( $history as $item ){
                    ?>
                             <tr>
                                 <td><?php echo date('M d, Y H:i' , strtotime($item->backup_date)); ?></td>
                                 <td>
                                     <?php
                                        $info = json_decode( $item->info , true );
                                        echo '<b>options:</b> '.implode(', ' , $info['options']).'<br/>';
                                        echo '<b>destinations:</b> local'.(empty($info['destinations'])?'':',').implode(', ' , $info['destinations']).'<br/>';
                                     ?>
                                 </td>
                                 <td>
                                     <button type="button" class="btn btn-primary btn-restore sns-action" <?php echo $disabled; ?> data-backup_id="<?php echo $item->id; ?>">Restore</button>
                                     <a href="<?php echo SNS_BACKUPS_URL.$item->filename.'.zip'; ?>"><button type="button" class="btn btn-default btn-download sns-action" <?php echo $disabled; ?> data-backup_id="<?php echo $item->id; ?>">Download</button></a>
                                     <button type="button" class="btn btn-danger btn-delete sns-action" <?php echo $disabled; ?> data-backup_id="<?php echo $item->id; ?>"><span class="glyphicon glyphicon-remove"></span></button>
                                 </td>
                             </tr>
                    <?php
                            }
                    ?>
                            </tbody>
                    <?php
                        }
                    ?>
                </table>
            </div>
            <?php if( !$only_records ){ ?>
            </div>
        <?php
            }
        }

    }