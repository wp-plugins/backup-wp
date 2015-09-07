(function($) {

    var $manual = {};
    var $history = {};
    var $settings = {};
    var $state = {};
    var $options = {};

    $state.lastResponseTime = 0;
    $state.TYPE_BACKUP = 'backup';
    $state.TYPE_RESTORE = 'restore';
    $state.TYPE_UPLOAD = 'upload';
    $state.ACTIVE = 'active';
    $state.FINISHED = 'finished';
    $state.FAILED = 'failed';
    $state.NONE = 'none';
    $state.READY_TO_START = 'ready_to_start';
    $state.backupProgress = null;
    $state.restoreProgress = null;
    $state.uploadProgress = null;
    $state.currentStatus = $state.NONE;

    $state.isDisabled = false;

    $state.disableActions = function(){
        $state.isDisabled = true;
        $("#backup-main-block .sns-action").attr("disabled" , "disabled");
    };

    $state.enableActions = function(){
        $("#backup-main-block .sns-action").removeAttr("disabled");
        $state.isDisabled = false;
    };

    $state.polling = null;
    $state.startPoll = function(){
        $state.getStatus();
    };

    $state.stopPoll = function(){
        if( $state.backupProgress != null ){
            $state.quickCompleteProgress( $state.TYPE_BACKUP );
        }else if( $state.restoreProgress != null ){
            $state.quickCompleteProgress( $state.TYPE_RESTORE );
        }else{
            $state.enableActions();
        }
    };

    $state.getStatus = function(){
        $.ajax ( {
            type		:	'get',
            url			: 	ajaxurl,
            data	    : 	{action : 'sns_state_get_status'},
            dataType	: 	'json',
            success		: 	function( result ) {
                if(typeof result.data !== 'undefined'){
                    $state.currentStatus = result.data.status;
                    if( $state.processStatus( result.data ) ){
                        $state.getStatus();
                    }
                }else{
                    $state.getStatus();
                }

            },
            error       :   function( result ) {
                $state.getStatus();
            }
        } );
    };

    $state.reset = function(){
        $.ajax ( {
            type		:	'get',
            url			: 	ajaxurl,
            data	    : 	{action : 'sns_state_reset_status'},
            dataType	: 	'json'
        } );
    };

    $state.quickCompleteProgress = function( type ){
        $state.quickProgress = setInterval( function(){
                var val;
                if( type == $state.TYPE_BACKUP ){
                    if( $state.backupProgress == null ){
                        return;
                    }
                    val = $state.backupProgress.progressbar( "value" );
                }else if( type == $state.TYPE_RESTORE ){
                    if( $state.restoreProgress == null ){
                        return;
                    }
                    val = $state.restoreProgress.progressbar( "value" );
                }
                var o = val+10;
                if( type == $state.TYPE_BACKUP ){
                    $state.backupProgressLabel.text( o+'%' );
                    $state.backupProgress.progressbar( "value", o );
                }else if( type == $state.TYPE_RESTORE ){
                    $state.restoreProgressLabel.text( o+'%' );
                    $state.restoreProgress.progressbar( "value", o );
                }
            },
            0.0000001
        );
    };

    $state.processStatus = function( data ){

        if( data.status == $state.ACTIVE || data.status == $state.READY_TO_START ){
            if(!$state.isDisabled){
                $state.disableActions();
            }
            $state.drawProgress( data );
            return true;
        }
        if( data.status == $state.FINISHED ){
            $state.reset();
            if( data.type == $state.TYPE_BACKUP ){
                $.snsToast('Backed up!' , true);
                $state.review();
                if( $state.backupProgress == null ){
                    $state.enableActions();
                }
            }else if( data.type == $state.TYPE_RESTORE ){
                $.snsToast('Restored!' , true);
                if( $state.restoreProgress == null ){
                    $state.enableActions();
                }
            }
        }else if( data.status == $state.FAILED ){
            $.snsToast('Failed!' , true);
            $state.reset();
            if( data.type == $state.TYPE_BACKUP ){
                if( $state.backupProgress == null ){
                    $state.enableActions();
                }
            }else if( data.type == $state.TYPE_RESTORE ){
                if( $state.restoreProgress == null ){
                    $state.enableActions();
                }
            }
        }
        $state.stopPoll();
        return false;
    };

    $state.drawProgress = function( data ) {

        if( data.type == $state.TYPE_BACKUP ){
            if( $state.backupProgress == null ){
                $state.progressInit( data.type );
            }
            $state.backupProgress.progressbar( "value", data.progress );
            $state.backupProgressLabel.text( data.progress_view );
        }else if( data.type == $state.TYPE_RESTORE ){
            if( $state.restoreProgress == null ){
                $state.progressInit( data.type );
            }
            $state.restoreProgress.progressbar( "value", data.progress );
            $state.restoreProgressLabel.text( data.progress_view );
        }else if( data.type == $state.TYPE_UPLOAD ){
            if( $state.uploadProgress == null ){
                $state.progressInit( data.type );
            }
            $state.uploadProgress.progressbar( "value", data.progress );
            $state.uploadProgressLabel.text( data.progress_view );
        }
    };

    $state.progressInit = function( type ){

        if( type == $state.TYPE_BACKUP ){
            $state.backupProgress = $( "#progressbar-backup" );
            $state.backupProgressLabel = $( "#progressbar-backup .progress-label" );
            $state.backupProgress.progressbar({
                value: false,
                max: 100,
                complete: function() {
                    clearInterval( $state.quickProgress );
                    $state.backupProgressLabel.text("");
                    $state.backupProgress.progressbar( "destroy" );
                    $state.backupProgress = null;
                    if( $state.currentStatus == $state.FINISHED ){
                        $.snsToast('Backed up!' , true);
                        $state.review();
                    }else if( $state.currentStatus == $state.FAILED ){
                        $.snsToast('Failed!' , true);
                    }else{
                        $.snsToast();
                    }
                    $state.enableActions();
                }
            });
        }else if( type == $state.TYPE_RESTORE ){
            $state.restoreProgress = $( "#progressbar-restore" );
            $state.restoreProgressLabel = $( "#progressbar-restore .progress-label" );
            $state.restoreProgress.progressbar({
                value: false,
                max: 100,
                complete: function() {
                    clearInterval( $state.quickProgress );
                    $state.restoreProgressLabel.text("");
                    $state.restoreProgress.progressbar( "destroy" );
                    $state.restoreProgress = null;
                    if( $state.currentStatus == $state.FINISHED ){
                        $.snsToast('Restored!' , true);
                    }else if( $state.currentStatus == $state.FAILED ){
                        $.snsToast('Failed!' , true);
                    }else{
                        $.snsToast();
                    }
                    $state.enableActions();
                }
            });
        }else if( type == $state.TYPE_UPLOAD ){
            $state.uploadProgress = $( "#progressbar-upload" );
            $state.uploadProgressLabel = $( "#progressbar-upload .progress-label" );
            $state.uploadProgress.progressbar({
                value: false,
                max: 100,
                complete: function() {
                    $state.uploadProgressLabel.text("");
                    $state.uploadProgress.progressbar( "destroy" );
                    $state.uploadProgress = null;
                    $.snsToast('Uploaded!' , true);
                }
            });
        }

    };

    $state.review = function(){
        if($("#sns-review-off").val() != 1){
            $("#sns-review-box").modal('show');
        }
    };

    $manual.save = function(){
        if( $state.isDisabled ){
            return false;
        }
        $state.disableActions();
        var form = $("#backup-main-content .manual-form");
        var send_data = form.serializeArray();
        send_data.push({name: 'action', value :'sns_manual_backup'});
        $.snsToast('Waiting...');

        $.ajax ( {
            type		:	'post',
            url			: 	ajaxurl,
            data	    : 	{action:'sns_prepare_process',type:'backup'},
            dataType	: 	'json',
            success     :   function( result ){
                $.ajax ( {
                    type		:	'post',
                    url			: 	ajaxurl,
                    data	    : 	send_data,
                    dataType	: 	'json'
                } );
                $state.startPoll();
            }
        } );
    };

    $options.configure = function(){

        $("#backup-main-content .option-full").change(function(){
            if($(this).is(':checked')){
                $("#backup-main-content .option").attr('checked','checked');
            }
        });
        $("#backup-main-content .option").change(function(){
            if(!$(this).is(':checked')){
                $("#backup-main-content .option-full").removeAttr('checked');
            }
        });

    };

    $options.save = function(){

        var form = $("#backup-main-content .options-form");
        var send_data = form.serializeArray();
        send_data.push({name: 'action', value :'sns_save_options'});
        $.snsToast('Saving...');
        $.ajax ( {
            type		:	'post',
            url			: 	ajaxurl,
            data	    : 	send_data,
            dataType	: 	'json',
            success		: 	function( result ) {
                if( result.status == 'OK' ){
                    $.snsToast('Saved!' , true);
                }else{
                    $.processResult( result );
                }
            },
            error:function( result ) {
                $.processResult( JSON.parse(result.responseText) );
            }
        } );

    };

    $settings.saveFTP = function(){

        if( $state.isDisabled ){
            return false;
        }
        var form = $("#backup-main-content .ftp-form");
        var send_data = form.serializeArray();
        send_data.push({name: 'action', value :'sns_save_ftp'});
        $.snsToast('Linking...');
        $state.disableActions();
        $.ajax ( {
            type		:	'post',
            url			: 	ajaxurl,
            data	    : 	send_data,
            dataType	: 	'json',
            success		: 	function( result ) {
                if( result.status == 'OK' ){
                    $.snsToast('Linked!' , true);
                    $("#backup-main-content .ftp-data input").attr('disabled' , 'disabled');
                    $("#backup-main-content #link-ftp").hide();
                    $("#backup-main-content #unlink-ftp").show();
                    $("#backup-main-content #ftp-status").text("active");
                }else{
                    $.processResult( result );
                }
                $state.enableActions();
            },
            error:function( result ) {
                $.processResult( JSON.parse(result.responseText) );
                $state.enableActions();
            }
        } );

    };

    $settings.unlinkFTP = function(){

        var send_data = [];
        send_data.push({name: 'action', value :'sns_unlink_ftp'});
        $.snsToast('Unlinking...');
        $.ajax ( {
            type		:	'post',
            url			: 	ajaxurl,
            data	    : 	send_data,
            dataType	: 	'json',
            success		: 	function( result ) {
                if( result.status == 'OK' ){
                    $("#backup-main-content #link-ftp").show();
                    $("#backup-main-content #unlink-ftp").hide();
                    $.snsToast('Unlinked!' , true);
                    $("#backup-main-content .ftp-data input").val("").removeAttr("disabled");
                    $("#backup-main-content #ftp-status").text('inactive');
                }else{
                    $.processResult( result );
                }
            },
            error:function( result ) {
                $.processResult( JSON.parse(result.responseText) );
            }
        } );

    };

    $settings.configure = function(){

        $settings.configure_log();

        $("#backup-main-content .destination").click(function(){
            var dest_type = $(this).data('dest_type');
            var action = 'sns_check_'+dest_type;
            if( $(this).is(':checked') ){
                $.snsToast('Checking...');
                $.ajax ( {
                    type		:	'post',
                    url			: 	ajaxurl,
                    data        :   {action: action },
                    dataType	: 	'json',
                    success		: 	function( result ) {
                        $.snsToast('Checked' , true);
                        if( result.status != 'OK' ){
                            $settings.process_check( dest_type );
                        }
                    },
                    error:function( result ) {
                        $.snsToast('Checked' , true);
                        $settings.process_check( dest_type );
                    }
                } );
            }
        });
    };

    $settings.process_check = function( dest_type ){
        $("#backup-main-content .location-"+dest_type).removeAttr("checked");
        if ( confirm( "Cannot connect to "+dest_type+", click OK to check details." ) ) {
            var index = $('#backup-main-content #menu-tabs a[href="#menu-tab-settings"]').parent().index();
            $("#backup-main-content #menu-tabs").tabs("option", "active", index);
            var index = $('#backup-main-content #settings-tabs a[href="#settings-tab-cloud"]').parent().index();
            $("#backup-main-content #settings-tabs").tabs("option", "active", index);
        }
    };

    $settings.configure_log = function(){
        $("#backup-main-content #log-refresh").click(function(){
            $.snsToast('Refreshing...');
            $.ajax ( {
                type		:	'post',
                url			: 	ajaxurl,
                data        :   {action: 'sns_log_refresh' },
                dataType	: 	'json',
                success		: 	function( result ) {
                    $.snsToast('Refreshed' , true);
                    if( result.status == 'OK' ){
                        $("#backup-main-content #log-content").val(result.data);
                    }else{
                        $.processResult( result );
                    }
                },
                error:function( result ) {
                    $.processResult( JSON.parse(result.responseText) );
                }
            } );
        });
        $("#backup-main-content #log-empty").click(function(){
            $.snsToast('Emptying...');
            $.ajax ( {
                type		:	'post',
                url			: 	ajaxurl,
                data        :   {action: 'sns_log_empty' },
                dataType	: 	'json',
                success		: 	function( result ) {
                    $.snsToast('Emptied' , true);
                    if( result.status == 'OK' ){
                        $("#backup-main-content #log-content").val('');
                    }else{
                        $.processResult( result );
                    }
                },
                error:function( result ) {
                    $.processResult( JSON.parse(result.responseText) );
                }
            } );
        });
    };

    $history.delete = function( id , elem ){

        $.snsToast('Deleting...');
        $.ajax ( {
            type		:	'get',
            url			: 	ajaxurl,
            data        :   { id: id , action: 'sns_backup_delete' },
            dataType	: 	'json',
            success		: 	function( result ) {
                if( result.status == 'OK' ){
                    $(elem).parents('tr').remove();
                    $.snsToast('Deleted!' , true);
                }else{
                    $.processResult( result );
                }
            },
            error:function( result ) {
                $.processResult( JSON.parse(result.responseText) );
            }
        } );

    };

    $history.restore = function( id ){
        if( $state.isDisabled ){
            return false;
        }
        $state.disableActions();
        $.snsToast('Waiting...');

        $.ajax ( {
            type		:	'post',
            url			: 	ajaxurl,
            data	    : 	{action:'sns_prepare_process',type:'restore'},
            dataType	: 	'json',
            success     :   function( result ){
                $.ajax ( {
                    type		:	'get',
                    url			: 	ajaxurl,
                    data        :   { id: id , action: 'sns_backup_restore' },
                    dataType	: 	'json'
                } );
                $state.startPoll();
            }
        } );

    };



    function sns_remove_param_from_url(url, parameter) {
        //prefer to use l.search if you have a location/link object
        var urlparts= url.split('?');
        if (urlparts.length>=2) {

            var prefix= encodeURIComponent(parameter)+'=';
            var pars= urlparts[1].split(/[&;]/g);

            //reverse iteration as may be destructive
            for (var i= pars.length; i-- > 0;) {
                //idiom for string.startsWith
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }

            url= urlparts[0]+'?'+pars.join('&');
            return url;
        } else {
            return url;
        }
    }

    function sns_replace_url_param(url, paramName, paramValue){
        var pattern = new RegExp('('+paramName+'=).*?(&|$)');
        var newUrl=url;
        if(url.search(pattern)>=0){
            if(paramValue == '')
            {
                newUrl = url.replace(pattern,'');
            } else {
                newUrl = url.replace(pattern,'$1' + paramValue + '$2');
            }
        }
        else{
            if( paramValue!='' ){
                newUrl = newUrl + (newUrl.indexOf('?')>0 ? '&' : '?') + paramName + '=' + paramValue;
            }
        }
        return newUrl;
    }

    $history.configure = function(){
        $("#backup-main-content .btn-delete").click(function(){
            if ( confirm( "Are you sure you want to delete the backup?" ) ) {
                $history.delete( $(this).data('backup_id') , $(this) );
            }
        });
        $("#backup-main-content .btn-restore").click(function(){
            var url = window.location.href;
            url = sns_remove_param_from_url(url, 'sns_ex_restore');
            url = sns_remove_param_from_url(url, 'sns_uname');
            url = sns_replace_url_param(url, 'sns_restore', 1);
            url = sns_replace_url_param(url, 'sns_backup_id', $(this).data('backup_id'));
            window.location.href = url;
            // $history.restore( $(this).data('backup_id') );
        });
    };

    $history.configureExternalRestore = function(){

        var uploadResponse = {status:'OK'};
        var uploader = new plupload.Uploader({

            browse_button : 'external-browse',
            container: document.getElementById('external-container'),
            url : ajaxurl,
            multipart_params: {'action':'sns_external_upload'},
            file_data_name: 'backup_file',
            multi_selection: false,
            filters : {
                max_file_size:$("#sns-max-filesize").val(),
                mime_types: [
                    {title : "zip files", extensions : "zip"}
                ]
            },

            init: {
                PostInit: function() {
                    document.getElementById('external-restore').onclick = function() {
                        if( $state.isDisabled ){
                            return false;
                        }
                        $state.disableActions();
                        uploader.start();

                        return false;
                    };
                },

                FilesAdded: function(up, files) {
                    $("#backup-main-block .external-backup-input").val(files[0]['name']);
                },

                UploadProgress: function(up, file){
                    var data = {
                        type:$state.TYPE_UPLOAD,
                        progress:file.percent,
                        progress_view:file.percent+'%'
                    };
                    $state.drawProgress( data );
                },

                FileUploaded: function(upldr, file, object) {
                    var result;
                    try {
                        result = eval(object.response);
                    } catch(err) {
                        result = eval('(' + object.response + ')');
                    }
                    if(result.status == 'OK'){
                        $history.externalRestore(result.uname);
                    }else{
                        $.processResult(result);
                    }
                },

                BeforeUpload: function() {
                    $.snsToast('Uploading...');
                },

                Error: function(up, err) {
                    if(typeof err.response != 'undefined'){
                        uploadResponse = JSON.parse( err.response );
                        $.processResult(uploadResponse);
                    }else{
                        if(err.code == '-600'){
                            alert('The size of the uploaded file is greater than allowed.Check your server settings.');
                        }else{
                            alert(err.message);
                        }
                    }
                }
            }
        });

        uploader.init();

    };

    $history.externalRestore = function(uname){
        var url = window.location.href;
        url = sns_remove_param_from_url(url, 'sns_restore');
        url = sns_remove_param_from_url(url, 'sns_backup_id');
        url = sns_replace_url_param(url, 'sns_ex_restore', 1);
        url = sns_replace_url_param(url, 'sns_uname', uname);
        window.location.href = url;

//        $.snsToast('Restoring...');
//
//        $.ajax ( {
//            type		:	'post',
//            url			: 	ajaxurl,
//            data	    : 	{action:'sns_prepare_process',type:'restore'},
//            dataType	: 	'json',
//            success     :   function( result ){
//                $.ajax ( {
//                    type		:	'post',
//                    url			: 	ajaxurl,
//                    data        :   { filename: filename , action: 'sns_external_restore' },
//                    dataType	: 	'json',
//                    success     :   function(){
//                        if( $("#backup-main-block .external-backup-input").val() == '' ){
//                            return;
//                        }
//                        $("#backup-main-block .external-backup-input").val('');
//
//                        var control = $("#backup-main-block input[name=backup_file]");
//                        control.replaceWith( control = control.clone( true ) );
//                    }
//                } );
//                $state.startPoll();
//            }
//        } );
    };

    $( document ).ready(function(){
        $state.disableActions();
        $(window).load(function(){
            $state.enableActions();
        });
        $("#sns-review-box").modal({show:false});
        $("div#sns-rate").raty({
            number:		5,
            start:  5,
            starOn: 'star-on.png',
            starOff: 'star-off.png',
            path: $("#sns-image-path").val(),
            width: 200
        });
        $("#sns-review-btn").click(function(){
            $("#sns-review-box").modal('hide');
            window.open('https://wordpress.org/support/view/plugin-reviews/backup-wp?rate='+$("#sns-rate-score").val()+'#postform', '_blank');
        });
        $("#sns-dont-ask, #sns-review-btn").click(function(){
            var sendData = {action: 'sns_review_off'};
            $.ajax ( {
                type		:	'get',
                data        :   sendData,
                url			: 	ajaxurl,
                dataType	: 	'json',
                success		: 	function( result ) {
                    if(result.status == 'OK'){
                        $("#sns-review-off").val(1);
                        $("#sns-review-box").modal('hide');
                    }else{
                        $.processResult( JSON.parse(result.responseText) );
                    }
                },
                error:function( result ) {
                    $.processResult( JSON.parse(result.responseText) );
                }
            } );
        });
        $("#backup-main-content #settings-tabs").tabs();
        $("#backup-main-content #menu-tabs").tabs({
            activate: function(event ,ui){
                if(ui.newTab.find('a').attr('href') == '#menu-tab-history'){
                    $.snsToast('Updating...');
                    var sendData = {action: 'sns_history_update'};
                    $.ajax ( {
                        type		:	'get',
                        data        :   sendData,
                        url			: 	ajaxurl,
                        dataType	: 	'html',
                        success		: 	function( result ) {
                            $("#backup-main-content #menu-tab-history .menu-content .records").empty().html( result );
                            $history.configure();
                            $.snsToast('Updated!' , true);
                        },
                        error:function( result ) {
                            $.processResult( JSON.parse(result.responseText) );
                        }
                    } );
                }
            }
        });

        $("#backup-main-content .manual-form").submit(function(){
            $manual.save();
            return false;
        });

        $("#backup-main-content .ftp-form").submit(function(){
            $settings.saveFTP();
            return false;
        });

        $("#backup-main-content #unlink-ftp").click(function(){
            $settings.unlinkFTP();
            return false;
        });

        $("#backup-main-content #restore-file").change(function(){
            $("#backup-main-content .restore-form").submit();
        });

        $settings.configure();

        $history.configure();
        $history.configureExternalRestore();

        $state.startPoll();

        $("#sns-reporting").click(function(){console.log('blabla');
            var sendData = {action: 'sns_save_reporting'};
            if($(this).attr('checked') == 'checked'){
                sendData.report_log = 1;
            }
            $.ajax ( {
                type		:	'get',
                data        :   sendData,
                url			: 	ajaxurl,
                error:function( result ) {
                    $.processResult( JSON.parse(result.responseText) );
                }
            } );
        });

    });

    $.processResult = function( result ){
        $.snsToast();
        if( typeof result.error_msg != 'undefined' && result.error_msg != '' ){
            alert( result.error_msg );
        }else{
            $.snsToast('System error' , false , null , 'red');
        }
    };


    $.snsToast = function ( data, closeTimeout , closeCallback , textColor ) {

        var	closeSnsToastDialog =  function () {
            var obj = $('.snsToastBox');
            if ( obj.length > 0 ) {
                var timeoutRef = obj.data( 'timeoutRef' );
                if ( typeof timeoutRef != 'undefined' && timeoutRef != null ) {
                    clearTimeout( timeoutRef );
                    obj.data( 'timeoutRef' , null );
                    obj.remove();
                }
            }
        };

        var defCloseTimeout = 1000;

        var options = {
            text 			:	 ' Loading ... ',
            action			:	 'open',				//	open , close
            closeTimeout	:	 -1,
            textColor		:    null,
            closeCallback	:	 null
        };


        if ( typeof data == 'undefined'  ) {
            options.action 		  = 'close';
        }
        else if ( typeof data == 'object' ) {

        }
        else {
            options.text = data;
        }

        if ( typeof closeTimeout != 'undefined' && closeTimeout !== false ) {
            if ( closeTimeout === true ) {
                closeTimeout = defCloseTimeout;
            }
            options.closeTimeout = closeTimeout;
            if ( typeof closeCallback != 'undefined' ) {
                options.closeCallback = closeCallback;
            }
        }

        if ( typeof textColor != 'undefined' ) {

            if (	textColor === true	) {
                options.textColor = 'red';
            }
            else {
                options.textColor = textColor;
            }

        }


        if ( options.action == 'open' ) {

            var obj = $('.snsToastBox');
            if ( obj.length == 0 ) {
                obj = $( '<div/>' ).addClass( 'snsToastBox' );
                $( '#backup-main-content' ).append( obj );
                var tmpF = function (){ var aaa = 1;  alert( aaa );  aaa ++;  } ;
                obj.data( 'timeoutRef' , tmpF );
            }

            if ( options.closeTimeout != -1 ) {
                var timeoutToClose = setTimeout( function () {
                    closeSnsToastDialog();
                    if ( typeof options.closeCallback == 'function' ) {
                        options.closeCallback();
                    }
                } ,  closeTimeout );
            }
            obj.html( '<div class="snsToastText" >'+options.text+'</div>' );

            if ( options.textColor !== null ) {
                $( '.snsToastText' , obj ).css ( 'color' , options.textColor );
            }

        }
        else  {

            if ( options.closeTimeout != -1 ) {

                var timeoutToClose = setTimeout( function () {
                    closeSnsToastDialog();
                    if ( typeof options.closeCallback == 'function' ) {
                        options.closeCallback();
                    }
                } ,  closeTimeout );

            }
            else {
                closeSnsToastDialog();
                if ( typeof options.closeCallback == 'function' ) {
                    options.closeCallback();
                }
            }

        }

    };

})( jQuery );
