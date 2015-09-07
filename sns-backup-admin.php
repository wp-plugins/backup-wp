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
                                    Sns_Notification::draw();
                                    ?>
                                </div>
                                <div id="settings-tab-cloud">
                                    <div id="dropbox-block">
                                        <?php
                                        Sns_Dropbox::draw();
                                        ?>
                                    </div>
                                    <div id="ftp-block">
                                        <?php
                                        try
                                        {
                                            $ftp = new Sns_Ftp();
                                            $ftp->draw();
                                        }
                                        catch (Exception $e)
                                        {

                                        }
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
                                    <label class="checkbox-inline sns-report-block">
                                        <input id="sns-reporting" type="checkbox" <?php echo (get_option('sns_backup_report_log') == "1")?' checked="checked" ':'';?>>Automatically report logs
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $uploadSize = @ini_get('upload_max_filesize');
            $uploadSize = (false === $uploadSize)?0:sns_return_bytes($uploadSize);

            $postSize = @ini_get('post_max_size');
            $postSize = (false === $postSize)?0:sns_return_bytes($postSize);
            ?>
            <input id="sns-max-filesize" type="hidden" value="<?php echo min($uploadSize, $postSize); ?>">
            <!--            <a target="_blank" href="--><?php //echo SNS_BACKUP_URL.'/terms.txt'; ?><!--" class="fr terms">By using "Backup" plugin you're agreeing these terms</a>-->
        </div>
        <div id="sns-review-box" class="modal fade dn">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Leave a review</h4>
                    </div>
                    <div class="modal-body">
                        <div id="sns-rate"></div>
                    </div>
                    <div class="modal-footer">
                        <button id="sns-dont-ask" type="button" class="btn btn-default">Don't ask again</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Ask me later</button>
                        <button id="sns-review-btn" type="button" class="btn btn-primary">Review</button>
                    </div>
                    <input type="hidden" value="<?php echo SNS_BACKUP_URL.'/images/'; ?>" id="sns-image-path" />
                    <input type="hidden" value="<?php echo (get_option('sns_backup_review_off') === false)?0:1; ?>" id="sns-review-off" />
                </div>
            </div>
        </div>
    </div>
</div>
