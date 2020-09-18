 /* ============================================================
 * jquery.validate.regpersonal.js 个人注册验证
 * ============================================================
 * Copyright 74cms.
 * ============================================================ */

(function($) {
    'use strict';

    // 自定义验证方法，验证是否被注册
    $.validator.addMethod('IsRegistered', function(value, element) {
        var result = false, eletype = element.name;
        $.ajax({
            url: qscms.root + '?m=Home&c=Members&a=ajax_check',
            cache: false,
            async: false,
            type: 'post',
            dataType: 'json',
            data: { type: eletype, param: value },
            success: function(json) {
                if (json && json.status) {
                    result = true;
                } else {
                    result = false;
                }
            }
        });
        return result;
    }, '已被注册');

    // 点击获取验证码先判断是否输入了手机号
    var regularMobile = /^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|17[0-9]{9}$|16[0-9]{9}$|19[0-9]{9}$/;
    $('#J_getverificode').click(function() {
        var mobileValue = $.trim($('#mobile').val());
        // 获取后台发送验证码配置
        var captcha_open = eval($('#J_captcha_open').val());
        if (mobileValue == '') {
            disapperTooltip("remind", "请输入手机号码");
            $('#mobile').focus();
            return false;
        };
        if (mobileValue != "" && !regularMobile.test(mobileValue)) {
            disapperTooltip("remind", "请输入正确的手机号码");
            $('#mobile').focus();
            return false;
        }
        $.ajax({
            url: qscms.root + '?m=Home&c=Members&a=ajax_check',
            cache: false,
            async: false,
            type: 'post',
            dataType: 'json',
            data: { type: 'mobile', param: mobileValue },
            success: function(json) {
                if (json && json.status) {
                    if (captcha_open) {
                        $("#btnCheck").click();
                    } else {
                        toSetSms();
                    }
                } else {
                    disapperTooltip("remind", "该手机号已被注册，请尝试登录");
                    $('#mobile').focus();
                    return false;
                }
            }
        });
    });

    // 获取后台注册验证配置
    var config_varify_reg = eval($('#J_config_varify_reg').val());

    // 个人手机注册验证程序
    $("#regMobileForm").validate({
        submitHandler: function(form) {
            if (!$('#regMobileForm input[name="agreement"]').is(':checked')) {
                disapperTooltip("remind", '请同意注册协议');
                return false;
            }
            if (config_varify_reg) {
                $("#btnCheckMobile").click();
            } else {
                regPerByMobileHandler();
            }
        },
        rules: {
            mobile: {
                required: true, 
                match: /^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|17[0-9]{9}$|16[0-9]{9}$|19[0-9]{9}$/,
                IsRegistered: true
            },
            mobile_vcode: {
                required: true,
                match: /\d{6}$/
            },
            password: {
                required: true,
                rangelength: [6, 16]
            },
            passwordVerify: {
                required: true,
                rangelength: [6, 16],
                equalTo: "#password"
            }
        },
        messages: {
            mobile: {
                required: '<div class="ftxt">请输入手机号码</div><div class="fimg"></div>',
                match: '<div class="ftxt">手机号格式不正确</div><div class="fimg"></div>',
                IsRegistered: '<div class="ftxt">该手机号已被注册，请尝试登录</div><div class="fimg"></div>'
            },
            mobile_vcode: {
                required: '<div class="ftxt">请输入验证码</div><div class="fimg"></div>',
                match: '<div class="ftxt">手机验证码为6位纯数字</div><div class="fimg"></div>'
            },
            password: {
                required: '<div class="ftxt">请输入密码</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">密码长度要求为6-16个字符</div><div class="fimg"></div>'
            },
            passwordVerify: {
                required: '<div class="ftxt">请输入确认密码</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">密码长度要求为6-16个字符</div><div class="fimg"></div>',
                equalTo: '<div class="ftxt">两次输入的密码不一致</div><div class="fimg"></div>'
            }
        },
        errorClasses: {
            mobile: {
                required: 'tip err',
                match: 'tip err',
                IsRegistered: 'tip err'
            },
            mobile_vcode: {
                required: 'tip err',
                match: 'tip err'
            },
            password: {
                required: 'tip err',
                rangelength: 'tip err',
                match: 'tip err'
            },
            passwordVerify: {
                required: 'tip err',
                rangelength: 'tip err',
                equalTo: 'tip err'
            }
        },
        tips: {
            /* mobile: '<div class="ftxt">手机号可用于登录网站和找回密码</div><div class="fimg"></div>', */
            mobile_vcode: '<div class="ftxt">请输入手机验证码</div><div class="fimg"></div>',
            password: '<div class="ftxt">6-16位字符组成，区分大小写</div><div class="fimg"></div>',
            passwordVerify: '<div class="ftxt">请再次输入密码</div><div class="fimg"></div>'
        },
        tipClasses: {
            mobile: 'tip',
            mobile_vcode: 'tip',
            password: 'tip',
            passwordVerify: 'tip'
        },
        errorElement: 'div',
        errorPlacement: function(error, element) {
            if (element.attr('name') == 'mobile_vcode') {
                element.closest('.J_validate_group').find('.J_showtip_box').append(error);
            }  else {
                element.closest('.J_validate_group').find('.J_showtip_box').append(error);
            }
        },
        success: function(label) {
            label.append('<div class="ok"></div>');
        }
    });

    // 个人邮箱注册验证程序
    $("#regEmailForm").validate({
        submitHandler: function(form) {
            if (!$('#regEmailForm input[name="agreement"]').is(':checked')) {
                disapperTooltip("remind", '请同意注册协议');
                return false;
            }
            if (config_varify_reg) {
                $("#btnCheckEmail").click();
            } else {
                regPerByEmailHandler();
            }
        },
        rules: {
            username: {
                required: true,
                match: /^(?=[\u4e00-\u9fa5a-zA-Z])(?!\d+)[\u4e00-\u9fa5\w.]{6,18}$/,
                IsRegistered: true
            },
            email: {
                required: true,
                email: true,
                IsRegistered: true
            },
            emailpassword: {
                required: true,
                rangelength: [6, 16]
            },
            emailpasswordVerify: {
                required: true,
                rangelength: [6, 16],
                equalTo: "#emailpassword"
            }
        },
        messages: {
            username: {
                required: '<div class="ftxt">请输入用户名</div><div class="fimg"></div>',
                match: '<div class="ftxt">中英文开头6-18位，无特殊符号</div><div class="fimg"></div>',
                IsRegistered: '<div class="ftxt">该用户名已被注册</div><div class="fimg"></div>'
            },
            email: {
                required: '<div class="ftxt">请输入邮箱</div><div class="fimg"></div>',
                email: '<div class="ftxt">邮箱格式不正确</div><div class="fimg"></div>',
                IsRegistered: '<div class="ftxt">该邮箱地址已被注册，请尝试登录</div><div class="fimg"></div>'
            },
            emailpassword: {
                required: '<div class="ftxt">请输入密码</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">密码长度要求为6-16个字符</div><div class="fimg"></div>'
            },
            emailpasswordVerify: {
                required: '<div class="ftxt">请输入确认密码</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">密码长度要求为6-16个字符</div><div class="fimg"></div>',
                equalTo: '<div class="ftxt">两次输入的密码不一致</div><div class="fimg"></div>'
            }
        },
        errorClasses: {
            username: {
                required: 'tip err',
                match: 'tip err',
                IsRegistered: 'tip err'
            },
            email: {
                required: 'tip err',
                email: 'tip err',
                IsRegistered: 'tip err'
            },
            emailpassword: {
                required: 'tip err',
                rangelength: 'tip err'
            },
            emailpasswordVerify: {
                required: 'tip err',
                rangelength: 'tip err',
                equalTo: 'tip err'
            }
        },
        tips: {
            username: '<div class="ftxt">中英文开头6-18位，无特殊符号</div><div class="fimg"></div>',
            email: '<div class="ftxt">邮箱用于接收简历及系统重要通知</div><div class="fimg"></div>',
            emailpassword: '<div class="ftxt">6-16位字符组成，区分大小写</div><div class="fimg"></div>',
            emailpasswordVerify: '<div class="ftxt">请再次输入密码</div><div class="fimg"></div>'
        },
        tipClasses: {
            username: 'tip',
            email: 'tip',
            emailpassword: 'tip',
            emailpasswordVerify: 'tip'
        },
        errorElement: 'span',
        errorPlacement: function(error, element) {
            element.closest('.J_validate_group').find('.J_showtip_box').append(error);
        },
        success: function(label) {
            label.append('<div class="ok"></div>');
        }
    });

    var register = {
        initialize: function() {
            this.initControl();
        },
        initControl: function() {
            // 手机注册提交
            $('#btnMoilbPhoneRegister').die().live('click', function() {
                $(this).submitForm({
                    beforeSubmit: $.proxy(frmMobilValid.form, frmMobilValid),
                    success: function(data) {
                        if (data.status) {
                            window.location.href = data.data.url;
                        } else {
                            $('#btnMoilbPhoneRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                            disapperTooltip("remind", data.msg);
                        }
                    },
                    clearForm: false
                });
                if (frmMobilValid.valid()) {
                    $('#btnMoilbPhoneRegister').val('注册中...').addClass('btn_disabled').prop('disabled', !0);
                }
                return false;
            });

            // 邮箱注册提交
            $('#btnEmailRegister').die().live('click', function() {
                $(this).submitForm({
                    beforeSubmit: $.proxy(frmEmailValid.form, frmEmailValid),
                    success: function(data) {
                        if (data.status) {
                            window.location.href = data.data.url;
                        } else {
                            $('#btnEmailRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                            disapperTooltip("remind", data.msg);
                        }
                    },
                    clearForm: false
                });
                if (frmEmailValid.valid()) {
                    $('#btnEmailRegister').val('注册中...').addClass('btn_disabled').prop('disabled', !0);
                }
                return false;
            });
        }
    }

    // 发送手机验证码
    function toSetSms() {
        function settime(countdown) {
            if (countdown == 0) {
                $('#J_getverificode').prop("disabled", 0);
                $('#J_getverificode').removeClass('btn_disabled hover');
                $('#J_getverificode').val('获取验证码');
                countdown = 180;
                return;
            } else {
                $('#J_getverificode').prop("disabled", !0);
                $('#J_getverificode').addClass('btn_disabled');
                $('#J_getverificode').val('重新发送' + countdown + '秒');
                countdown--;
            }
            setTimeout(function() {
                settime(countdown)
            },1000)
        }
        $.ajax({
            url: qscms.root+'?m=Home&c=Members&a=reg_send_sms',
            type: 'POST',
            dataType: 'json',
            data: {mobile: $.trim($('#mobile').val()),geetest_challenge: $("input[name='geetest_challenge']").val(),geetest_validate: $("input[name='geetest_validate']").val(),geetest_seccode: $("input[name='geetest_seccode']").val()}
        })
        .done(function(data) {
            if (parseInt(data.status)) {
                setTimeout(function() { 
                    disapperTooltip("success", "验证码已发送，请注意查收");
                    // 开始倒计时
                    var countdowns = 180;
                    settime(countdowns);
                },800)
            } else {
                setTimeout(function() { 
                    disapperTooltip("remind", data.msg);
                },1500)
            }
        });
    }

    // 个人手机注册处理程序
    function regPerByMobileHandler() {
        $('#btnMoilbPhoneRegister').val('注册中...').addClass('btn_disabled').prop('disabled', !0);
        $.ajax({
            url: qscms.root+'?m=Home&c=Members&a=register',
            type: 'POST',
            dataType: 'json',
            data: $('#regMobileForm').serialize(),
            success: function (data) {
                if(data.status == 1){
                    window.location.href = data.data.url;
                }else{
                    if ($('#regMobileForm input[name="agreement"]').is(':checked')) {
                        $('#btnMoilbPhoneRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                    }
                    disapperTooltip("remind", data.msg);
                }
            },
            error:function(data){
                if ($('#regMobileForm input[name="agreement"]').is(':checked')) {
                    $('#btnMoilbPhoneRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                }
                disapperTooltip("remind", data.msg);
            }
        });
    }

    // 个人邮箱注册处理程序
    function regPerByEmailHandler() {
        $('#btnEmailRegister').val('注册中...').addClass('btn_disabled').prop('disabled', !0);
        $.ajax({
            url: qscms.root+'?m=Home&c=Members&a=register',
            type: 'POST',
            dataType: 'json',
            data: $('#regEmailForm').serialize(),
            success: function (data) {
                if(data.status == 1){
                    window.location.href = data.data.url;
                }else{
                    if ($('#regEmailForm input[name="agreement"]').is(':checked')) {
                        $('#btnEmailRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                    }
                    disapperTooltip("remind", data.msg);
                }
            },
            error:function(data){
                if ($('#regEmailForm input[name="agreement"]').is(':checked')) {
                    $('#btnEmailRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                }
                disapperTooltip("remind", data.msg);
            }
        });
    }

    // 手机发送验证码配置极验
    if (parseInt(qscms.smsTatus)) {
        $.ajax({
            // 获取id，challenge，success（是否启用failback）
            url: qscms.root+'?m=Home&c=Captcha&t=' + (new Date()).getTime(), // 加随机数防止缓存
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
            }
        });
        var handler = function(captchaObj) {
            captchaObj.appendTo("#popup-captcha");
            captchaObj.bindOn("#btnCheck");
            captchaObj.onSuccess(function() {
                toSetSms();
            });
            captchaObj.onFail(function() {

            });
            captchaObj.onError(function() {

            });
            captchaObj.getValidate()
        };
    }

    // 通过手机注册配置极验
    if (config_varify_reg && parseInt(qscms.smsTatus)) {
        $.ajax({
            // 获取id，challenge，success（是否启用failback）
            url: qscms.root+'?m=Home&c=Captcha&t=' + (new Date()).getTime(), // 加随机数防止缓存
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
                }, handlerRegMobile);
            }
        });
        var handlerRegMobile = function(captchaObj) {
            captchaObj.appendTo("#popup-captcha-mobile");
            captchaObj.bindOn("#btnCheckMobile");
            captchaObj.onSuccess(function() {
                regPerByMobileHandler();
            });
            captchaObj.onFail(function() {
                
            });
            captchaObj.onError(function() {
                
            });
            captchaObj.getValidate()
        };
    };

    // 通过邮箱注册配置极验
    if (config_varify_reg) {
        $.ajax({
            // 获取id，challenge，success（是否启用failback）
            url: qscms.root+'?m=Home&c=Captcha&t=' + (new Date()).getTime(), // 加随机数防止缓存
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
                }, handlerRegEmail);
            }
        });
        var handlerRegEmail = function(captchaObj) {
            captchaObj.appendTo("#popup-captcha-email");
            captchaObj.bindOn("#btnCheckEmail");
            captchaObj.onSuccess(function() {
                regPerByEmailHandler();
            });
            captchaObj.onFail(function() {
                
            });
            captchaObj.onError(function() {
                
            });
            captchaObj.getValidate()
        };
    }
})(jQuery);