<div class="bootstrap-wrapper">
    <div id="backup-main-block">
        <span class="head-title">Backup</span>
        <a target="_blank" href="<?php echo SNS_PRO_URL; ?>" class="btn btn-warning btn-default" style="float: right; margin: -25px 0 0 10px; background-color:rgb(255,90,0);border-color:rgb(255,90,0);">Upgrade to PRO version</a>
        <div id="backup-main-content">
            <div id="menu-tabs" class="menu-block">
                <div class="menu-container">
                    <ul>
                        <li class="menu-item"><a href="#menu-tab-manual">Manual backup</a></li>
                        <li class="menu-item"><a href="#menu-tab-history">Backup history & restore</a></li>
                        <li class="menu-item"><a href="#menu-tab-schedule">Schedule</a></li>
                        <li class="menu-item"><a href="#menu-tab-settings">Settings</a></li>
                    </ul>
                </div>
                <div class="content-block">
                    <div id="menu-tab-manual">
                        <span class="menu-title">Backup destination</span>
                        <div class="menu-content">
                            <form class="manual-form" autocomplete="off" role="form" action="">
                                <?php
                                    $destination = new Sns_Destination( Sns_Backup::BACKUP_MODE_MANUAL );
                                    $destination->draw();
                                ?>
                                <div class="separator"></div>
                                <div class="cb"></div>
                                <div id="progressbar-backup"><div class="progress-label"></div></div>

                                <button type="submit" class="btn btn-primary sns-action">Backup</button>
                            </form>
                        </div>
                    </div>
                    <div id="menu-tab-history">
                        <?php Sns_History::draw(); ?>
                    </div>
                    <div id="menu-tab-schedule">
                        <?php Sns_Schedule::draw(); ?>
                    </div>
                    <div id="menu-tab-settings">
                        <div id="settings-tabs">
                            <div class="settings-items">
                                <ul>
                                    <li class="settings-item"><a href="#settings-tab-options">Options <?php echo SNS_PRO_TOOLTIP; ?></a></li>
                                    <li class="settings-item settings-item-middle"><a href="#settings-tab-notifications">Notifications <?php echo SNS_PRO_TOOLTIP; ?></a></li>
                                    <li class="settings-item settings-item-middle"><a href="#settings-tab-cloud">Cloud</a></li>
                                    <li class="settings-item settings-item-middle"><a href="#settings-tab-log">Log</a></li>
                                </ul>
                            </div>
                            <div class="settings-content">
                                <div id="settings-tab-options">
                                    <?php Sns_Option::draw(); ?>
                                </div>
                                <div id="settings-tab-notifications">
                                    <?php
                                        $notifications = new Sns_Notification();
                                        $notifications->draw();
                                    ?>
                                </div>
                                <div id="settings-tab-cloud">
                                    <div id="dropbox-block">
                                        <?php
                                            $dropbox = Sns_Dropbox::draw();
                                        ?>
                                    </div>
                                    <div id="ftp-block">
                                        <?php
                                            $ftp = new Sns_Ftp();
                                            $ftp->draw();
                                        ?>
                                    </div>
                                </div>
                                <div id="settings-tab-log">
                                    <div class="fr">
                                        <button id="log-refresh" type="button" class="btn btn-primary">
                                            <span class="glyphicon glyphicon-refresh"></span>
                                        </button>
                                        <button id="log-empty" type="button" class="btn btn-default">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </button>
                                    </div>
                                    <div class="cb"></div>
                                    <textarea id="log-content"><?php Sns_Log::print_log(); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<!--            <a target="_blank" href="--><?php //echo SNS_BACKUP_URL.'/terms.txt'; ?><!--" class="fr terms">By using "Backup" plugin you're agreeing these terms</a>-->
        </div>
    </div>
</div>
