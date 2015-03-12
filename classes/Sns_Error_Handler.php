<?php
    class Sns_Error_Handler {

        private static $log_max_size 	   = 	1;    // Mb
        private static $log_file_handle  = 	null;
        private static $log_file        = 	null;

        private static $errors = array (

            E_ERROR 				=>		'FATAL',
            E_WARNING	 			=> 		'WARNING',
            E_NOTICE	 			=> 		'NOTICE',
            E_USER_ERROR			=> 		'U_FATAL',
            E_USER_WARNING	 		=> 		'U_WARNING',
            E_USER_NOTICE	 		=> 		'U_NOTICE',
            E_STRICT				=> 		'STRICT',
            E_RECOVERABLE_ERROR		=> 		'REC_ERR'

        );

        public static function fatal_error_handler() {

            $last_error = error_get_last();
            if ( $last_error['type']  == E_ERROR  ){
                Sns_Error_Handler::app_error_handler( E_ERROR, $last_error['message'], $last_error['file'], $last_error['line'] );
            }
            if( self::$log_file_handle != null ){

                $file_size =  filesize ( self::$log_file  );
                $file_size =  $file_size/1024/1024;
                $log_content = '';
                if( $file_size > self::$log_max_size ) {
                    $log_content = self::get_file_contents( self::$log_file ,   $file_size*1024*1024  - self::$log_max_size*1024*1024 ,  self::$log_max_size*1024*1024  );
                }
                fclose( self::$log_file_handle );
                if(  $log_content != ''  ){
                    file_put_contents( self::$log_file  ,  $log_content );
                }
            }
        }

        public static function get_file_contents( $file ,   $offset = -1,   $max_length = -1 ) {
            $handle = fopen( $file, "r" );
            if( $offset != -1 ){
                fseek( $handle , $offset );
                if( $max_length == -1 ){
                    $max_length = filesize( $file ) - $offset;
                }
                $contents = fread( $handle, $max_length );
            }else{
                $max_length = filesize( $file );
                $contents  = fread( $handle, $max_length );
            }
            return $contents;
        }

        private static function error_name_by_code( $code ) {
            if( isset( self::$errors[ $code ] ) ){
                return self::$errors[ $code ];
            }
            return $code;
        }

        public static function app_error_handler(  $error_code, $message, $error_file, $error_line ) {
            if( strpos($error_file, SNS_BACKUP_ROOT) === false ){
                return;
            }
            $error_str  = '['.date( 'Y-m-d H:i:s' ).'] ';
            $error_str .= '['.self::error_name_by_code( $error_code ).'] ';
            $error_str .= '['.$message.'] ';
            $error_str .= '[ File '.$error_file.' , Line '.$error_line.' ] ';
            $error_str .= '[ URL '.( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ).' ] ';
            $error_str .= PHP_EOL;
            self::log_error( $error_str );
        }

        private static function log_error( $line ) {

            self::$log_file = SNS_ERR_LOG_FILE;
            if( self::$log_file_handle == null ) {
                self::$log_file_handle = fopen(  self::$log_file , 'a+' );
            }
            @fwrite( self::$log_file_handle , $line );

        }


        public static function init () {
            register_shutdown_function( 'Sns_Error_Handler::fatal_error_handler' );
            set_error_handler( 'Sns_Error_Handler::app_error_handler' );
        }
    }
