$(document).ready(function()
{		
	//通用按钮
	$(".admin_submit").hover(function(){$(this).addClass("admin_submit_hover")},function(){$(this).removeClass("admin_submit_hover")});
	//备注
	$(".admin_note").hover(function(){$(this).css("color","#000000")},function(){$(this).css("color","#999999")});
	//搜索按钮
	$(".seh .sbt").hover(function(){$(this).addClass("sbt_hover")},function(){$(this).removeClass("sbt_hover")});
	//全选
	$('#chkAll').click(function()
	{
		$("#form1 :checkbox").not('disabled').prop('checked',$("#chk").is(':checked'));
		setbg();
		if ($("#chk").is(':checked')) {
			$("#form1 :checkbox:checked").not('#chk').each(function(index, el) {
				$(this).parent().parent().addClass('foribg');
			});
		} else {
			$("#form1 :checkbox").not('#chk').each(function(index, el) {
				$(this).parent().parent().removeClass('foribg');
			});
		}
		
	});
	//监听表单单选（单项目。单选项）
    $('.imgchecked,.imgchecked_small').live('click', function () {
        var thisInput = $(this).find("input");
        var thisCode = $(this).data('code').split(',');
        if ($(this).hasClass('select')) {
            thisInput.val(thisCode[0]);
            $(this).removeClass('select');
        } else {
            thisInput.val(thisCode[1]);
            $(this).addClass('select');
        }
    })
    //监听表单单选(多项目。单选项)
    $('.imgradio .radio').live('click', function () {
        if ($(this).hasClass('disabled')) return false;
        $(this).parent().find("div").removeClass('select');
        $(this).addClass('select');
        data = $(this).attr('data');
        data1 = $(this).attr('data1');
        $(this).parent().find("input").eq(0).val(data);
        $(this).parent().find("input").eq(1).val(data1);
    });
    //列表页关键字高亮
    $strikingkey = $('.footso .sinput').val()
    if ($strikingkey && isNaN($strikingkey)) {
        $('.striking').highlight($strikingkey);
    }
    




	// 反选
	$("#form1 :checkbox").not('#chk').click(function(event) {console.log();
		if ($(this).is(':checked')) {
			$(this).parent().parent().addClass('foribg');
			if ($("#form1 :checkbox:checked").not('#chk').length == $("#form1 :checkbox").not('#chk').length) {
				$("#chk").prop('checked', !0);
			}
		} else {
			$(this).parent().parent().removeClass('foribg');
			$("#chk").prop('checked', 0);
		}
	});
	//list列表项目变色
	$("#list tr:gt(0)").hover(function(){$(this).css("background-color","#F5FCFE");},function()	{$(this).css("background-color","");});
	//文本框变色
	$("input[type='text']").focus(function(){$(this).css({"border-color":"#0066CC #9DCEFF #9DCEFF #0066CC","background-color":"#EEF8FF"})});
	$("input[type='text']").blur(function(){$(this).css({"border-color":"","background-color":""})});
	//单选和复选状态
	$("input[type='checkbox'],input[type='radio']").live('click', function() {
  	setbg()
	});
	setbg();
	//table自适应
	$('#list').rowSizing({ selectRows:"", imgOff: '',imgOn : ''	});
	//全屏窗口
	setframe();
	$("#open_frame").hover(function(){$(this).addClass("open_frame_hover")},function(){$(this).removeClass("open_frame_hover")});
	$("#open_frame").click(function(){closeFrameset();});
	$("#close_frame").hover(function(){$(this).addClass("close_frame_hover")},function(){$(this).removeClass("close_frame_hover")});
	$("#close_frame").click(function(){openFrameset();});
});
//设置label背景
function setbg()
{
		$(":checkbox").parent("label").css("color","#666666");		
		$(":checkbox[checked]").parent("label").css("color","#009900");
		$(":radio").parent("label").css("color","#666666");
		$(":radio[checked]").parent("label").css("color","#009900");
}
//模拟select
function showmenu(menuID,showID,inputname)
{
	$(menuID).click(function(){
		$(menuID).blur();
		$(menuID).parent("div").css("position","relative");
		$(showID).slideToggle("fast");
		//生成背景
		$(menuID).parent("div").before("<div class=\"menu_bg_layer\"></div>");
		$(".menu_bg_layer").height($(document).height());
		$(".menu_bg_layer").css({ width: "100%", position: "absolute", left: "0", top: "0" , "z-index": "0"});
		//生成背景结束
		$(showID+" li").click(function(){
			$(menuID).val($(this).attr("title"));
			$(inputname).val($(this).attr("id"));
			$(".menu_bg_layer").hide();
			$(showID).hide();
			$(menuID).parent("div").css("position","");	
			$(this).css("background-color","");
			//$("#Form1").validate().element(inputname); //验证表单中的一个需要验证的表单元素。 			
		});

				$(".menu_bg_layer").click(function(){
					$(".menu_bg_layer").hide();
					$(showID).hide();
					$(menuID).parent("div").css("position","");
				});
		$(showID+" li").hover(
		function()
		{
		$(this).css("background-color","#DAECF5");
		},
		function()
		{
		$(this).css("background-color","");

		}
		);
	});
}

