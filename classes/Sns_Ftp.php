<?php

class Sns_Ftp {

    private $server;
    private $username;
    private $password;
    private $port;

    public function __construct(){
        Sns_Checker::checkFTP();
    }

    public function setServer( $server ){
        $this->server = $server;
    }

    public function setUsername( $username ){
        $this->username = $username;
    }

    public function setPassword( $password ){
        $this->password = $password;
    }

    public function setPort( $port ){
        $this->port = $port;
    }

    public function unlink(){
        $this->setServer("");
        $this->setUsername("");
        $this->setPassword("");
        $this->setPort("");
        $this->save();
    }

    public function fill_data(){
        $data = $this->get_details();
        $this->setServer( $data['server'] );
        $this->setUsername( $data['username'] );
        $this->setPassword( $data['password'] );
        $this->setPort( $data['port'] );
    }

    public function get_details(){
        global $wpdb;
        $table = SNS_DB_PREFIX.'settings_ftp';
        $query_str = "SELECT
                            `server`,
                            `username`,
                            `password`,
                            `port`
                          FROM {$table}
                          LIMIT 1";

        $data = $wpdb->get_results( $query_str , ARRAY_A );
        return $data[0];
    }

    public function save(){

        global $wpdb;
        $table = SNS_DB_PREFIX.'settings_ftp';
        $query =  "UPDATE `{$table}`
                       SET  `server` = '".strval($this->server)."',
                            `username` = '".strval($this->username)."',
                            `password` = '".strval($this->password)."',
                            `port` = '".strval($this->port)."'
                        ";
        if ($wpdb->query($query) === false ){
            throw new Sns_Exception_DB_Error($query);
        }

    }

    public function test(){

        if ($this->server == '') {
            throw new Sns_Exception_Unavailable_Operation('Cannot connect to FTP server');
        }

        $con = ftp_connect($this->server, $this->port);
        if (false === $con) {
            throw new Sns_Exception_Unavailable_Operation('Cannot connect to FTP server');
        }

        $loggedIn = ftp_login($con,  $this->username,  $this->password);
        if( $loggedIn === false ){
            throw new Sns_Exception_Unavailable_Operation('Cannot log in');
        }

        if( ftp_close($con) === false ) {
            throw new Sns_Exception_Unavailable_Operation('Cannot close FTP connection');
        }

    }

    public function upload( $filePath , $fileName ){

        $details = $this->get_details();
        $this->setServer($details['server']);
        $this->setUsername($details['username']);
        $this->setPassword($details['password']);
        $this->setPort($details['port']);
        $conn_id = ftp_connect($this->server, $this->port);

        $login = ftp_login($conn_id, $this->username, $this->password);
        if( $login ){
            $dir = SNS_FTP_BACKUPS_FOLDER;
            if( !$this->ftp_is_dir( $conn_id , $dir ) ){
                if (ftp_mkdir($conn_id, $dir) === false){
                    throw new Sns_Exception_Unavailable_Operation('Cannot create folder '.$dir.'on FTP server');
                }
            }
            if (ftp_put($conn_id, $dir.'/'.$fileName, $filePath, FTP_BINARY) === false) {
                throw new Sns_Exception_Unavailable_Operation('Cannot upload file '.$filePath.'to FTP server');
            }
        }
        else{
            throw new Sns_Exception_Unavailable_Operation('Cannot connect to FTP server.');
        }
        if( ftp_close( $conn_id ) === false ){
            throw new Sns_Exception_Unavailable_Operation('Cannot close connection to FTP server.');
        }
    }

    public function ftp_is_dir( $ftpcon , $dir ) {
        // get current directory
        $original_directory = ftp_pwd( $ftpcon );
        // test if you can change directory to $dir
        // suppress errors in case $dir is not a file or not a directory
        if ( ftp_chdir( $ftpcon, $dir ) !== false ) {
            // If it is a directory, then change the directory back to the original directory
            @ftp_chdir( $ftpcon, $original_directory );
            return true;
        }
        else {
            return false;
        }
    }

    public function is_linked(){
        try{
            $this->fill_data();
            $this->test();
            return true;
        }catch( Exception $e ){
            return false;
        }
    }

    public function draw(){
        $details = $this->get_details();
        $linked = $this->is_linked();
		if( extension_loaded('ftp') ){
        ?>
        <span class="ftp-subtitle">FTP server details</span>
        <div class="ftp-data">
            <form  class="ftp-form">
                <div class="form-group">
                    <label class="control-label col-sm-2">Server:</label>
                    <input name="ftp[server]" <?php echo ($linked)?' disabled="disabled" ':''; ?>placeholder="ftp.example.com" value="<?php echo $details['server']; ?>" type="text" class="form-control"/>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">User Name:</label>
                    <input name="ftp[username]" <?php echo ($linked)?' disabled="disabled" ':''; ?> placeholder="user@example.com" value="<?php echo $details['username']; ?>" type="text" class="form-control"/>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Password:</label>
                    <input name="ftp[password]" <?php echo ($linked)?' disabled="disabled" ':''; ?> placeholder="******" value="<?php echo $details['password']; ?>" type="password" class="form-control"/>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Port:</label>
                    <input name="ftp[port]" <?php echo ($linked)?' disabled="disabled" ':''; ?> placeholder="<?php echo SNS_FTP_DEF_PORT; ?>" value="<?php echo $details['port']; ?>" type="text" class="form-control"/>
                </div>
                <div class="form-group">
                    <label class="col-sm-2">Status:</label>
                    <span id="ftp-status"><?php if( $linked ){ echo 'active'; }else{ echo 'inactive'; } ?></span>
                </div>
                <div class="separator"></div>
                <div class="dropbox-btns">
                    <button type="submit" id="link-ftp" class="btn btn-primary sns-action <?php if( $linked ){ echo ' dn ';} ?>">Link account</button>
                    <button id="unlink-ftp" class="btn btn-primary sns-action <?php if( !$linked ){ echo ' dn ';} ?>">Unlink</button>
                </div>
            </form>
        </div>
		<?php }
    }

}