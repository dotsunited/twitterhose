(function($, window) {
    $(function() {
        window.WEB_SOCKET_SWF_LOCATION = "/assets/swf/WebSocketMain.swf";

        var log = function(msg) {
            $('#messages').append(msg+'\n');
        };

        var ws = new WebSocket("ws://localhost:1337/");

        ws.onerror = function(e) {
            log('Error', e);
        };

        ws.onopen = function() {
            log('Connected');
            $('#sendmessage').on('submit', function(e) {
                e.preventDefault();

                ws.send($('#message', this).val());
                $('#message', this).val('');
            });
        };

        ws.onmessage = function(e) {
            log('Message: '+e.data);
        };

        ws.onclose = function() {
            log('Disconnected');
        };
    });
})(jQuery, window);
