;(function ($) {
    
    $(document).ready(function () {
        $( "table.wp-list-table.subscribers .delete" ).on( "click", "form", function ( e ) {

            e.preventDefault();
            alert("hello")
            if( !confirm( icAjaxHandler.confirm ) ) return;
            var self = $( this ),
                id = $('.delete-point').data( 'point-id' );

                var self = $( this ),
                data = {
                    id: id
                };

                console.log( id )

            $.post( icAjaxHandler.ajaxUrl, data, function ( response ) {

                if ( response.success ) {
                    if ( response.success ) {
                        // self.find( "p.hide" ).addClass( "success" );
                        // self[0].reset();
                    }
                }
                
            } ).fail( function ( e ) {
                console.log( icAjaxHandler.error, e );
            } );

        } )


    });
})(jQuery);
