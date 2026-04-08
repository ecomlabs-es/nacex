(function($) {
    // START of plugin definition
    $.fn.showModalDialog = function(options) {

        // build main options and merge them with default ones
        var optns = $.extend({}, $.fn.showModalDialog.defaults, options);

        // create the iframe which will open target page
        var $frame = $('<iframe />');
        $frame.attr({
            'src': optns.url,
            'scrolling': optns.scrolling
        });

        // set the padding to 0 to eliminate any padding, 
        // set padding-bottom: 10 so that it not overlaps with the resize element
        $frame.css({
            'padding': 0,
            'margin': 0,
            'padding-bottom': 10
        });

        // create jquery dialog using recently created iframe
        var $modalWindow = $frame.dialog({
            autoOpen: true,
            modal: true,
            width: optns.width,
            height: optns.height,
            resizable: optns.resizable,
            position: optns.position,
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            close: function() {
                // save the returnValue in options so that it is available in the callback function
                optns.returnValue = $frame[0].contentWindow.window.returnValue;
                optns.onClose();
            },
            resizeStop: function() { $frame.css("width", "100%"); }
        });

        // set the width of the frame to 100% right after the dialog was created
        // it will not work setting it before the dialog was created
        $frame.css("width", "100%");

        // pass dialogArguments to target page
        $frame[0].contentWindow.window.dialogArguments = optns.dialogArguments;
        // override default window.close() function for target page
        $frame[0].contentWindow.window.close = function() { $modalWindow.dialog('close'); };

        $frame.load(function() {
            if ($modalWindow) {
                
                var maxTitleLength = 50; // max title length
                var title = $(this).contents().find("title").html(); // get target page's title

                if (title.length > maxTitleLength) {
                    // trim title to max length
                    title = title.substring(0, maxTitleLength) + '...';
                }

                // set the dialog title to be the same as target page's title
                $modalWindow.dialog('option', 'title', title);
            }
        });

        return null;
    };

    // plugin defaults
    $.fn.showModalDialog.defaults = {
        url: null,
        dialogArguments: null,
        height: 'auto',
        width: 'auto',
        position: 'center',
        resizable: true,
        scrolling: 'yes',
        onClose: function() { },
        returnValue: null
    };
    // END of plugin
})(jQuery);

// do so that the plugin can be called $.showModalDialog({options}) instead of $().showModalDialog({options})
jQuery.showModalDialog = function(options) { $().showModalDialog(options); };
