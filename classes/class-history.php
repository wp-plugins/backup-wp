<?php
    require_once( dirname(__FILE__).'/../sns-config.php');

    class History {

        public static function get_history(){

            global $wpdb;
            $table = SNS_DB_PREFIX.'backups';
            $history = $wpdb->get_results( "SELECT
                                                `id`,
                                                `type`,
                                                `info`,
                                                `backup_date`,
                                                `hash`
                                            FROM {$table}
                                            ORDER BY `backup_date` DESC"
                                          , OBJECT_K );
            return $history;

        }

        public static function get_backup_by_id( $id ){

            global $wpdb;
            $id = intval( $id );
            $table = SNS_DB_PREFIX.'backups';
            $data =  $wpdb->get_results( "SELECT
                                          `id`,
                                          `hash`
                                        FROM {$table}
                                        WHERE `id` = {$id}
                                        LIMIT 1"
            );
            return $data[0];

        }

        public static function restore( $backup_id ){

            $backup_data = self::get_backup_by_id( $backup_id );
            if( empty( $backup_data ) ){
                return false;
            }else{
                $backup_dir = SNS_BACKUPS_PATH.'sns_backup-'.$backup_data->hash;
                $backup_file =  $backup_dir.'.tar';
                if( !is_file( $backup_file ) ){
                    return false;
                }else{
                    if( self::restore_from_file( $backup_dir , $backup_file )){
                        Sns_Backup::save_restore( $backup_id );
                        return true;
                    }
                    return false;
                }
            }

        }

        public static function restore_from_file( $backup_dir , $backup_file ){

            if( !mkdir( $backup_dir ) ){
                return false;
            }
            self::restore_item( $backup_file , $backup_dir );
            $options = Option::get_locations();
            foreach( $options as $option => $to ){
                $item = ( $option == Option::DB )?($backup_dir.'/wp_dump.sql'):($backup_dir.'/'.$option.'.tar');
                if( is_file( $item ) ){
                    if( $option == Option::DB ){
                        if( !Sns_Backup::import_db( $item , true ) ){
                            return false;
                        }
                    }else{
                        self::restore_item( $item , realpath($to) );
                    }
                }
            }
            self::delete_dir( $backup_dir );
            return true;

        }

        public static function restore_item( $item_path , $restore_path ){

            $phar = new PharData( $item_path );
            try{
                if( is_dir( $restore_path ) ){
                    self::delete_dir( $restore_path , array(
                        realpath( SNS_BACKUP_ROOT ) ,
                        realpath( WP_CONTENT_DIR.'/debug.log' ),
                        realpath( SNS_BACKUPS_PATH )
                    ) );
                }
                if( !is_dir( $restore_path ) ){
                    if( !mkdir( $restore_path ) ){
                        die('cannot create dir-'.$restore_path);
                    }
                }
                $phar->extractTo( $restore_path , null , true );
            }catch(Exception $e){
                print_r($e);
            }

        }

        public static function delete( $backup_id ){

            global $wpdb;
            $backup_id = intval( $backup_id );
            $table = SNS_DB_PREFIX.'backups';
            $backup_data = self::get_backup_by_id( $backup_id );

            if( !empty( $backup_data ) ){
                $wpdb->query(
                    "  DELETE FROM {$table}
                       WHERE `id` = {$backup_id}
                    "
                );
                @unlink(SNS_BACKUPS_PATH.'sns_backup-'.$backup_data->hash.'.tar');
                return true;
            }
            return false;

        }

        public static function delete_dir($dirPath , $exclude = array()) {

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            if( !empty( $exclude ) ){
                $files = new CallbackFilterIterator($files , function ($file) use ($exclude) {
                    foreach( $exclude as $excludeFile ){
                        if( strpos( $file, $excludeFile ) !== false ){
                            return false;
                        }
                    }
                    return true;
                });
            }

            foreach ($files as $fileinfo) {
                if( $fileinfo->isDir() ){
                    if( self::is_dir_empty($fileinfo) ){
                        @rmdir($fileinfo->getRealPath());
                    }
                }else{
                    @unlink($fileinfo->getRealPath());
                }
            }
            if(self::is_dir_empty($dirPath)){
                @rmdir($dirPath);
            }

        }

        public static function is_dir_empty($dir) {

            if (!is_readable($dir)) return null;
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    return false;
                }
            }
            return true;

        }

        public static function send_restore_email( $succeed ){

            $userEmail = Settings::get_email();
            if( $succeed ){
                wp_mail($userEmail , __('Restore of your Wordpress site!', 'sns-backup') , __('Your Wordpress site was successfully restored!', 'sns-backup'));
            }else{
                wp_mail($userEmail , __('Restore of your Wordpress site!', 'sns-backup') , __('Your Wordpress site restoration failed!', 'sns-backup'));
            }

        }

        public static function draw( $only_records = false ){
            $history = self::get_history();
            if( !$only_records ){
?>
            <span class="menu-title"><?php _e( 'Your Backup History' ); ?></span>
            <div class="menu-content">
                <div class="external-restore">
                    <input type="text" placeholder="<?php _e('Browse a backup file to restore from.'); ?>" class="form-control external-backup-input">
                    <div id="external-container">
                        <button id="external-browse" type="button" class="btn btn-default"><?php _e( 'Browse' ); ?></button>
                        <button id="external-restore" type="button" class="btn btn-primary"><?php _e( 'Restore' ); ?></button>
                    </div>
                </div>
                <div class="separator"></div>
<?php       } ?>
            <div class="records">
                <table class="table">
                    <?php
                        if( empty( $history ) ){
                            _e( 'Your history is empty' );
                        }else{
                    ?>
                            <thead>
                                <th class="h-date"><?php _e( 'Backup date' ); ?></th>
                                <th><?php _e( 'Options' ); ?></th>
                                <th class="h-actions"><?php _e( 'Actions' ); ?></th>
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
                                        echo implode(', ' , $info['options']);
                                     ?>
                                 </td>
                                 <td>
                                     <button type="button" class="btn btn-primary btn-restore" data-backup_id="<?php echo $item->id; ?>"><?php _e( 'Restore' ); ?></button>
                                     <a href="<?php echo SNS_BACKUPS_URL.'sns_backup-'.$item->hash.'.tar'; ?>"><button type="button" class="btn btn-default btn-download" data-backup_id="<?php echo $item->id; ?>"><?php _e( 'Download' ); ?></button></a>
                                     <button type="button" class="btn btn-danger btn-delete" data-backup_id="<?php echo $item->id; ?>"><span class="glyphicon glyphicon-remove"></span></button>
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