<?php
class Sns_State {

    const TYPE_RESTORE = 'restore';
    const TYPE_BACKUP = 'backup';
    const STATUS_ACTIVE = 'active';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';
    const STATUS_NONE = 'none';
    const STATUS_READY_TO_START = 'ready_to_start';

	public static $currentBackupFilename = '';
	
    public static function get_status($type = Sns_Backup::BACKUP_MODE_MANUAL){
          global $wpdb;
            if( $type == Sns_Backup::BACKUP_MODE_MANUAL ){
                $table = SNS_DB_PREFIX.'state';
                $query = "SELECT
                                `type`,
                                `status`,
                                `start_date`,
                                `msg`
                           FROM {$table}
                           LIMIT 1";
                $res = $wpdb->get_results( $query , ARRAY_A );
            }else{
                $table = SNS_DB_PREFIX.'schedule_config';
                $query = "SELECT
                                `status`
                           FROM {$table}
                           WHERE `status` = '".Sns_State::STATUS_ACTIVE."'
                           LIMIT 1";
                $res = $wpdb->get_results( $query , ARRAY_A );
            }
            if( !empty( $res ) ){
                $res = $res[0];
            }
            return $res;
    }

    public static function update( $data ){
        global $wpdb;

        $table = SNS_DB_PREFIX.'state';
        $query = " UPDATE {$table}
                       SET    `status` = '{$data['status']}'
                     ";
        if( isset( $data['type'] ) ){
            $query .= " ,`type` = '{$data['type']}' ";
        }
        if( isset( $data['start_date'] ) ){
            $query .= " ,`start_date` = '{$data['start_date']}' ";
        }
        $wpdb->query( $query );
    }
}