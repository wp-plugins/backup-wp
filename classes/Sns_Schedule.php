<?php
    class Sns_Schedule {

        const CONFIG_ENABLED = '1';
        const CONFIG_DISABLED = '0';

        const CONFIG_HOURLY = 'hourly';
        const CONFIG_DAILY = 'daily';
        const CONFIG_WEEKLY = 'weekly';
        const CONFIG_MONTHLY = 'monthly';

        public static function get_config_options(){
            return array(
                self::CONFIG_HOURLY,
                self::CONFIG_DAILY,
                self::CONFIG_WEEKLY,
                self::CONFIG_MONTHLY
            );
        }

        public static function draw(){
?>
            <span class="menu-title">Configure schedule <?php echo SNS_PRO_TOOLTIP; ?></span>
            <div class="tab-content">
                <form role="form" autocomplete="off" class="schedule-form" action="">
                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" value="" disabled="disabled">Enable
                        </label>
                    </div>

                    <label>How often you want to backup?</label>
                    <div class="periodicity-block">
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" disabled="disabled">Each hour
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" disabled="disabled">Each day
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" disabled="disabled">Each week
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="radio">
                                <input type="radio" disabled="disabled">Each month
                            </label>
                        </div>
                    </div>
                    <div class="separator"></div>
                    <label>Where you want to save your backup?</label>
                    <?php
                        $destination = new Sns_Destination( Sns_Backup::BACKUP_MODE_SCHEDULE );
                        $destination->draw();
                    ?>
                    <div class="separator"></div>
                    <button type="submit" class="btn btn-primary sns-action disabled">Save</button>
                </form>
            </div>
        <?php
        }

    }