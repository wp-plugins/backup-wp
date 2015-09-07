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

function sns_register_shutdown(){
	$proc = Sns_State::get_status();
	if( $proc['status'] == Sns_State::STATUS_ACTIVE ){
		var_dump(SNS_BACKUPS_PATH.Sns_State::$currentBackupFilename.'.zip');
		$data = array( 'status'    =>  Sns_State::STATUS_FAILED );
		Sns_State::update( $data );	
		$file = SNS_BACKUPS_PATH.Sns_State::$currentBackupFilename.'.zip';
		unlink( $file );
	}
}
function sns_backup_manual_backup() {
	@set_time_limit(0);
	register_shutdown_function('sns_register_shutdown');
	
    try{
        Sns_Log::log_msg('########PROCESS STARTED########'.PHP_EOL);
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

        Sns_Checker::check();
        $locations = ( isset( $_POST['locations'] )?$_POST['locations']:array());
        $destination = new Sns_Destination( Sns_Backup::BACKUP_MODE_MANUAL );
        $destination->set_destinations( $locations );
        $destination->save();
        $backup = new Sns_Backup( Sns_Backup::BACKUP_MODE_MANUAL );
		Sns_State::$currentBackupFilename = $backup->getFilename();
		
		$dest = 'local';
		if (count($locations)) $dest .= ','.implode(',',array_keys($locations));
           
		$options = Sns_Option::get_options( true );
		foreach( $options as $option => $data ){
			if( $option == Sns_Option::FULL ){
				$option_list = array(
					$option
				);
				break;
			}
			if( $option != Sns_Option::COUNT  ){
				$option_list []= $option;
			}
		}
		$options = implode(',', array_values($option_list));
		$engLogDecription  = '----------------------------------------------------';
		$engLogDecription .= "\n".ucfirst($stateData['type']).": ".$options;
		$engLogDecription .= "\nDestination: ".$dest."\n";
		$engLogDecription .= "----------------------------------------------------\n"; 
		
		Sns_Log::log_msg($engLogDecription);
        Sns_Log::log_action('Backing up');
        $warns = $backup->backup();
        Sns_Log::log_action('Backing up', SNS_LOG_END);
		Sns_Log::log_msg("\n");

        $skipped_files = '';
        if( !empty( $warns['not_readable'] ) ){
            $skipped_files .= '*********WARNING**********'.PHP_EOL;
            $skipped_files .= 'The following files are not readable and were excluded from backup package'.PHP_EOL;
            $i = 1;
            foreach( $warns['not_readable'] as $file ){
                $skipped_files .= $i.'. '.$file.PHP_EOL;
                $i++;
            }
            Sns_Log::log_msg( $skipped_files );
        }

        $stateData = array(
            'status' => Sns_State::STATUS_FINISHED,
            'type' => Sns_State::TYPE_BACKUP
        );
        Sns_State::update( $stateData );
        Sns_Log::log_msg('########PROCESS ENDED########'.PHP_EOL);
    }catch( Exception $e ){
        Sns_Log::log_exception_obj($e);
        $ex_data = Sns_Exception_Handler::get_exception_data( $e );
        $stateData = array(
            'status'    => Sns_State::STATUS_FAILED,
            'type'      => Sns_State::TYPE_BACKUP,
            'msg'       => $ex_data['status'].' : '.$ex_data['msg']
        );
        Sns_State::update( $stateData );
        Sns_Log::log_msg('########PROCESS ENDED########'.PHP_EOL);
        Sns_Log::report('backup');
    }
    die();

}

function sns_backup_backup_delete() {

    $state = Sns_State::get_status();
    if( ( $state['status'] == Sns_State::STATUS_ACTIVE ) ){
        throw new Sns_Exception_Unavailable_Operation('There is an existing active process.Please wait.');
    }
    Sns_History::delete( $_GET['id'] );
    $result = new stdClass();
    $result->status = 'OK';
    sns_send_response( $result );

}

