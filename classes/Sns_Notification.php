<?php
    class Sns_Notification {

        public static function draw(){
?>
            <form  class="notifications-form">
                <div class="form-group">
                    <label class="control-label col-sm-2">Enable:</label>
                    <input id="notification-enable" type="checkbox" value="" disabled="disabled">
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Email:</label>
                    <input placeholder="email@example.com" value="" id="sns-settings-email" type="text" disabled="disabled" class="form-control"/>
                </div>
                <div class="separator"></div>
                <button type="submit" class="btn btn-primary sns-action disabled">Save</button>
            </form>
<?php
        }

    }