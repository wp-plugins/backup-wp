<?php

    require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'sns-config.php' );

    Sns_Error_Handler::init();
    Sns_Exception_Handler::init();

    function sns_send_response( $result ){

        echo  json_encode( $result );
        die();

    }

    function sns_backup_update_history() {

        Sns_History::draw( true );
        die();

    }

    function sns_backup_manual_backup() {

        $state = Sns_State::get_status();
        if( $state['status'] == Sns_State::STATUS_ACTIVE ){
            throw new Sns_Exception_Unavailable_Operation('There is an existing active process.Please wait.');
        }

        $stateData = array(
            'status' => Sns_State::STATUS_ACTIVE,
            'type' => Sns_State::TYPE_BACKUP,
            'start_date' => date('Y-m-d H:i:s')
        );
        Sns_State::update( $stateData );
        try{
            sleep(4);
            $locations = ( isset( $_POST['locations'] )?$_POST['locations']:array());
            $destination = new Sns_Destination( Sns_Backup::BACKUP_MODE_MANUAL );
            Sns_Log::log_action('Saving destinations');
            $destination->set_destinations( $locations );
            $destination->save();
            Sns_Log::log_action('Saving destinations' , SNS_LOG_END);
            $backup = new Sns_Backup( Sns_Backup::BACKUP_MODE_MANUAL );
            Sns_Log::log_action('Backing up');
            $backup->backup();
            Sns_Log::log_action('Backing up', SNS_LOG_END);
            $stateData = array(
                'status' => Sns_State::STATUS_FINISHED,
                'type' => Sns_State::TYPE_BACKUP
            );
            Sns_State::update( $stateData );
        }catch( Exception $e ){
            $ex_data = Sns_Exception_Handler::get_exception_data( $e );
            $stateData = array(
                'status'    => Sns_State::STATUS_FAILED,
                'type'      => Sns_State::TYPE_BACKUP,
                'msg'       => $ex_data['status'].' : '.$ex_data['msg']
            );
            Sns_State::update( $stateData );
        }
        die();

    }

    function sns_backup_backup_delete() {

        $state = Sns_State::get_status();
        if( ( $state['status'] == Sns_State::STATUS_ACTIVE ) ){
            throw new Sns_Exception_Unavailable_Operation('There is an existing active process.Please wait.');
        }
        Sns_Log::log_action( 'Deleting backup' );
        Sns_History::delete( $_GET['id'] );
        Sns_Log::log_action( 'Deleting backup' , SNS_LOG_END );
        $result = new stdClass();
        $result->status = 'OK';
        sns_send_response( $result );

    }

    function sns_backup_backup_restore(){

        $state = Sns_State::get_status();
        if( ( $state['status'] == Sns_State::STATUS_ACTIVE ) ){
            throw new Sns_Exception_Unavailable_Operation('There is an existing active process.Please wait.');
        }

        $stateData = array(
            'status' => Sns_State::STATUS_ACTIVE,
            'type' => Sns_State::TYPE_RESTORE,
            'start_date' => date('Y-m-d H:i:s')
        );
        Sns_State::update( $stateData );
        try{
            sleep(4);
            Sns_Log::log_action( 'Restoring' );
            try{
                Sns_History::restore( $_GET['id'] );
            }catch( Exception $e ){
                Sns_Log::log_msg('[FAILED Restore]');
                throw $e;
            }
            Sns_Log::log_msg('[SUCCEED Restore]'.PHP_EOL);
            Sns_Log::log_action( 'Restoring' , SNS_LOG_END );

            $stateData = array(
                'status' => Sns_State::STATUS_FINISHED,
                'type' => Sns_State::TYPE_RESTORE
            );
            Sns_State::update( $stateData );
        }catch( Exception $e ){
            $ex_data = Sns_Exception_Handler::get_exception_data( $e );
            $stateData = array(
                'status'    => Sns_State::STATUS_FAILED,
                'type'      => Sns_State::TYPE_RESTORE,
                'msg'       => $ex_data['status'].' : '.$ex_data['msg']
            );
            Sns_State::update( $stateData );
        }
        die();

    }

    function sns_backup_external_restore() {

        $state = Sns_State::get_status();
        if( ( $state['status'] == Sns_State::STATUS_ACTIVE ) ){
            throw new Sns_Exception_Unavailable_Operation('There is an existing active process.Please wait.');
        }

        $stateData = array(
            'status' => Sns_State::STATUS_ACTIVE,
            'type' => Sns_State::TYPE_RESTORE,
            'start_date' => date('Y-m-d H:i:s')
        );
        Sns_State::update( $stateData );
        try{
            sleep(4);
            try{
                if( !empty( $_FILES ) && isset( $_FILES['backup_file']) && $_FILES['backup_file']['type'] == 'application/x-tar' ){
                    $backup_file = $_FILES['backup_file'];
                    $extension = substr( basename($backup_file['name']) , -4 );
                    if( $extension == '.tar' ){
                        $file_dir = dirname(__FILE__).SNS_DS.'sns_backup-external.tar';
                        if( move_uploaded_file( $backup_file['tmp_name'] , $file_dir ) ){
                            $backup_dir = substr( $file_dir , 0 , strlen($file_dir)-4 );
                            Sns_Log::log_action('Restoring from external file');
                            Sns_History::restore_from_file( $backup_dir , $file_dir );
                            Sns_Log::log_action('Restoring from external file' , SNS_LOG_END);
                            if( unlink( $file_dir ) === false ){
                                throw new Sns_Exception_Unavailable_Operation( 'Cannot delete the file '.$file_dir );
                            }
                        }else{
                            throw new Sns_Exception_Unavailable_Operation( 'Cannot move uploaded file' );
                        }
                    }
                }else{
                    throw new Sns_Exception_Not_Found( 'File not found' );
                }
                Sns_Log::log_msg('[SUCCEED Restore]'.PHP_EOL);
            }catch( Exception $e ){
                Sns_Log::log_msg('[FAILED Restore]'.PHP_EOL);
                throw $e;
            }
            $stateData = array(
                'status' => Sns_State::STATUS_FINISHED,
                'type' => Sns_State::TYPE_RESTORE
            );
            Sns_State::update( $stateData );
        }catch( Exception $e ){
            $ex_data = Sns_Exception_Handler::get_exception_data( $e );
            $stateData = array(
                'status'    => Sns_State::STATUS_FAILED,
                'type'      => Sns_State::TYPE_RESTORE,
                'msg'       => $ex_data['status'].' : '.$ex_data['msg']
            );
            Sns_State::update( $stateData );
        }
        die();

    }

    function sns_backup_save_ftp() {

        $ftp = new Sns_Ftp();
        $details = ( isset( $_POST['ftp'] ) )?$_POST['ftp']:array();
        $ftp->setServer( trim( strval( $details['server'] ) ) );
        $ftp->setUsername( trim( strval( $details['username'] ) ) );
        $ftp->setPassword( trim( strval( $details['password'] ) ) );
        $port = (trim( strval( $details['port'] ) ) == '')?SNS_FTP_DEF_PORT:trim( strval( $details['port'] ) );
        $ftp->setPort( $port );

        $ftp->test();
        $ftp->save();

        $result = new stdClass();
        $result->status = 'OK';
        sns_send_response( $result );

    }

    function sns_backup_check_ftp(){
        $ftp = new Sns_Ftp();
        $ftp->fill_data();
        $ftp->test();

        $result = new stdClass();
        $result->status = 'OK';
        sns_send_response( $result );
    }

    function sns_backup_unlink_ftp(){
        $ftp = new Sns_Ftp();
        $ftp->unlink();
        $result = new stdClass();
        $result->status = 'OK';
        sns_send_response( $result );
    }

    function sns_backup_log_refresh(){
        $result = new stdClass();
        $result->status = 'OK';
        $result->data = Sns_Log::get_log();
        sns_send_response( $result );
    }

    function sns_backup_log_empty(){
        $result = new stdClass();
        $result->status = 'OK';
        Sns_Log::empty_log();
        sns_send_response( $result );
    }

    function sns_backup_state_get_status(){
        $result = new stdClass();
        $result->status = 'OK';
        $data = Sns_State::get_status();
        if( $data['status'] == Sns_State::STATUS_ACTIVE ){
            $temp = (time() - strtotime( $data['start_date'] ))*1000*(SNS_PROCESS_STEP_COUNT/SNS_PROCESS_DURATION);
            $progress = round( $temp * ( 100 / SNS_PROCESS_STEP_COUNT ) , 1 );
            $data['progress'] = ( $progress >= 99 ) ? 99 : $progress;
            $data['progress_view'] = $data['progress'].'%';
        }
        $result->data = $data;
        $result->response_time = time();
        sns_send_response( $result );
    }

    function sns_backup_state_reset_status(){
        $stateData = array(
            'status' => Sns_State::STATUS_NONE
        );
        Sns_State::update( $stateData );
        die();
    }
