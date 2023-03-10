;(function ($) {
    // Hide message after 30 seconds
    const notice = document.getElementById("ic-referred-message")
    if (typeof notice !== "undefined" && notice !== null) {
        setTimeout(function () {
            notice.style.display = "none"
        }, 60000) // 1 minute
    }

    // Copy Referral Code
    const ic_button = document.querySelector("#copy-referral-code")
    const ic_input = document.querySelector("#invite_friend")
    if (typeof ic_button !== "undefined" && ic_button !== null) {
        ic_button.addEventListener("click", function () {
            if (typeof ic_input !== "undefined" && ic_input !== null) {
                console.log(ic_input.value)
                ic_input.select()
                ic_input.setSelectionRange(0, 99999)
                // navigator.clipboard.writeText(ic_input.value);
                document.execCommand("copy")
            }
        })
    } 

    $(document).ready(function() {
        $('body').on('click', '#place_order.second-btn', function(e){
            e.preventDefault();
            $('.itc-av-wrapper').addClass('active-popup')
            $(this).hide();
            $('#place_order.alt').css('cssText', 'display: block !important;');
        });

        $('.itc-second-part').hide();

        $('body').on('click', '.itc-av-wrapper.active-popup .yes', function(e){
            e.preventDefault();
            $('.itc-av-wrapper').removeClass('active-popup')
        });

        $('body').on('click', '.itc-av-wrapper.active-popup .no', function(e){
            e.preventDefault();
            // $('.itc-av-wrapper').removeClass('active-popup')
            $('.itc-first-part').hide();
            $('.itc-second-part').show();
        });
    })

})(jQuery);
