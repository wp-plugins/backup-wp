<?php
class Sns_Exception_Handler {

    public static function init () {
        set_exception_handler( 'Sns_Exception_Handler::exception_handler' );
    }

    public static function get_exception_data( $ex ){

        $data = array(
            'msg' => $ex->getMessage()
        );
        if (  $ex  instanceof Sns_Exception_Not_Found  ) {
            $data['status'] = 'NOT_FOUND';
            return $data;
        }

        if ( $ex  instanceof Sns_Exception_Permission_Denied ) {
            $data['status'] = 'FORBIDDEN';
            return $data;
        }

        if( $ex instanceof Sns_Exception_Invalid_Data ) {
            $data['status'] = 'BAD_REQUEST';
            return $data;
        }

        if( ( $ex instanceof Sns_Exception_Unavailable_Operation ) ) {
            $data['status'] = 'UNAVAILABLE_OPERATION';
            return $data;
        }

        if( ( $ex instanceof Sns_Exception_DB_Error ) ) {
            $data['status'] = 'DB_ERROR';
            return $data;
        }

        $data['status'] = 'SYSTEM_ERROR';
        return $data;

    }

    private static function not_found_page( $msg ){
        if (  self::xml_http_request() ) {
            header('HTTP/1.1 404 Not Found');
            $result = new stdClass();
            $result->status = 'NOT_FOUND';
            $result->error_msg = $msg;
            sns_send_response( $result );
        }
        else {
            Sns_Log::log_msg($msg);
        }
    }

    private static function page_forbidden( $msg ){

        if (   self::xml_http_request() ) {
            header('HTTP/1.1 403 Forbidden');
            $result = new stdClass();
            $result->status = 'FORBIDDEN';
            $result->error_msg = $msg;
            sns_send_response( $result );
        }
        else {
            Sns_Log::log_msg($msg);
        }

    }

    private static function bad_request( $msg ){

        if (   self::xml_http_request() ) {
            header('HTTP/1.1 400 Bad Request');
            $result = new stdClass();
            $result->status = 'BAD_REQUEST';
            $result->error_msg = $msg;
            sns_send_response( $result );
        }
        else {
            Sns_Log::log_msg($msg);
        }

    }

    private static function unavailable_operation( $msg ){

        if (   self::xml_http_request() ) {
            header('HTTP/1.1 405 Method Not Allowed');
            $result = new stdClass();
            $result->status = 'UNAVAILABLE_OPERATION';
            $result->error_msg = $msg;
            sns_send_response( $result );
        }
        else {
            Sns_Log::log_msg($msg);
        }

    }

    private static function server_error(){

        header('HTTP/1.1 500 Internal Server Error');
        if (  self::xml_http_request() ) {
            $result = new stdClass();
            $result->status = 'SERVER_ERROR';
            sns_send_response( $result );
        }
        else {
            Sns_Log::log_msg('Server error');
        }

    }

    private static function db_error(){

        header('HTTP/1.1 500 Internal Server Error');
        if (  self::xml_http_request() ) {
            $result = new stdClass();
            $result->status = 'DB_ERROR';
            sns_send_response( $result );
        }
        else {
            Sns_Log::log_msg('DB error');
        }

    }

    public static function log( $ex ){
        Sns_Log::log_exception( get_class( $ex ) , $ex->getMessage() , $ex->getFile() , $ex->getLine()  );
    }

    public static function exception_handler( $ex ){

        self::log( $ex );

        if (  $ex  instanceof Sns_Exception_Not_Found  ) {
            self::not_found_page( $ex->getMessage() );
            return true;
        }

        if ( $ex  instanceof Sns_Exception_Permission_Denied ) {
            self::page_forbidden( $ex->getMessage() );
            return true;
        }

        if( $ex instanceof Sns_Exception_Invalid_Data ) {
            self::bad_request( $ex->getMessage() );
            return true;
        }

        if( ( $ex instanceof Sns_Exception_Unavailable_Operation ) ) {
            self::unavailable_operation( $ex->getMessage() );
            return true;
        }

        if( ( $ex instanceof Sns_Exception_DB_Error ) ) {
            self::db_error();
            return true;
        }

        self::server_error();
        return true;
    }

    private static function xml_http_request(){
        if ( defined('DOING_AJAX') && DOING_AJAX ) {
            return true;
        }
        return false;
    }

}
