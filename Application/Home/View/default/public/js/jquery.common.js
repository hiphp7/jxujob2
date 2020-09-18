// JavaScript Document
$(document).ready(function()
{
	//给所有J_hoverbut的元素增加hover样式
	$(".J_hoverbut").hover(
		function()
		{
		$(this).addClass("hover");
		},
		function()
		{
		$(this).removeClass("hover");
		}
		);

	//首页BANER右侧选项卡切换
	$(".noticestab .tli").click( function () {
		$(this).addClass("select").siblings(".tli").removeClass("select");
		var index = $(".noticestab .tli").index(this);
		$('.notice_showtabs').eq(index).show().siblings(".notice_showtabs").hide();
	});
	//首页底部资讯选项卡切换
	$(".newstab .newstli").click( function () {
		$(this).addClass("select").siblings(".newstli").removeClass("select");
		var index = $(".newstab .newstli").index(this);
		$('.news_showtabs').eq(index).show().siblings(".news_showtabs").hide();
	});
	//首页登录二维码和文本登录切换
	$(".code_login,.txt_login").click( function () {
		$(".j_mob_show").toggle();
		$(".J_qr_code_show").toggle();
		$(".code_login").toggle();
		$(".txt_login").toggle();
	});
	$('.code_login').click(function(){
		get_weixin_qrcode();
	});
	var qrcode_time,
		waiting_weixin_scan = function(){
			$.getJSON(qscms.root + '?m=Home&c=Members&a=waiting_weixin_login',function(result){
				if(result.status == 1){
					window.location.href = result.data;
				}
			});
		},
		get_weixin_qrcode = function(){
			clearInterval(qrcode_time);
			$.getJSON(qscms.root + '?m=Home&c=Qrcode&a=get_weixin_qrcode',{type:'login'},function(result){
				if(result.status == 1){
					$('#J_weixinQrCode').empty().append(result.data);
					qrcode_time=setInterval(waiting_weixin_scan,5000);
				}else{
					$('#J_weixinQrCode').empty().html(result.msg);
				}
			});
		};
//给符合条件的的文本框增加获取焦点的边框和背景变化	
	$(".J_focus input[type='text'][dir!='no_focus'],.J_focus textarea[dir!='no_focus'],.J_focus input[type='password']").focus(function(){
	$(this).addClass("input_focus")											
	});
	$(".J_focus input[type='text'][dir!='no_focus'],.J_focus textarea[dir!='no_focus'],.J_focus input[type='password']").blur(function(){
	$(this).removeClass("input_focus")	
	});

});
