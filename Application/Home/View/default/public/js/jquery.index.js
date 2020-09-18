/* ============================================================
 * jquery.index.js  首页js集合
 * ============================================================
 * Copyright 74cms.
 * ============================================================ */
!function($) {

	// 处理圆角
	if (window.PIE) { 
	    $('.pie_about').each(function() {
	        PIE.attach(this);
	    });
	}

	// 轮播广告位
	$('#slider').nivoSlider({pauseTime: 3000});
    $('#slidersmall').nivoSlider({pauseTime: 3000});

    // 名企推荐广告位滑动效果
    $(".alist").each(function() {
		$(this).hoverdir()
	})

    // 底部固定登录、注册条
    $('.slide_tip .close').on('click', function(event) {
    	$('.slide_tip').remove();
    });

    // ajax加载登录口内信息
	$.getJSON(qscms.root + '?m=Home&c=index&a=ajax_user_info',function(result){
		if(result.status == 1){
			$('#J_userWrap').html(result.data.html);
		}
	});

	// 签到
	$('#J_sign_in').live('click',function(){
    	var f = $(this);
        var byname = $(this).data('byname');
    	$.getJSON(qscms.root+'?m=Home&c=members&a=sign_in',function(result){
    		if(result.status == 1){
    			disapperTooltip("goldremind", '每天签到增加'+result.data+byname+'<span class="point">+'+result.data+'</span>');
    			f.text('已签');
    		}else{
    			disapperTooltip('remind',result.msg);
    		}
    	});
    });

    // 是否自动登录
    $('.J_expire').click(function() {
    	if ($(this).is(':checked')) {
    		$(this).val('1');
    	} else {
    		$(this).val('0');
    	}
    });

    // 回车登录
    $('.J_loginword').bind('keypress', function(event) {
		if (event.keyCode == "13") {
            $(this).closest('.type_box').find('.index_login_btn').click();
		}
	});

    // 手机动态码登录
    var regularMobile = /^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|17[0-9]{9}$|16[0-9]{9}$|19[0-9]{9}$/; // 验证手机号正则
    $('input[name="mobile"]').keyup(function(event) {
        var $thisTypeBox = $(this).closest('.type_box');
        var mobileValue = $(this).val();
        if (mobileValue.length >= 11) {
            if (mobileValue != "" && !regularMobile.test(mobileValue)) {
                $thisTypeBox.addClass('err');
                $thisTypeBox.find('.J_errbox').text('手机号码格式不正确');
                return false;
            }
            $thisTypeBox.removeClass('err');
            $thisTypeBox.find('.J_errbox').text('');
        }
    });

    // 验证是否开启
    var whetherVerify = eval($('#J_captcha_open').val());
    var verifyPhoto = false;

    // 图片验证码
    function verifyPhotoDialog() {
        var verifyCodeDialog = $(this).dialog({
            title: '请输入下图中的文字或字母',
            content: [
                '<div class="dia-captcha-item">',
                '<img src="" class="dia-captcha-img">',
                '<input type="text" name="captcha-solution" class="dia-captcha-solution" id="captcha" maxlength="10">',
                '</div>'
            ].join('')
        })
    }
    
    // 获取验证码
    $('#getVerfyCode').die().live('click', function(event) {
        var mobileValue = $.trim($('input[name="mobile"]').val());
        var $thisTypeBox = $(this).closest('.type_box');
        if (!mobileValue.length) {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('请填写手机号码');
            $('input[name="mobile"]').focus();
            return false;
        }
        if (mobileValue != "" && !regularMobile.test(mobileValue)) {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('手机号码格式不正确');
            $('input[name="mobile"]').focus();
            return false;
        }
        $.ajax({
            url: qscms.root + '?m=Home&c=Members&a=ajax_check',
            cache: false,
            async: false,
            type: 'post',
            dataType: 'json',
            data: { type: 'mobile', param: mobileValue },
            success: function(result) {
                if (!result.status) {
                    $('#J_sendVerifyType').val('1');// 标识为发验证码
                    if (whetherVerify) {
                        if (verifyPhoto) {
                            verifyPhotoDialog();
                        } else {
                            $("#btnVerifiCode").click();
                        }
                    } else {
                        validDoSomethig();
                    }
                } else {
                    $thisTypeBox.addClass('err');
                    $thisTypeBox.find('.J_errbox').text('您输入的手机号未注册会员');
                    return false;
                }
            }
        });
        
    });

	// 账号登录验证
    $('#J_do_login_btn').click(function() {
    	var usernameValue = $.trim($('input[name=username]').val());
    	var passwordValue = $.trim($('input[name=password]').val());
    	var expireValue = $.trim($('input[name=expire]').val());
        var $thisTypeBox = $(this).closest('.type_box');
    	if (usernameValue == '') {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('请输入邮箱/用户名/手机号！');
    		return false;
    	}
    	if (passwordValue == '') {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('请输入密码！');
    		return false;
    	}
    	$thisTypeBox.removeClass('err');
    	if($("#verify_userlogin").val()==1){
            $('#J_sendVerifyType').val('0');
			$("#btnVerifiCode").click();
		}else{
            $('#J_do_login_btn').val('登录中...').addClass('btn_disabled').prop('disabled', !0);
			doLogin();
		}
    });

    // 手机动态码登录验证
    $('#J_do_login_bymobile_btn').click(function() {
        var mobileValue = $.trim($('input[name=mobile]').val());
        var verfyCodeValue = $.trim($('input[name=verfy_code]').val());
        var expireValue = $.trim($('input[name=expire_obile]').val());
        var $thisTypeBox = $(this).closest('.type_box');
        if (mobileValue == '') {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('请输入手机号');
            return false;
        }
        if (mobileValue != "" && !regularMobile.test(mobileValue)) {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('手机号码格式不正确');
            $('input[name="mobile"]').focus();
            return false;
        }
        if (verfyCodeValue == '') {
            $thisTypeBox.addClass('err');
            $thisTypeBox.find('.J_errbox').text('请输入手机验证码');
            return false;
        }
        $thisTypeBox.removeClass('err');
        if($("#verify_userlogin").val()==1){
            $('#J_sendVerifyType').val('0');
            $("#btnVerifiCode").click();
        }else{
            $('#J_do_login_bymobile_btn').val('登录中...').addClass('btn_disabled').prop('disabled', !0);
            doLogin();
        }
    });

    // 配置验证码，只有在未登录情况下需要
    if (!eval($('#whetherVisitors').val())) {
        $.ajax({
            // 获取id，challenge，success（是否启用failback）
            url: qscms.root+'?m=Home&c=captcha&t=' + (new Date()).getTime(), // 加随机数防止缓存
            type: "get",
            dataType: "json",
            success: function (data) {
                // 使用initGeetest接口
                // 参数1：配置参数
                // 参数2：回调，回调的第一个参数验证码对象，之后可以使用它做appendTo之类的事件
                initGeetest({
                    gt: data.gt,
                    challenge: data.challenge,
                    product: "popup", // 产品形式，包括：float，embed，popup。注意只对PC版验证码有效
                    offline: !data.success // 表示用户后台检测极验服务器是否宕机，一般不需要关注
                }, handler);
            },
            error:function(data){
                var loginTypeValue = eval($('#J_loginType').val());
                $('.type_box').eq(loginTypeValue).addClass('err');
                $('.type_box').eq(loginTypeValue).find('.J_errbox').text(data['responseText']);
                $('.type_box').eq(loginTypeValue).find('.index_login_btn').val('无法完成登录');
            }
        });

        // 验证码加载
        var handler = function(captchaObj) {
            captchaObj.appendTo("#popup-captcha");
            captchaObj.bindOn("#btnVerifiCode");
            captchaObj.onSuccess(function() {
                validDoSomethig();
            });
            captchaObj.onFail(function() {
                var loginTypeValue = eval($('#J_loginType').val());
                $('.type_box').eq(loginTypeValue).addClass('err');
                $('.type_box').eq(loginTypeValue).find('.J_errbox').text('滑动验证失败！');
            });
            captchaObj.onError(function() {

            });
            captchaObj.getValidate()
        };
    }

    // 账号登录方法
    function doLogin() {
        var loginTypeValue = eval($('#J_loginType').val());
        $('.type_box').eq(loginTypeValue).find('.index_login_btn').val('正在登录中...');
        if (loginTypeValue) {
            var mobileValue = $.trim($('input[name=mobile]').val());
            var verfyCodeValue = $.trim($('input[name=verfy_code]').val());
            var expireValue = $.trim($('input[name=expire_obile]').val());
            var $thisTypeBox = $('#J_do_login_bymobile_btn').closest('.type_box');
            // 提交表单
            $.ajax({
                url: qscms.root+'?m=Home&c=Members&a=login',
                type: "post",
                dataType: "json",
                data: {
                    mobile: mobileValue,
                    mobile_vcode: verfyCodeValue,
                    expire: expireValue,
                    // 二次验证所需的三个值
                    geetest_challenge: $("input[name='geetest_challenge']").val(),
                    geetest_validate: $("input[name='geetest_validate']").val(),
                    geetest_seccode: $("input[name='geetest_seccode']").val()
                },
                success: function (result) {
                    if (parseInt(result.status)) {
                        // ajax加载登录口内信息
                        $.getJSON(qscms.root + '?m=Home&c=Index&a=ajax_user_info',function(result){
                            if(result.status == 1){
                                $('#J_userWrap').html(result.data.html);
                                $.getJSON(qscms.root + "?m=Home&c=AjaxCommon&a=get_header_min",function(result){
                                    if(result.status == 1){
                                        $('#J_header').html(result.data.html);
                                    }
                                });
                            }
                        });
                    } else {
                        $thisTypeBox.addClass('err');
                        $thisTypeBox.find('.J_errbox').text(result.msg);
                        $('#J_do_login_bymobile_btn').val('登录').removeClass('btn_disabled').prop('disabled', 0);
                        $("#verify_userlogin").val(result.data);
                    }
                }
            });
        } else {
            var usernameValue = $.trim($('input[name=username]').val());
            var passwordValue = $.trim($('input[name=password]').val());
            var expireValue = $.trim($('input[name=expire]').val());
            var $thisTypeBox = $('#J_do_login_btn').closest('.type_box');
            // 提交表单
            $.ajax({
                url: qscms.root+'?m=Home&c=Members&a=login',
                type: "post",
                dataType: "json",
                data: {
                    username: usernameValue,
                    password: passwordValue,
                    expire: expireValue,
                    // 二次验证所需的三个值
                    geetest_challenge: $("input[name='geetest_challenge']").val(),
                    geetest_validate: $("input[name='geetest_validate']").val(),
                    geetest_seccode: $("input[name='geetest_seccode']").val()
                },
                success: function (result) {
                    if(result.msg == 5){
                        window.location.href = result.data;
                    }else{
                        if (parseInt(result.status)) {
                            // ajax加载登录口内信息
                            $.getJSON(qscms.root + '?m=Home&c=Index&a=ajax_user_info',function(result){
                                if(result.status == 1){
                                    $('#J_userWrap').html(result.data.html);
                                    $.getJSON(qscms.root + "?m=Home&c=AjaxCommon&a=get_header_min",function(result){
                                        if(result.status == 1){
                                            $('#J_header').html(result.data.html);
                                        }
                                    });
                                }
                            });
                        } else {
                            $thisTypeBox.addClass('err');
                            $thisTypeBox.find('.J_errbox').text(result.msg);
                            $('#J_do_login_btn').val('登录').removeClass('btn_disabled').prop('disabled', 0);
                            $("#verify_userlogin").val(result.data);
                        }
                    }
                }
            });
        }
    }

    // 极验通过之后需要做的操作
    function validDoSomethig() {
        var toType = eval($('#J_sendVerifyType').val());
        var loginTypeValue = eval($('#J_loginType').val());
        if (toType) { // 1为发送验证码
            var mobileValue = $.trim($('input[name=mobile]').val());
            $.ajax({
                url: qscms.root + '?m=Home&c=Members&a=reg_send_sms',
                cache: false,
                async: false,
                type: 'post',
                dataType: 'json',
                data: { sms_type: 'login', mobile: mobileValue,geetest_challenge: $("input[name='geetest_challenge']").val(),geetest_validate: $("input[name='geetest_validate']").val(),geetest_seccode: $("input[name='geetest_seccode']").val()},
                success: function(result) {
                    if (result.status) {
                        $('.type_box').eq(loginTypeValue).removeClass('err');
                        disapperTooltip("success", "验证码已发送，请注意查收");
                        // 开始倒计时
                        var countdown = 180;
                        function settime() {
                            if (countdown == 0) {
                                $('#getVerfyCode').prop("disabled", 0);
                                $('#getVerfyCode').removeClass('btn_disabled');
                                $('#getVerfyCode').val('获取验证码');
                                countdown = 180;
                                return;
                            } else {
                                $('#getVerfyCode').prop("disabled", !0);
                                $('#getVerfyCode').addClass('btn_disabled');
                                $('#getVerfyCode').val('重新发送' + countdown + '秒');
                                countdown--;
                            }
                            setTimeout(function() { 
                                settime()
                            },1000)
                        }
                        settime();
                    } else {
                        $('.type_box').eq(loginTypeValue).addClass('err');
                        $('.type_box').eq(loginTypeValue).find('.J_errbox').text(result.msg);
                        return false;
                    }
                }
            });
        } else {
            doLogin();
        }
    }

	// 顶部搜索类型切换
    $('.selecttype').click(function() {
    	$(this).closest('.inputbg').toggleClass('open');
		$('.selecttype_down .down_list').off().click(function() {
			var type = $(this).data('type');
			var hiddenType = $('#top_search_type').val();
			var txt = $(this).text();
			var originalTxt = $('.selecttype').text();
			$('.selecttype').text(txt);
			$(this).text(originalTxt);
			$(this).data('type', hiddenType);
			$('#top_search_type').val(type);
		});
    	$(document).on('click', function(e) {
			var target  = $(e.target);
			if (target.closest(".selecttype").length == 0) {
				$('.inputbg').removeClass('open');
			};
		});
    });

    // 顶部回车搜索
	$('#top_search_input').bind('keypress', function(event) {
		if (event.keyCode == "13") {
			$("#top_search_btn").click();
		}
	});

    // 顶部搜索跳转
    $('#top_search_btn').click(function() {
        $('#top_search_input').val(htmlspecialchars($('#top_search_input').val()));
        var post_data = $('#ajax_search_location').serialize();
        if(qscms.keyUrlencode==1){
            post_data = encodeURI(post_data);
        }
        $.post(qscms.root + '?m=Home&c=Index&a=search_location',post_data,function(result){
            if(result.status == 1){
                window.location=result.data;
            }
        },'json')
        return !1;
    });

    // 换一批  ajax_loading  
    var isDone = true; // 防止重复点击
    var ajaxpage = {};
    $('.J_change_batch').click(function() {
    	var $thisParent = $(this).closest('.J_change_parent');
    	var $url = $(this).data('url');
    	$thisParent.addClass('open_ajax');
    	if (isDone) {
    		isDone = false;
            if(!ajaxpage[$url]) ajaxpage[$url] = 2;
        	$.getJSON($url, {p: ajaxpage[$url]}, function(result) {
        		if(result.status == 1){
                    $thisParent.find('.J_change_result').html(result.data.html);
                    isDone = true;
                    if(result.data.isfull){
                        ajaxpage[$url] = 1;
                    }else{
                        ajaxpage[$url]++;
                    }
                }
                $thisParent.removeClass('open_ajax');
        	});
    	};
    });

    // 新闻资讯切换
    var isDoneNews = true; // 防止重复点击
    $('.J_news_list_title').click(function() {
        var $cid = $(this).attr('cid');
        $(this).addClass('select').siblings().removeClass('select');
        $('.J_news_content').find('.ajax_loading').show();
        if (isDoneNews) {
            isDoneNews = false;
            $.getJSON(qscms.root + '?m=Home&c=AjaxCommon&a=news_list', {type_id: $cid}, function(data) {
                $('.J_news_content').find('.J_news_txt').html(data.data);
                isDoneNews = true;
                $('.J_news_content').find('.ajax_loading').hide();
            });
        };
    });

    // 登录方式切换
    $('#forMobileLogin').click(function() {
        var thisIndex = $(this).data('index');
        $('#J_loginType').val(thisIndex);
        $('.switch_txt').eq(thisIndex).addClass('active').siblings('.switch_txt').removeClass('active');
        $('.type_box').eq(thisIndex).addClass('active').siblings('.type_box').removeClass('active');
        $('#forAccountLogin').show();
    });

    $('#forAccountLogin').click(function() {
        var thisIndex = $(this).data('index');
        $('#J_loginType').val(thisIndex);
        $('.switch_txt').eq(thisIndex).addClass('active').siblings('.switch_txt').removeClass('active');
        $('.type_box').eq(thisIndex).addClass('active').siblings('.type_box').removeClass('active');
        $('#forAccountLogin').hide();
    });

    // 计算首页广告位切换条的位置
    $('.nivo-controlNav').each(function(index, el) {
        var controlLength = $(this).find('.nivo-control').length;
        $(this).find('.nivo-control').eq(controlLength - 1).css('margin-right', 0);
        var thisWidth = $(this).outerWidth();
        var parentWidth = $(this).closest('.nivoSlider').outerWidth();
        $(this).css('left', (parentWidth - thisWidth) / 2);
    });
    $('.nivo-prevNav').each(function(index, el) {
        var thisHeight = $(this).outerHeight();
        var parentHeight = $(this).closest('.nivoSlider').outerHeight();
        $(this).css('top', (parentHeight - thisHeight) / 2);
    });
    $('.nivo-nextNav').each(function(index, el) {
        var thisHeight = $(this).outerHeight();
        var parentHeight = $(this).closest('.nivoSlider').outerHeight();
        $(this).css('top', (parentHeight - thisHeight) / 2);
    });
}(window.jQuery);