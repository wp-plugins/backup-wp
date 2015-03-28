<?php
class Sns_Log {

    private static $log_file = SNS_LOG_FILE;

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
        $content = @file_get_contents( self::$log_file );
        if( $content === false ){
            throw new Sns_Exception_Unavailable_Operation('Cannot read the log file.');
        }
        return $content;
    }

    public static function empty_log(){
        $content = @file_put_contents( self::$log_file , '' );
        if( $content === false ){
            throw new Sns_Exception_Unavailable_Operation('Cannot write in the log file.');
        }
    }

    private static function log( $log_str ){
        @file_put_contents( self::$log_file , $log_str , FILE_APPEND );
    }

    public static function report($action){
        if(get_option('sns_backup_report_log') == false || get_option('sns_backup_report_log') == 0){
            return;
        }
        $log = '';
        $log_file = file_get_contents(self::$log_file);
        $last_process_start = strrpos($log_file, '########PROCESS STARTED########');
        if($last_process_start !== false){
            $last_process_end = strrpos($log_file, '########PROCESS ENDED########');
            if($last_process_end === false){
                $length = null;
            }else{
                $length = $last_process_end - $last_process_start;
            }
            $log = substr( $log_file, $last_process_start, $length );
        }

        $data = array(
            'ptype' => 1,
            'log' => array(
                'action' => $action,
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version'),
                'server_software' => $_SERVER['SERVER_SOFTWARE'],
                'site_url' => get_site_url(),
                'site_name' => get_bloginfo('name'),
                'log' => base64_encode($log),
                'reported_at' => date('Y-m-d H:i:s'),
            ),
            'token' => '28016ffb35b451291bfed7c5905474d6'
        );

        self::sns_do_post_request(SNS_API_URL, json_encode($data));
    }

    private static function sns_do_post_request($url, $data, $optional_headers = null)
    {
        $params = array('http' => array(
            'method' => 'POST',
            'content' => $data
        ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }else{
            $params['http']['header'] = "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($data) . "\r\n";
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            return false;
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            return false;
        }
        return $response;
    }

}
