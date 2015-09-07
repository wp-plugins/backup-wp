<?php
    class Sns_Checker {

        public static function check($onlyExtensions = false){
            if (!extension_loaded('mbstring')) {
				throw new Sns_Exception_Not_Found('PHP mbstring extension is not loaded.');
			}

			if ($onlyExtensions) return;

            if( !is_dir( SNS_BACKUPS_PATH ) ){
                if( !mkdir( SNS_BACKUPS_PATH ) ){
                    throw new Sns_Exception_Unavailable_Operation('Cannot create folder '.SNS_BACKUPS_PATH);
                }
            }
            if( !is_writable( SNS_BACKUPS_PATH ) ){
                throw new Sns_Exception_Permission_Denied('Permission denied.Directory is not writable '.SNS_BACKUPS_PATH);
            }
            if(!class_exists('ZipArchive')){
                throw new Sns_Exception_Not_Found('Zip extension missing on your server.');
            }
        }

        public static function checkFTP(){
            if( !extension_loaded('ftp') ){
                throw new Sns_Exception_Not_Found('PHP ftp extension is not loaded.');
            }
        }

        public static function initialCheck(){
            if ( version_compare(PHP_VERSION, '5.3.0', '<') ) {
                wp_die('PHP >=5.3.0 version required.');
            }

            try{
            	self::check(true);
            }catch(Exception $e){
            	wp_die($e->getMessage());
            }
        }
        
    }
?>