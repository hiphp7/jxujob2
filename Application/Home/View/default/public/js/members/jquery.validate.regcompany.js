 /* ============================================================
 * jquery.validate.regcompany.js 企业注册验证
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

    // 自定义验证方法，验证手机号是否唯一
    $.validator.addMethod('IsRegisteredT', function(value, element) {
        var result = false, eletype = 'mobile';
        if (value.length) {
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
        } else {
            result = true;
        }
        return result;
    }, '已被注册');

    // 自定义验证方法，验证区号
    $.validator.addMethod("inputRegValiZone", function(value, element, param) {
        if (this.optional(element))
            return "dependency-mismatch";
        var reg = param;
        if (typeof param == 'string') {
            reg = new RegExp(param);
        }
        return reg.test(value);
    }, '区号格式不正确');

    // 自定义验证方法，固话手机二选一
    $.validator.addMethod("lineMobileAchoice", function(value, element, param) {
        var regularTelphone = /^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|17[0-9]{9}$|16[0-9]{9}$|19[0-9]{9}$/;
        var achoice = true;
        var telphoneValue = $.trim($('#telephone').val());
        var landlinefirstValue = $.trim($("#landline_tel_first").val());
        var landlinenextValue = $.trim($("#landline_tel_next").val());
        if (telphoneValue == '' && landlinenextValue == '') {
            achoice = false;
        }
        if (telphoneValue != "" && !regularTelphone.test(telphoneValue) && landlinenextValue == '') {
            achoice = false;
        }
        return achoice;
    }, '固定电话和手机号码请至少填写一项');

    // 手机号输入实时验证二选一
    $('input[name="telephone"]').on('keyup', function(event) {
        var telephoneValue = $(this).val();
        if (telephoneValue.length >= 11) {
            if (!$('#landline_tel_next').closest('.td1').next().find('.ok').length) {
                $('#landline_tel_next').closest('.td1').next().empty();
            }
        }
    });

    // 固定电话输入实时验证二选一
    $('input[name="landline_tel_next"]').on('keyup', function(event) {
        var telValue = $(this).val();
        if (telValue.length >= 6) {
            if (!$('#telephone').closest('.td1').next().find('.ok').length) {
                $('#telephone').closest('.td1').next().empty();
            }
        }
    });

    // 获取后台注册验证配置
    var config_varify_reg = eval($('#J_config_varify_reg').val());

    // 注册企业处理程序
    function regCompanyHandler() {
        $('#btnRegister').val('注册中...').addClass('btn_disabled').prop('disabled', !0);
        $.ajax({
            url: qscms.root+'?m=Home&c=Members&a=register',
            type: 'POST',
            dataType: 'json',
            data: $('#registerForm').serialize(),
            success: function (data) {
                if(data.status == 1){
                    window.location.href = data.data.url;
                }else{
                    if ($('input[name="agreement"]').is(':checked')) {
                        $('#btnRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                    }
                    disapperTooltip("remind", data.msg);
                }
            },
            error:function(data){
                if ($('input[name="agreement"]').is(':checked')) {
                    $('#btnRegister').val('注册').removeClass('btn_disabled').prop('disabled', 0);
                }
                disapperTooltip("remind", data.msg);
            }
        });
    }

    // 企业注册验证程序
    $('#registerForm').validate({
        submitHandler: function(form) {
            if (!$('input[name="agreement"]').is(':checked')) {
                disapperTooltip("remind", '请同意注册协议');
                return false;
            }
            var landline_tel_num = $.trim($('#landline_tel_first').val()) + '-' + $.trim($('#landline_tel_next').val());
            if ($.trim($('#landline_tel_last').val()).length) {
                landline_tel_num += '-' + $.trim($('#landline_tel_last').val());
            }
            $('#landline_tel').val(landline_tel_num);
            if (config_varify_reg) {
                $('#verifyRegCompany').click();
            } else {
                regCompanyHandler();
            }
        },
        rules: {
            companyname: {
                required: true,
                rangelength: [4, 25],
                IsRegistered: true
            },
            contact: {
                required: true,
                rangelength: [1, 10]
            },
            landline_tel_first: {
                inputRegValiZone: '^[0-9]{3}[0-9]?$'
            },
            landline_tel_next: {
                match: '^[0-9]{6,11}$',
                lineMobileAchoice: true
            },
            landline_tel_last: {
                number: true,
                rangelength: [1, 4]
            },
            telephone: {
                match: /^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|17[0-9]{9}$|16[0-9]{9}$|19[0-9]{9}$/,
                lineMobileAchoice: true,
                IsRegisteredT : true
            },
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
            cpassword: {
                required: true,
                rangelength: [6, 16]
            },
            cpasswordVerify: {
                required: true,
                rangelength: [6, 16],
                equalTo: "#cpassword"
            }
        },
        messages: {
            companyname: {
                required: '<div class="ftxt">请输入企业名称</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">4-25个字组成</div><div class="fimg"></div>',
                IsRegistered: '<div class="ftxt">该企业名称已被注册</div><div class="fimg"></div>'
            },
            contact: {
                required: '<div class="ftxt">请输入企业联系人</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">1-10个字组成</div><div class="fimg"></div>'
            },
            landline_tel_first: {
                inputRegValiZone: '<div class="ftxt">请填写正确的区号</div><div class="fimg"></div>'
            },
            landline_tel_next: {
                match: '<div class="ftxt">请输入6-11位的数字</div><div class="fimg"></div>',
                lineMobileAchoice: '<div class="ftxt">固定电话和手机号码至少填写一项</div><div class="fimg"></div>'
            },
            landline_tel_last: {
                number: '<div class="ftxt">分机号码为数字</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">1-4位数字组成</div><div class="fimg"></div>'
            },
            telephone: {
                match: '<div class="ftxt">手机号格式不正确</div><div class="fimg"></div>',
                lineMobileAchoice: '<div class="ftxt">固定电话和手机号码请至少填写一项</div><div class="fimg"></div>',
                //IsRegisteredT : '<div class="ftxt">手机号已被注册</div><div class="fimg"></div>'
            },
            username: {
                required: '<div class="ftxt">请输入用户名</div><div class="fimg"></div>',
                match: '<div class="ftxt">中英文开头6-18位，无特殊符号</div><div class="fimg"></div>',
                IsRegistered: '<div class="ftxt">用户名已被注册</div><div class="fimg"></div>'
            },
            email: {
                required: '<div class="ftxt">请输入邮箱</div><div class="fimg"></div>',
                email: '<div class="ftxt">邮箱格式不正确</div><div class="fimg"></div>',
                IsRegistered: '<div class="ftxt">该邮箱地址已被注册，请尝试登录</div><div class="fimg"></div>'
            },
            cpassword: {
                required: '<div class="ftxt">请输入密码</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">密码长度要求为6-16个字符</div><div class="fimg"></div>'
            },
            cpasswordVerify: {
                required: '<div class="ftxt">请输入确认密码</div><div class="fimg"></div>',
                rangelength: '<div class="ftxt">密码长度要求为6-16个字符</div><div class="fimg"></div>',
                equalTo: '<div class="ftxt">两次输入的密码不一致</div><div class="fimg"></div>'
            }
        },
        errorClasses: {
            companyname: {
                required: 'tip err',
                rangelength: 'tip err',
                IsRegistered: 'tip err'
            },
            contact: {
                required: 'tip err',
                rangelength: 'tip err'
            },
            landline_tel_first: {
                inputRegValiZone: 'tip err'
            },
            landline_tel_next: {
                match: 'tip err',
                lineMobileAchoice: 'tip err'
            },
            landline_tel_last: {
                number: 'tip err',
                rangelength: 'tip err'
            },
            telephone: {
                match: 'tip err',
                lineMobileAchoice: 'tip err',
                //IsRegisteredT:  'tip err'
            },
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
            cpassword: {
                required: 'tip err',
                rangelength: 'tip err'
            },
            cpasswordVerify: {
                required: 'tip err',
                rangelength: 'tip err',
                equalTo: 'tip err'
            }
        },
        tips: {
            companyname: '<div class="ftxt">名称与企业营业执照保持一致</div><div class="fimg"></div>',
            contact: '<div class="ftxt">请填写全名</div><div class="fimg"></div>',
            /* telephone: '<div class="ftxt">手机号可用于登录网站和找回密码</div><div class="fimg"></div>', */
            username: '<div class="ftxt">中英文开头6-18位，无特殊符号</div><div class="fimg"></div>',
            email: '<div class="ftxt">用邮箱用于接收简历及系统通知</div><div class="fimg"></div>',
            cpassword: '<div class="ftxt">由6-16个数字、字母和符号组成</div><div class="fimg"></div>',
            cpasswordVerify: '<div class="ftxt">请再次输入密码</div><div class="fimg"></div>'
        },
        tipClasses: {
            companyname: 'tip',
            contact: 'tip',
            telephone: 'tip',
            username: 'tip',
            email: 'tip',
            cpassword: 'tip',
            cpasswordVerify: 'tip'
        },
        groups: {
            phoneNum: 'landline_tel_first landline_tel_next landline_tel_last'
        },
        errorElement: 'div',
        errorPlacement: function(error, element) {
            if (element.attr('name') == 'landline_tel_last' || element.attr('name') == 'landline_tel_next' || element.attr('name') == 'landline_tel_first') {
                element.closest('.J_validate_group').find('.J_showtip_box').append(error);
            }  else {
                element.closest('.J_validate_group').find('.J_showtip_box').append(error);
            }
        },
        success: function(label) {
            label.append('<div class="ok"></div>');
        }
    });

    // 如果后台开启注册验证就配置极验
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
                }, handler);
            },
            error:function(data){
                
            }
        });
        var handler = function(captchaObj) {
            captchaObj.appendTo("#popupCaptcha");
            captchaObj.bindOn("#verifyRegCompany");
            captchaObj.onSuccess(function() {
                regCompanyHandler();
            });
            captchaObj.onFail(function() {
                
            });
            captchaObj.onError(function() {
                
            });
            captchaObj.getValidate()
        }
    }
})(jQuery);