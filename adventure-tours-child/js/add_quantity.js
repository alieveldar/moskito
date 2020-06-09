jQuery(function( $ ) {
    function cartReload() {
        var data = {
            action: 'update_order_review',
            security: wc_checkout_params.update_order_review_nonce,
            post_data: $( 'form.checkout' ).serialize()
        };

        jQuery.post( add_quantity.ajax_url, data, function( response )
        {
            $( 'body' ).trigger( 'update_checkout' );
        });
    }
    $( "form.checkout" ).on( "change", "#billing_persons_count", function() {
        cartReload();
    });

    $( document ).ready(function () {
        cartReload();
        if ($('#thankyou_page')) {
            $('.header-wrap + .container').hide();
            $('.woocommerce-table--order-details tfoot tr:nth-last-child(2)').insertAfter($('.woocommerce-table--order-details tfoot tr:last-of-type'));
        }
    });
});