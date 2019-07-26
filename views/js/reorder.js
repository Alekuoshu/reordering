//--------------------------------------
//Reorder behavior
//--------------------------------------
(function($) {
    $(document).ready(function() {
        // ------------------------
        // init
        // ------------------------
        var target = $('.reoder-block');
        if (!target.length) return;

        // ------------------------
        // events
        // ------------------------
        var id_last_order = target.find('#id_last_order').val();
        if (id_last_order) {
            var init_url = $('.reorder-item a').attr('href');
            var newUrl = init_url + id_last_order;
            $('.reorder-item a').attr('href', newUrl);
            // alert(newUrl);
        } else {
            $('.reorder-item a').on('click', function(e) {
                e.preventDefault();
                console.log('Usted no tiene pedidos creados anteriormente!');
                alert('Usted no tiene pedidos creados anteriormente!');

            });
        }

    });

})(jQuery);