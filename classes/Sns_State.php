<?php
class Sns_State {

    const TYPE_RESTORE = 'restore';
    const TYPE_BACKUP = 'backup';
    const STATUS_ACTIVE = 'active';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';
    const STATUS_NONE = 'none';
    const STATUS_READY_TO_START = 'ready_to_start';

    public static function get_status(){
        global $wpdb;
        $table = SNS_DB_PREFIX.'state';
        $query = "SELECT
                            `type`,
                            `status`,
                            `start_date`,
                            `msg`
                       FROM {$table}
                       LIMIT 1";
        $res = $wpdb->get_results( $query , ARRAY_A );
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