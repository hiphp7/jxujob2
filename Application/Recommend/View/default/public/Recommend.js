!function($) {
    var pluTemplate = [
        '<div class="plugin-recommend-box">',
        '<div class="prb-logo"></div>',
        '<a href="javascript:;" class="prb-close plu-rec-close"></a>',
        '<div class="prb-container">',
        '<div class="prb-group">',
        '<div id="plu-ajax-box"></div>',
        '<div class="clear"></div>',
        '</div>',
        '<div class="prb-h26"></div>',
        '<div class="prb-btn-group">',
        '<a href="javascript:;" class="prb-btn b1 J_change_batch">换一批</a>',
        '<div class="h-w39"></div>',
        '<a href="javascript:;" class="prb-btn b2 plu-rec-close">取消</a>',
        '<div class="clear"></div>',
        '</div>',
        '</div>',
        '</div>'
    ].join('');
    $.getJSON(qscms.root + '?m=Recommend&c=index&a=get_recommend', function(result) {
        if(result.status == 1 && result.data.html){
            var recDialog = $(this).dialog({
                header: false,
                content: pluTemplate,
                border: false,
                innerPadding: false,
                footer: false,
                nobgColor: true
            });
            $('#plu-ajax-box').html(result.data.html);
            var $thisDialog = $('.plugin-recommend-box').closest('.modal_dialog');
            $thisDialog.css({
                left: ($(window).width() - $thisDialog.outerWidth())/2,
                top: ($(window).height() - $thisDialog.outerHeight())/2 + $(document).scrollTop()
            });
            $('.plu-rec-close').die().live('click', function() {
                setCookie('recommend_cancel', '1');
                /*$.getJSON(qscms.root + '?m=Recommend&c=index&a=cancel_recommend', function(result) {
                    if(result.status == 1){
                        console.log(result.msg);
                    }
                });*/
                $(this).closest('.modal').remove();
                $('.modal_backdrop').remove();
            })
        }
    });
    // 写cookies
    function setCookie(name, value) {
        var days = 1;
        var exp = new Date();
        exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
    }
    var p=2,isDone = true; // 防止重复点击
    // 换一批
    $('.J_change_batch').die().live('click', function() {
        if (isDone) {
            isDone = false;
            if(!p) p = 2;
            $.getJSON(qscms.root + '?m=Recommend&c=index&a=get_recommend',{p: p}, function(result) {
                if(result.status == 1){
                    if(result.data.isfull){
                        p = 1;
                    }else{
                        p++;
                    }
                    $('#plu-ajax-box').html(result.data.html);
                }
                isDone = true;
            });
        };
    });
}(window.jQuery);