(function($){
    'use strict';
    $(function(){
        const $start = $('#start_date');
        const $end = $('#end_date');
        if ($start.length && $end.length) {
            $start.on('change', function(){ $('#filter-form').submit(); });
            $end.on('change', function(){ $('#filter-form').submit(); });
        }
    });
})(jQuery);
