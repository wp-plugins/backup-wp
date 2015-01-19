<?php
    class Sns_Log {

        private static $log_file_handle  = 	null;
        private static $log_file        = 	null;

        public static function log_action( $action = '' , $position = SNS_LOG_START ) {
            if( $position == SNS_LOG_END ){
                $position = 'END';
            }else{
                $position = 'START';
            }
            $log_str = '['.date('Y-m-d H:i:s').'] ['.$position.' '.$action.']'.PHP_EOL;
            self::log( $log_str );
        }

        public static function log_exception( $exception , $message, $file, $line ) {
            $log_str  = '['.date( 'Y-m-d H:i:s' ).'] ';
            $log_str .= '[EXCEPTION '.$exception.'] ';
            $log_str .= '['.$message.'] ';
            $log_str .= '[ File '.$file.' , Line '.$line.' ] ';
            $log_str .= '[ URL '.( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ).' ] ';
            $log_str .= PHP_EOL;
            self::log( $log_str );
        }

        public static function log_exception_obj( $ex ){
            self::log_exception( get_class( $ex ) , $ex->getMessage() , $ex->getFile() , $ex->getLine()  );
        }

        public static function log_msg( $msg ){
            self::log( $msg );
        }

        public static function print_log(){
            echo self::get_log();
        }

        public static function get_log(){
            self::$log_file = SNS_LOG_FILE;
            $content = file_get_contents( self::$log_file );
            if( $content === false ){
                throw new Sns_Exception_Unavailable_Operation('Cannot read the log file.');
            }
            return str_replace(PHP_EOL, (PHP_EOL.PHP_EOL), $content);
        }

        public static function empty_log(){
            self::$log_file = SNS_LOG_FILE;
            $content = file_put_contents( self::$log_file , '' );
            if( $content === false ){
                throw new Sns_Exception_Unavailable_Operation('Cannot write in the log file.');
            }
        }

        private static function log( $log_str ){
            self::$log_file = SNS_LOG_FILE;
            if( self::$log_file_handle == null ) {
                self::$log_file_handle = fopen(  self::$log_file , 'a+' );
            }
            fwrite( self::$log_file_handle , $log_str );
        }

    }
