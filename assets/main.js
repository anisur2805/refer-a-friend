;(function ($) {
    // Hide message after 30 seconds
    const notice = document.getElementById("ic-referred-message")
    if (typeof notice !== "undefined" && notice !== null) {
        setTimeout(function () {
            notice.style.display = "none"
        }, 30000) // 30 seconds
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

    $(document).ready(function () {
        $("#send_email").click(function (e) {
            e.preventDefault();
            var data = {
                action: "ic_send_email",
            }
            $.post( icAjaxHandler.ajaxUrl, data, function (response) {})

            // var to = "anisur2805@gmail.com"
            // var subject = "Test Email"
            // var message = "This is a test email sent from WordPress."
            // var body = encodeURIComponent(message)
            // var mailtoLink =
            //     "mailto:" + to + "?subject=" + subject + "&body=" + body
            // window.open(mailtoLink)
        });
    })
})(jQuery);
