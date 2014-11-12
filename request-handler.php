<?php

    require_once( dirname(__FILE__).'/sns-config.php' );
    require_once( SNS_CLASSES_PATH.'class-helper.php' );
    require_once( SNS_CLASSES_PATH.'class-option.php' );
    require_once( SNS_CLASSES_PATH.'class-settings.php' );
    require_once( SNS_CLASSES_PATH.'class-schedule.php' );
    require_once( SNS_CLASSES_PATH.'class-manual.php' );
    require_once( SNS_CLASSES_PATH.'class-history.php' );

    function sns_send_response( $response , $alt = null ){

        $result = new stdClass();
        if( $response !== false ){
            $result->status = 'OK';
        }else{
            $result->status = 'INVALID';
        }
        if( !is_null( $alt ) ){
            $result->data = $alt;
        }
        echo json_encode( $result );
        die();

    }

    function sns_backup_update_history() {

        History::draw( true );
        die();

    }

    function sns_backup_manual_backup() {

        $locations = ( isset( $_POST['locations'] )?$_POST['locations']:array()) ;
        sns_send_response( Manual::backup( $locations ) );

    }

    function sns_backup_backup_delete() {

        sns_send_response( History::delete( $_GET['id'] ) );

    }

    function sns_backup_backup_restore(){

        $response = History::restore( $_GET['id'] );
        History::send_restore_email( $response );
        sns_send_response( $response );

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
        History::send_restore_email( $response );
        sns_send_response( $response );

    }

    function sns_backup_save_options() {

        $alt = null;
        $new_options = ( isset( $_POST['options'] ) )?$_POST['options']:array();
        if( empty( $new_options ) || (count( $new_options ) == 1 ) && isset($new_options[Option::COUNT])  ){
            $response = false;
            $alt = __('Choose items to backup' , 'sns-backup');
        }else{
            $response = Option::save( $new_options );
            if( $response ){
                $settings = isset( $_POST['settings'] )?$_POST['settings']:array();
                $type = Settings::SETTINGS_EMAIL;
                if( isset( $settings[$type]['email'] ) ){
                    if( $settings[$type]['email'] != '' ){
                        $response = Settings::validate( $settings );
                    }
                    if( $response ){
                        $response = Settings::save( $type , $settings[$type] );
                    }else{
                        $alt = __('Enter valid email.', 'sns-backup');
                    }
                }
            }
        }
        sns_send_response( $response , $alt );

    }

    function sns_backup_save_schedule() {

        $new_config = $_POST['config'];
        $response = Schedule::save( $new_config );
        $locations = $_POST['locations'];
        Settings::save_locations( $locations , 'schedule' );

        sns_send_response( $response );

    }

    function sns_backup_save_settings() {

        $alt = null;
        $settings = $_POST['settings'];
        $type = $_POST['type'];
        if( !isset( $settings[Settings::SETTINGS_EMAIL] ) || $settings[Settings::SETTINGS_EMAIL]['email'] == '' ){
            $response = false;
        }else{
            $response = Settings::validate( $settings );
            if( $response ){
                $response = Settings::save( $type , $settings[$type] );
            }else{
                $alt = __('Enter valid email.', 'sns-backup');
            }
        }
        sns_send_response( $response , $alt );

    }

    function sns_backup_get_email() {

        echo Settings::get_email();
        die();

    }

?>