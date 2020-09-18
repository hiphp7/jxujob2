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
	
	
	//积分商城产品详细页介绍和兑换记录切换
	$(".tnav .nl").click( function () {
		$(this).addClass("select").siblings(".nl").removeClass("select");
		var index = $(".tnav .nl").index(this);
		$('.info').eq(index).show().siblings(".info").hide();
	});
});
