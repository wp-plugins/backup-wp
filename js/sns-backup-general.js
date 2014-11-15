(function($) {

    var $manual = {};
    var $history = {};

    $manual.save = function(){

        $.snsToast('Backing up...');
        $.ajax ( {
            type		:	'post',
            url			: 	ajaxurl,
            data	    : 	{action:'sns_manual_backup'},
            dataType	: 	'json',
            success		: 	function( result ) {
                if( result.status == 'OK' ){
                    $.snsToast('Backed up!' , true);
                }else if( result.status == 'INVALID' ){
                    $.snsToast('Something went wrong' , null , null , 'red');
                }
            }
        } );

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
                }else if( result.status == 'INVALID' ){
                    $.snsToast('Something went wrong' , true , null , 'red');
                }
            }
        } );

    };

    $history.restore = function( id ){

        $.snsToast('Restoring...');
        $.ajax ( {
            type		:	'get',
            url			: 	ajaxurl,
            data        :   { id: id , action: 'sns_backup_restore' },
            dataType	: 	'json',
            success		: 	function( result ) {
                if( result.status == 'OK' ){
                    $.snsToast('Restored!' , true);
                }else if( result.status == 'INVALID' ){
                    $.snsToast('Something went wrong' , true , null , 'red');
                }
            }
        } );

    };

    $history.configure = function(){
        $("#backup-main-content .btn-delete").click(function(){
            $history.delete( $(this).data('backup_id') , $(this) );
        });
        $("#backup-main-content .btn-restore").click(function(){
            $history.restore( $(this).data('backup_id') );
        });
    };

    $history.configureExternalRestore = function(){

        var uploader = new plupload.Uploader({

            browse_button : 'external-browse',
            container: document.getElementById('external-container'),
            url : ajaxurl,
            multipart_params: {'action':'sns_external_restore'},
            file_data_name: 'backup_file',
            multi_selection: false,
            filters : {
                mime_types: [
                    {title : "tar files", extensions : "tar"}
                ]
            },

            init: {
                PostInit: function() {
                    document.getElementById('external-restore').onclick = function() {
                        uploader.start();
                        return false;
                    };
                },

                FilesAdded: function(up, files) {
                   $("#backup-main-block .external-backup-input").val(files[0]['name']);
                },

                UploadComplete: function() {
                    if( $("#backup-main-block .external-backup-input").val() == '' ){
                        return;
                    }
                    $.snsToast('Restored!' , true);
                    $("#backup-main-block .external-backup-input").val('');

                    var control = $("#backup-main-block input[name=backup_file]");
                    control.replaceWith( control = control.clone( true ) );
                },

                BeforeUpload: function() {
                    $.snsToast('Restoring...');
                },

                Error: function(up, err) {
                    $.snsToast('Something went wrong' , true , null , 'red');
                }
            }
        });

        uploader.init();
    };

    $( document ).ready(function(){

        $('#backup-main-content #menu-tabs').tabs({
            disabled: [2,3],
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
                        }
                    } );
                }
            }
        });

        $(".sns-tooltip").tooltipster({
            content: $("#pro-label-content .pro-v-label"),
            interactive: true,
            position: 'top'
        });

        $("#backup-main-content .manual-form").submit(function(){
            $manual.save();
            return false;
        });

        $history.configure();
        $history.configureExternalRestore();

        $("#backup-main-content #restore-file").change(function(){
            $("#backup-main-content .restore-form").submit();
        });

    });



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

        if ( typeof closeTimeout != 'undefined' ) {
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
                $( 'body' ).append( obj );
                var tmpF = function (){ var aaa = 1;  alert( aaa );  aaa ++;  } ;
                obj.data( 'timeoutRef' , tmpF );
            }
            obj.css("left", (($(window).width() - obj.outerWidth()) / 2) +  $(window).scrollLeft() + "px");

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
