<?php
    class Sns_Dropbox {

        public static function draw(){
?>
            <span class="dropbox-subtitle">Dropbox settings <?php echo SNS_PRO_TOOLTIP; ?></span>
            <div class="dropbox-data">
                <form  class="dropbox-form">
                    <div class="form-group">
                        <label class="control-label col-sm-2">Name:</label>
                        <input id="dropbox-name" disabled="disabled" value="" type="text" class="form-control"/>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Email:</label>
                        <input id="dropbox-email" disabled="disabled" value="" type="text" class="form-control"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">Status:</label>
                        <span id="dropbox-status">inactive</span>
                    </div>
                    <div class="dropbox-btns">
                        <button type="submit" id="link-dropbox" class="btn btn-primary sns-action disabled">Link account</button>
                    </div>
                </form>
            </div>
            <div class="separator"></div>
<?php
        }

    }