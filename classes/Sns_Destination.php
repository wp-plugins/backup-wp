<?php
class Sns_Destination {

    const SETTINGS_DROPBOX = 'dropbox';
    const SETTINGS_FTP = 'ftp';
    const SETTINGS_GOOGLE_DRIVE = 'google_drive';
    const SETTINGS_AMAZON_S3 = 'amazon_s3';
    const SETTINGS_ONE_DRIVE = 'one_drive';

    const SET = '1';
    const NOT_SET = '0';

    private $mode;
    private $destinations = array();

    public function  __construct( $mode ){
        $this->mode = $mode;
    }

    public function set_destinations( $locations ){
        $this->destinations = $locations;
    }

    public static function get_destinations_list(){
        return array(
            self::SETTINGS_DROPBOX,
            self::SETTINGS_FTP,
            self::SETTINGS_GOOGLE_DRIVE,
            self::SETTINGS_AMAZON_S3,
            self::SETTINGS_ONE_DRIVE
        );
    }

    public function save(){

        global $wpdb;
        $table = SNS_DB_PREFIX.'settings_destinations';
        $status_col = ( $this->mode == Sns_Backup::BACKUP_MODE_MANUAL )?'manual_status':'schedule_status';

        foreach( $this->get_destinations_list() as $location ){
            $status_val = ( isset($this->destinations[$location]) && $this->destinations[$location] == self::SET )?self::SET:self::NOT_SET;
            $query = " UPDATE {$table}
                           SET    `{$status_col}` = '{$status_val}'
                           WHERE  `name` = '{$location}'
                        ";
            if( $wpdb->query( $query ) === false ){
                throw new Sns_Exception_DB_Error( $query );
            }
        }

    }

    public function get_destinations( ){
        global $wpdb;
        $table = SNS_DB_PREFIX.'settings_destinations';
        $status_col = ( $this->mode == Sns_Backup::BACKUP_MODE_SCHEDULE )?'schedule_status':'manual_status';
        $query = "SELECT
                          `name`,
                          `{$status_col}` as `status`
                      FROM {$table}
                    ";
        return $wpdb->get_results( $query , OBJECT_K );
    }

    public function draw(){
        $locations = $this->get_destinations();
        ?>
        <div class="form-group checkbox-containter">
            <label class="checkbox-inline">
                <input type="checkbox" disabled="disabled" checked="checked" value="">Local
            </label>
        </div>
        <div class="form-group checkbox-containter">
            <label class="checkbox-inline">
                <?php if( $this->mode == Sns_Backup::BACKUP_MODE_MANUAL ){ ?>
                    <input class="destination location-ftp" data-dest_type="ftp" name="locations[<?php echo self::SETTINGS_FTP; ?>]" type="checkbox" value="<?php echo self::SET; ?>" <?php echo ($locations[self::SETTINGS_FTP]->status == self::SET)?' checked ':'';?>>FTP
                <?php }else{ ?>
                    <input class="destination location-ftp" data-dest_type="ftp" disabled="disabled" type="checkbox">FTP
                <?php } ?>
            </label>
        </div>
        <div class="form-group checkbox-containter">
            <label class="checkbox-inline">
                <input disabled="disabled" class="destination location-dropbox" data-dest_type="dropbox" type="checkbox">Dropbox <?php echo ($this->mode == Sns_Backup::BACKUP_MODE_MANUAL)?SNS_PRO_TOOLTIP:''; ?>
            </label>
        </div>
    <?php
    }

}