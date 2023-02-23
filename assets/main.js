;(function($){
    // Hide message after 30 seconds
    const notice = document.getElementById('ic-referred-message');
    if( typeof( notice ) !== 'undefined' && notice !== null ) {
        setTimeout(function() {
            notice.style.display = 'none';
        }, 30000); // 30 seconds
    }
})(jQuery);