//模拟select


    showmenu("#J_key_click", "#J_key_type_id", "#J_key_type_cn", "#J_mlist");

    seltpye_y(".seltpye_y");
    // 动态改变导航
    function changeTopNavSome() {
        var $topMenu = $(window.parent.frames["topFrame"].document).find(".frame_top"),
            $topLogo = $topMenu.find('.logo'),
            $topLinks = $topMenu.find('.links'),
            $topAdmin = $topMenu.find('.adminname'),
            $topNav = $topMenu.find('.navmenu'),
            $topNavA = $topNav.find('a');
        var navAWidth = 0;
        var navWidth = $topMenu.width() - ($topLogo.width() + $topLinks.width() + 530);
        var menuIdArray = new Array();
        $topNavA.each(function () {
            $(this).removeClass('forDia');
            $(this).show();
        })
        $topNavA.each(function () {
            if (!$(this).hasClass('for_more')) {
                navAWidth = navAWidth + $(this).outerWidth();
                if (navAWidth > navWidth) {
                    menuIdArray.push($(this).data('id'));
                }
            }
        })
        if (menuIdArray.length) {
            for (var i = 0; i < menuIdArray.length; i++) {
                $topNavA.each(function () {
                    if (!$(this).hasClass('for_more')) {
                        if ($(this).data('id') == menuIdArray[i]) {
                            $(this).addClass('forDia');
                            $(this).hide();
                        }
                    }
                })
            }
            var $navMore = $topMenu.find('#J_nav_more');
            if ($navMore) {
                $navMore.remove();
            }
            $('<a href="javascript:;" class="for_more" id="J_nav_more"></a>').insertAfter($topNav.find('a:last'));
            $navMore.die().live('click', function () {
                $('.modal_backdrop').remove();
                $('.modal').remove();
                var qsDialog = $(this).dialog({
                    title: '更多导航',
                    loading: true,
                    footer : false
                });
                var $forDia = $topMenu.find('.forDia');
                var conHtml = '<div class="admin_management link_blue">';
                $forDia.each(function () {
                    conHtml += '<div class="manitem for_more"><a class="J_more_a more_a" target="mainFrame" href="' + $(this).attr('href') + '" frame-url="'+$(this).attr('frame-url')+'">' + $(this).text() + '</a></div>';
                })
                conHtml += '<div class="clear"></div></div>';
                qsDialog.setContent(conHtml);
                afreshDialogPosition();
                $('.J_more_a').die().live('click', function () {
                    parent.topFrame.location.reload();
                    window.parent.frames["leftFrame"].location.href = $(this).attr('frame-url');
                })
            })
        } else {
            $topMenu.find('#J_nav_more').remove();
        }
    }
    changeTopNavSome();
    $(window).resize(function () {
        changeTopNavSome();
    })
});


