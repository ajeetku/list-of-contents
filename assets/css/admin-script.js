(function($) {
    $(document).ready(function() {
        $('.locp-tab').click(function() {
            var target = $(this).data('target');

            // Remove active class from all tabs and add to the clicked tab
            $('.locp-tab').removeClass('active');
            $(this).addClass('active');

            // Hide all content sections and show the one that matches the clicked tab
            $('.locp-tab-content').removeClass('active');
            $('#' + target).addClass('active');
        });
    });
})(jQuery);