jQuery(document).ready(function($) {
    $('form.checkout').on('submit', function(event) {
        event.preventDefault(); // prevent default form submission
        
        var form_data = $(this).serialize(); // get form data
        var ajax_url = icPopupObj.ajaxUrl; // your AJAX URL
        
        // send AJAX request
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: form_data,
            success: function(response) {
                if (response.result === 'success') { // check if AJAX response is successful
                    // create and display popup
                    var popup_html = '<div class="my-popup"><p>Your order has been placed successfully!</p></div>';
                    $('body').append(popup_html);
                    
                    // add close button to popup
                    $('.my-popup').append('<button class="close-button">Close</button>');
                    
                    // close popup when close button is clicked
                    $('.close-button').on('click', function() {
                        $('.my-popup').remove();
                    });
                } else {
                    $('form.checkout').submit(); // submit the form again if AJAX response is not successful
                }
            },
            error: function(xhr, status, error) {
                console.log(error); // log any errors
            }
        });
    });
});