//跳转菜单
function jumpmenu(menuID,ShowDiv)
{
	$(menuID).click(function(){
		$(menuID).blur();
		$(menuID).parent("div").css("position","relative");
		$(ShowDiv).slideToggle("fast");
		//生成背景
		$(ShowDiv).parent("div").before("<div class=\"menu_bg_layer\"></div>");
		$(".menu_bg_layer").height($(document).height());
		$(".menu_bg_layer").css({ width: "100%", position: "absolute", left: "0", top: "0" , "z-index": "0"});
		//生成背景结束
		$(ShowDiv+" li").click(function(){	
		window.location.href=$(this).attr("url");			
		});
		$(".menu_bg_layer").click(function(){
		$(".menu_bg_layer").hide();
		$(ShowDiv).hide();
		$(ShowDiv).parent("div").css("position","");
		});
		$(ShowDiv+" li").hover(function(){$(this).css("background-color","#DAECF5")},function()	{$(this).css("background-color","")});	});
}
function getcathtml(val)
{
	if (val=="")return false;
    arrcity=val.split("|");
	var htmlstr='<ul>';
	for(var i=0;i<arrcity.length;i++)
		{
			 var city=arrcity[i].split(",");
	htmlstr+="<li id=\""+city[0]+"\" title=\""+city[1]+"\">"+city[1]+"</li>";
		}
	htmlstr+="</ul>";
	return htmlstr; 
}
//大列表
function showmenulayer(menuID,showID,inputname,inputname1,inputname2,arr)
{
	$(menuID).click(function(){
		$(menuID).blur();
		$(menuID).parent("div").css("position","relative");
		$(showID).slideToggle("fast");
		//生成背景
		$(menuID).parent("div").before("<div class=\"menu_bg_layer\"></div>");
		$(".menu_bg_layer").height($(document).height());
		$(".menu_bg_layer").css({ width: $(document).width(), position: "absolute", left: "0", top: "0" , "z-index": "0"});
		//生成背景结束
		$(showID+">ul>li").click(function(){
			$(menuID).val($(this).attr("title"));
			$(inputname).val($(this).attr("id"));
			var strclass=arr[$(this).attr("id")];
			if (strclass)//假如存在小类
			{
				$(showID).hide();
				$(showID+"_s").show();
				var	go_back="<span class=\"go_back\">[返回上层分类>>]</span>";
				$(showID+"_s").html(go_back+getcathtml(strclass));//生成LI
				
					$(showID+"_s>ul>li").click(function(){//点击小类

						$(menuID).val($(menuID).val()+"/"+$(this).attr("title"));
						$(inputname1).val($(this).attr("id"));
						var strclass1=arr[$(this).attr("id")];	
						if (strclass1)//假如存在小类
						{
							$(showID).hide();
							$(showID+"_s").hide();
							$(showID+"_third").show();
							var	go_back="<span class=\"go_back\">[返回上层分类>>]</span>";
							$(showID+"_third").html(go_back+getcathtml(strclass1));//生成LI
								$(showID+"_third>ul>li").click(function(){//点击小类	

									$(menuID).val($(menuID).val()+"/"+$(this).attr("title"));
									$(inputname2).val($(this).attr("id"));
									$(".menu_bg_layer").hide();	
									$(showID+"_s").hide();
									$(showID+"_third").hide();
									$(menuID+"_s").parent("div").css("position","");	
									$(this).css("background-color","");
								});
								$(".go_back").click(function(){//返回上层分类
								var top_v = $(menuID).val().split("/");
								$(menuID).val(top_v[0]);
								$(showID+"_s").show();
								$(showID+"_third").hide();
								});
								$(".dmenu>ul>li").hover(
									function()
									{
									$(this).css("background-color","#DAECF5");
									},
									function()
									{
									$(this).css("background-color","");
									}
									);
							}
							else
							{
							$(menuID).val($(menuID).val());
							$(inputname1).val($(this).attr("id"));
							$(inputname2).val("");
							$(".menu_bg_layer").hide();	
							$(showID).hide();
							$(showID+"_s").hide();
							$(showID+"_third").hide();
							$(menuID).parent("div").css("position","");	
							$(this).css("background-color","");
							}

					});
					$(".go_back").click(function(){//返回上层分类
					$(showID).show();
					$(showID+"_s").hide();
					$(showID+"_third").hide();
					});
					$(".dmenu>ul>li").hover(
						function()
						{
						$(this).css("background-color","#DAECF5");
						},
						function()
						{
						$(this).css("background-color","");
						}
						);
				}
			else
			{
			$(menuID).val($(this).attr("title"));
			$(inputname).val($(this).attr("id"));
			$(inputname1).val("");
			$(inputname2).val("");
			$(".menu_bg_layer").hide();
			$(showID).hide();
			$(menuID).parent("div").css("position","");	
			$(this).css("background-color","");
			}
			$("#Form1").validate().element(inputname); //验证表单中的一个需要验证的表单元素。 			
		});

				$(".menu_bg_layer").click(function(){
					$(".menu_bg_layer").hide();
					$(showID).hide();
					$(showID+"_s").hide();
					$(showID+"_third").hide();
					$(menuID).parent("div").css("position","");
				});
		$(".dmenu>ul>li").hover(
		function()
		{
		$(this).css("background-color","#DAECF5");
		},
		function()
		{
		$(this).css("background-color","");
		}
		);
	});
}
function makehtml(val)
{
	if (val=="")return false;
    arrcity=val.split("|");
	var htmlstr='<ul>';
	for (x in arrcity)
	{
	 var city=arrcity[x].split(",");
	htmlstr+="<li id=\""+city[0]+"\" title=\""+city[1]+"\">"+city[1]+"</li>";
	}
	htmlstr+="</ul>";
	return htmlstr; 
}

function setframe()
{
	var Frame=parent.document.getElementsByTagName("frameset")[1];
	if (Frame.cols=="0,*")
	{
		$("#open_frame").hide();
		$("#close_frame").show();
	}
	else
	{
		$("#open_frame").show();
		$("#close_frame").hide();
	}
}
function closeFrameset()
{
parent.document.getElementsByTagName("frameset")[0].rows = "0,*";
parent.document.getElementsByTagName("frameset")[1].cols = "0,*";
$("#open_frame").hide();
$("#close_frame").show();
}
function openFrameset(){
parent.document.getElementsByTagName("frameset")[0].rows = "70,*";
parent.document.getElementsByTagName("frameset")[1].cols = "200,*";
$("#open_frame").show();
$("#close_frame").hide();
}