function sns_backup_backup_restore($backupId){

    try{
        Sns_Log::log_msg('########PROCESS STARTED########'.PHP_EOL);
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
        Sns_Log::log_action( 'Restoring' );
        try{
            Sns_History::restore( $backupId );
        }catch( Exception $e ){
            Sns_Log::log_exception_obj( $e );
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
        Sns_Log::log_msg('########PROCESS ENDED########'.PHP_EOL);
    }catch( Exception $e ){
        Sns_Log::log_exception_obj($e);
        $ex_data = Sns_Exception_Handler::get_exception_data( $e );
        $stateData = array(
            'status'    => Sns_State::STATUS_FAILED,
            'type'      => Sns_State::TYPE_RESTORE,
            'msg'       => $ex_data['status'].' : '.$ex_data['msg']
        );
        Sns_State::update( $stateData );
        Sns_Log::log_msg('########PROCESS ENDED########'.PHP_EOL);
        Sns_Log::report('restore');
    }

    wp_redirect( admin_url( "admin.php?page=".$_GET['page'] ) );

}

function sns_backup_external_upload() {

    $state = Sns_State::get_status();
    if( $state['status'] == Sns_State::STATUS_ACTIVE ){
        throw new Sns_Exception_Unavailable_Operation('There is an existing active process.Please wait.');
    }
    $uname = date('m_d-H_i_s');
    $file_dir = dirname(__FILE__).SNS_DS.'sns_backup-external-'.$uname.'.zip';
    try{
        if( !empty( $_FILES ) && isset( $_FILES['backup_file']) && ($_FILES['backup_file']['type'] == 'application/zip' || $_FILES['backup_file']['type'] == 'application/octet-stream' ) ){
            $backup_file = $_FILES['backup_file'];
            $extension = substr( basename($backup_file['name']) , -4 );
            if( $extension == '.zip' ){
                if( !move_uploaded_file( $backup_file['tmp_name'] , $file_dir ) ){
                    throw new Sns_Exception_Unavailable_Operation( 'Cannot move uploaded file' );
                }else{
                    $result = new stdClass();
                    $result->status = 'OK';
                    $result->uname = $uname;
                    sns_send_response($result);
                }
            }
        }else{
            throw new Sns_Exception_Not_Found( 'File not found' );
        }
    }catch( Exception $e ){
        Sns_Log::log_exception_obj($e);
        throw $e;
    }
    die();
}

function sns_backup_external_restore($uname) {
    try{
        Sns_Log::log_msg('########PROCESS STARTED########'.PHP_EOL);
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
            $file_dir = dirname(__FILE__).SNS_DS.'sns_backup-external-'.$uname.'.zip';
            if( !file_exists($file_dir) ){
                throw new Sns_Exception_Not_Found( 'File not found' );
            }
            $backup_dir = substr( $file_dir , 0 , strlen($file_dir)-4 );
            Sns_Log::log_action('Restoring from external file');
            Sns_History::restore_from_file( $backup_dir , $file_dir );
            Sns_Log::log_action('Restoring from external file' , SNS_LOG_END);
            @unlink( $file_dir );
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
        Sns_Log::log_msg('########PROCESS ENDED########'.PHP_EOL);
    }catch( Exception $e ){
        Sns_Log::log_exception_obj($e);
        $ex_data = Sns_Exception_Handler::get_exception_data( $e );
        $stateData = array(
            'status'    => Sns_State::STATUS_FAILED,
            'type'      => Sns_State::TYPE_RESTORE,
            'msg'       => $ex_data['status'].' : '.$ex_data['msg']
        );
        Sns_State::update( $stateData );
        Sns_Log::log_msg('########PROCESS ENDED########'.PHP_EOL);
        Sns_Log::report('restore');

        wp_redirect( admin_url( "admin.php?page=".$_GET['page'] ) );
    }
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
function sns_backup_prepare_process(){
    $stateData = array(
        'status' => Sns_State::STATUS_READY_TO_START,
        'type' => (isset($_POST['type']) && $_POST['type'] == Sns_State::TYPE_RESTORE)?Sns_State::TYPE_RESTORE:Sns_State::TYPE_BACKUP
    );
    Sns_State::update( $stateData );
    die();
}

function sns_backup_save_reporting(){
    if(get_option('sns_backup_report_log') === false){
        add_option('sns_backup_report_log', 1);
    }
    if(!isset($_GET['report_log'])){
        update_option('sns_backup_report_log', 0);
    }else{
        update_option('sns_backup_report_log', 1);
    }
    die();
}

function sns_backup_sns_review_off(){
    if(get_option('sns_backup_review_off') === false){
        add_option('sns_backup_review_off', 1);
    }
    $result = new stdClass();
    $result->status = 'OK';
    sns_send_response($result);
}
