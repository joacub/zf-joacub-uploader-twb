/*
 * jQuery File Upload Plugin JS Example 7.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */
$(function () {
    'use strict';

    $('.fileupload-handler').each(function() {
    	
    	var $t = $(this);
    	
    	var options = {
			destroy: function (e, data) {
				data.dataType = 'json';
                var that = $(this).data('blueimp-fileupload') ||
                        $(this).data('fileupload');
                if (data.url) {
                    $.ajax(data).done(function (result) {
                    	
                    	if(!result.success) {
                    		$(this).find('.error-on-delete').html('<span class="label label-important">Error</span> ' + result.error);
                    		return false;
                    	}
                    	
                        that._transition(data.context).done(
                            function () {
                                $(this).remove();
                                that._trigger('destroyed', e, data);
                            }
                        );
                    });
                }
            }
    	};
    	
    	$.extend(options, optionsZfJoacubUploaderTwb[$t.prop('id')]);
    	
    	// Initialize the jQuery File Upload widget:
        $t.fileupload(options);

        // Enable iframe cross-domain access via redirect option:
        $t.fileupload(
            'option',
            'redirect',
            window.location.href.replace(
                /\/[^\/]*$/,
                '/cors/result.html?%s'
            )
        );

        // Upload server status check for browsers with CORS support:
        if ($.support.cors) {
            $.ajax({
                url: $t.fileupload('option', 'url'),
                type: 'HEAD'
            }).fail(function () {
                $('<span class="alert alert-error"/>')
                    .text('Subir servidor no está disponible - ' +
                            new Date())
                    .appendTo($t);
            });
        }
        // Load existing files:
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: $t.fileupload('option', 'url'),
            dataType: 'json',
            context: $t[0]
        }).done(function (result) {
            $t.fileupload('option', 'done')
                .call(this, null, {result: result});
        });
            
    	
    });
    

});
