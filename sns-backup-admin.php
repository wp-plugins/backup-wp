<?php
    require_once(dirname(__FILE__) . '/sns-config.php');
    require_once( SNS_CLASSES_PATH.'class-helper.php' );
    require_once( SNS_CLASSES_PATH.'class-option.php' );
    require_once( SNS_CLASSES_PATH.'class-settings.php' );
    require_once( SNS_CLASSES_PATH.'class-schedule.php' );
    require_once( SNS_CLASSES_PATH.'class-manual.php' );
    require_once( SNS_CLASSES_PATH.'class-history.php' );
?>
<div class="bootstrap-wrapper">
    <div id="backup-main-block">
        <span class="head-title"><?php _e('Backup', 'sns-backup'); ?></span>
        <a target="_blank" href="<?php echo SNS_PRO_URL; ?>" class="btn btn-warning btn-default" style="display: none; float: right; margin: -25px 0 0 10px;"><?php _e('Upgrade to PRO version'); ?></a>
        <div id="backup-main-content">
            <div id="menu-tabs" class="menu-block">
                <div class="menu-container">
                    <ul>
                        <li class="menu-item"><a href="#menu-tab-manual"><?php _e('Manual backup'); ?></a></li>
                        <li class="menu-item"><a href="#menu-tab-history"><?php _e('Backup history & restore'); ?></a></li>
                        <li class="menu-item"><a href="#menu-tab-schedule"><?php _e('Schedule'); ?></a></li>
                        <li class="menu-item"><a href="#menu-tab-options"><?php _e('Advance options'); ?></a></li>
                    </ul>
                </div>
                <div class="content-block">
                    <div id="menu-tab-manual">
                        <?php Manual::draw(); ?>
                    </div>
                    <div id="menu-tab-history">
                        <?php History::draw(); ?>
                    </div>
                    <div id="menu-tab-schedule">
                        <?php Schedule::draw(); ?>
                    </div>
                    <div id="menu-tab-options">
                        <?php Option::draw(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="emailSettingsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php _e('Please fill in your email'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-ls-4 control-label" for="textinput"><?php _e('Email'); ?></label>
                        <div class="col-ls-4">
                            <input name="settings[<?php echo Settings::SETTINGS_EMAIL; ?>][email]" id="user-email" type="text" class="form-control input-md">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close'); ?></button>
                    <button type="button" class="btn btn-primary email-save"><?php _e('Save changes'); ?></button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</div>
