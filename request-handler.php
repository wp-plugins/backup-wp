<?php

    require_once( dirname(__FILE__).'/sns-config.php' );
    require_once( SNS_CLASSES_PATH.'class-helper.php' );
    require_once( SNS_CLASSES_PATH.'class-option.php' );
    require_once( SNS_CLASSES_PATH.'class-manual.php' );
    require_once( SNS_CLASSES_PATH.'class-history.php' );

    function sns_send_response( $response ){

        $result = new stdClass();
        if( $response !== false ){
            $result->status = 'OK';
        }else{
            $result->status = 'INVALID';
        }
        echo json_encode( $result );
        die();

    }

    function sns_backup_update_history() {

        History::draw( true );
        die();

    }

    function sns_backup_manual_backup() {

        sns_send_response( Manual::backup() );

    }

    function sns_backup_backup_delete() {

        sns_send_response( History::delete( $_GET['id'] ) );

    }

    function sns_backup_backup_restore(){

        sns_send_response( History::restore( $_GET['id'] ) );

    }

    function sns_backup_external_restore() {

        $response = false;
        if( !empty( $_FILES ) &&  $_FILES['backup_file']['type'] == 'application/x-tar' ){
            $backup_file = $_FILES['backup_file'];
            $extension = substr( basename($backup_file['name']) , -4 );
            if( $extension == '.tar' ){
                $file_dir = dirname(__FILE__).'/sns_backup-external.tar';
                if( move_uploaded_file( $backup_file['tmp_name'] , $file_dir ) ){
                    $backup_dir = substr( $file_dir , 0 , strlen($file_dir)-4 );
                    if( History::restore_from_file( $backup_dir , $file_dir ) ){
                        unlink( $file_dir );
                        $response = true;
                    }
                }
            }
        }
        sns_send_response( $response );

    }

?>