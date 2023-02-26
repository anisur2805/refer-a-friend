;(function($){
    // Hide message after 30 seconds
    const notice = document.getElementById('ic-referred-message');
    if( typeof( notice ) !== 'undefined' && notice !== null ) {
        setTimeout(function() {
            notice.style.display = 'none';
        }, 30000); // 30 seconds
    }

    // Copy Referral Code
    const ic_button = document.querySelector('#copy-referral-code')
    const ic_input = document.querySelector('#invite_friend')
    if( typeof( ic_button) !== 'undefined' && ic_button !== null ) {
        ic_button.addEventListener('click', function() {
            if( typeof( ic_input) !== 'undefined' && ic_input !== null ) {
                console.log( ic_input.value )
                ic_input.select();
                // ic_input.setSelectionRange(0, 99999);
                // navigator.clipboard.writeText(ic_input.value);
                document.execCommand("copy");
            }
            
        })
    }

    $(document).ready(function() {
        $('#send-email').click(function() {
          $.ajax({
            type: "POST",
            url: icAjaxHandler.ajaxUrl,
            action: 'ic_send_email',
            success: function(data) {
              alert(data); // Replace this with any code you want to run when the email is sent successfully
            }
          });
        });
      });
})(jQuery);