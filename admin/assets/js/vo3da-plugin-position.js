(function ($) {
    'use strict';

    $(function () {
        $('#toplevel_page_vo3da-plugins li.wp-first-item').remove();
        $('#toplevel_page_vo3da-plugins').find('a.toplevel_page_vo3da-plugins').attr('href', '#');
    });

})(jQuery);