jQuery(document).ready(function($) {
    setTimeout(function() {
        $('.img-panda-toast').css({
            'transition': 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)',
            'opacity': '0',
            'transform': 'translateX(100%)'
        });
        setTimeout(function() {
            $('.img-panda-toast').remove();
        }, 500);
    }, 5000); // 5 seconds
});
