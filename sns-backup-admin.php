<?php
    require_once(dirname(__FILE__) . '/sns-config.php');
    require_once( SNS_CLASSES_PATH.'class-helper.php' );
    require_once( SNS_CLASSES_PATH.'class-option.php' );
    require_once( SNS_CLASSES_PATH.'class-manual.php' );
    require_once( SNS_CLASSES_PATH.'class-history.php' );
?>
<div class="bootstrap-wrapper">
    <div id="backup-main-block">
        <span class="head-title"><?php _e('Backup', 'sns-backup'); ?></span>
        <a target="_blank" href="<?php echo SNS_PRO_URL; ?>" class="btn btn-warning btn-default btn-upgrade"><?php _e('Upgrade to PRO version', 'sns-backup'); ?></a>
        <div id="backup-main-content">
            <div id="menu-tabs" class="menu-block">
                <div class="menu-container">
                    <ul>
                        <li class="menu-item"><a href="#menu-tab-manual"><?php _e('Manual backup', 'sns-backup'); ?></a></li>
                        <li class="menu-item"><a href="#menu-tab-history"><?php _e('Backup history & restore', 'sns-backup'); ?></a></li>
                        <li class="menu-item sns-tooltip"><a href="#menu-tab-schedule"><?php _e('Schedule', 'sns-backup'); ?></a></li>
                        <li class="menu-item sns-tooltip"><a href="#menu-tab-options"><?php _e('Advance options', 'sns-backup'); ?></a></li>
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
                    </div>
                    <div id="menu-tab-options">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="pro-label-content" style="display: none"><?php Helper::showPROLabel();?></div>