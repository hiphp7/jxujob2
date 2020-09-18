/**
 * 
 */

function addjobtest() {
	var zpzw = $('#zpzw').val();
	if (zpzw == "") {
		sweetAlert("请填写职位名称", "", "error");
		return false;
	}
	var gzqyjob = $("#gzqyjob").select2("data"); // 多选
	if (gzqyjob.length == 0) {

		sweetAlert("请选择工作区域", "", "error");
		return false;
	}
	var gzqysize = $("#gzqyjob option:selected").length;
	var gzqydm = "";
	var gzqy = "";
	var gzqy2dm = "";
	var gzqy2 = "";
	var gzqy3dm = "";
	var gzqy3 = "";
	if (gzqysize == 1) {
		gzqydm = $("#gzqyjob option:selected")[0].value;
		gzqy = $("#gzqyjob option:selected")[0].text;
	} else if (gzqysize == 2) {
		gzqydm = $("#gzqyjob option:selected")[0].value;
		gzqy = $("#gzqyjob option:selected")[0].text;
		gzqy2dm = $("#gzqyjob option:selected")[1].value;
		gzqy2 = $("#gzqyjob option:selected")[1].text;
	} else if (gzqysize == 3) {
		gzqydm = $("#gzqyjob option:selected")[0].value;
		gzqy = $("#gzqyjob option:selected")[0].text;
		gzqy2dm = $("#gzqyjob option:selected")[1].value;
		gzqy2 = $("#gzqyjob option:selected")[1].text;
		gzqy3dm = $("#gzqyjob option:selected")[2].value;
		gzqy3 = $("#gzqyjob option:selected")[2].text;
	}
	var zygjz = $("#zygjz").select2("data"); // 多选
	if (zygjz.length == 0) {
		sweetAlert("请选择专业需求", "", "error");
		return false;
	}
	var zygjzsize = $("#zygjz option:selected").length;
	var zygjz1 = "";
	var zygjz1dm = "";
	var zygjz2 = "";
	var zygjz2dm = "";
	var zygjz3="";
	var zygjz3dm = "";

	var zygjz4="";
	var zygjz4dm = "";

	var zygjz5="";
	var zygjz5dm = "";
	
	switch(zygjzsize){
		case 1:
			zygjz1 = $("#zygjz option:selected")[0].text;
			zygjz1dm = $("#zygjz option:selected")[0].value;
			break;
		case 2:
			zygjz1 = $("#zygjz option:selected")[0].text;
			zygjz1dm = $("#zygjz option:selected")[0].value;
			zygjz2 = $("#zygjz option:selected")[1].text;
			zygjz2dm = $("#zygjz option:selected")[1].value;
			break;
		case 3:
			zygjz1 = $("#zygjz option:selected")[0].text;
			zygjz1dm = $("#zygjz option:selected")[0].value;
			zygjz2 = $("#zygjz option:selected")[1].text;
			zygjz2dm = $("#zygjz option:selected")[1].value;

			zygjz3 = $("#zygjz option:selected")[2].text;
			zygjz3dm = $("#zygjz option:selected")[2].value;
			break;
		case 4:
			zygjz1 = $("#zygjz option:selected")[0].text;
			zygjz1dm = $("#zygjz option:selected")[0].value;
			zygjz2 = $("#zygjz option:selected")[1].text;
			zygjz2dm = $("#zygjz option:selected")[1].value;
			zygjz3 = $("#zygjz option:selected")[2].text;
			zygjz3dm = $("#zygjz option:selected")[2].value;
			zygjz4 = $("#zygjz option:selected")[3].text;
			zygjz4dm = $("#zygjz option:selected")[3].value;
			break;
		case 5:
			zygjz1 = $("#zygjz option:selected")[0].text;
			zygjz1dm = $("#zygjz option:selected")[0].value;
			zygjz2 = $("#zygjz option:selected")[1].text;
			zygjz2dm = $("#zygjz option:selected")[1].value;
			zygjz3 = $("#zygjz option:selected")[2].text;
			zygjz3dm = $("#zygjz option:selected")[2].value;
			zygjz4 = $("#zygjz option:selected")[3].text;
			zygjz4dm = $("#zygjz option:selected")[3].value;
			zygjz5 = $("#zygjz option:selected")[4].text;
			zygjz5dm = $("#zygjz option:selected")[4].value;
			break;
		
	}
	
	
	var zpzwlb = $("#zpzwlb").select2("data"); // 多选
	if (zpzwlb.length == 0) {
		sweetAlert("请选择招聘职位类别", "", "error");
		return false;
	}
	var zpzwlbsize = $("#zpzwlb option:selected").length;
	var zpzwlb1dm = ""
	var zpzwlb1 = "";
	var zpzwlb2dm = "";
	var zpzwlb2 = "";
	var zpzwlb3dm = "";
	var zpzwlb3 = "";
	if (zpzwlbsize == 1) {
		zpzwlb1dm = $("#zpzwlb option:selected")[0].value;
		zpzwlb1 = $("#zpzwlb option:selected")[0].text;
	} else if (zpzwlbsize == 2) {
		zpzwlb1dm = $("#zpzwlb option:selected")[0].value;
		zpzwlb1 = $("#zpzwlb option:selected")[0].text;
		zpzwlb2dm = $("#zpzwlb option:selected")[1].value;
		zpzwlb2 = $("#zpzwlb option:selected")[1].text;
	} else if (zpzwlbsize == 3) {
		zpzwlb1dm = $("#zpzwlb option:selected")[0].value;
		zpzwlb1 = $("#zpzwlb option:selected")[0].text;
		zpzwlb2dm = $("#zpzwlb option:selected")[1].value;
		zpzwlb2 = $("#zpzwlb option:selected")[1].text;
		zpzwlb3dm = $("#zpzwlb option:selected")[2].value;
		zpzwlb3 = $("#zpzwlb option:selected")[2].text;
	}
	var xzdm = $('#xzdm').val();
	var xz = $('#xz').html();
	if (xzdm == "") {
		sweetAlert("请选择薪资", "", "error");
		return false;
	}
	var xlyqdm = $('#xlyqdm').val();
	var xlyq = $('#xlyq').html();
	if (xlyq == "") {
		sweetAlert("请选择学历", "", "error");
		return false;
	}
	var gzhjdm = $('#gzhjdm').val();
	var gzhj = $('#gzhj').html();
	if (gzhjdm == "") {
		sweetAlert("请选择工作环境", "", "error");
		return false;
	}
	var zpdwlbdm = $('#zpdwlbdm').val();
	var zpdwlb = $('#zpdwlb').val();
    var shtyxydm=$('#shtyxydm').val();
    var zpdw=$('#zpdw').val();
    var schoolcode=$('#schoolcode').val();
var sfxwgk=$('#sfxwgk').val();


	var kjsrgzhjms = $('#kjsrgzhjms').val();
	var jsjdj = $("#jsjdj .selected").html();
	var wydj = $("#wydj .selected").html();
	var drivinglicense = $("#drivinglicense .selected").html();
	var xb = $("#xb .selected").html();
	var politicalid = $('#politicalid').val();
	var political = $('#political').html();
	if (politicalid == "") {
		sweetAlert("请选择政治面貌", "", "error");
		return false;
	}
	var xqrs = $('#xqrs').val();
	if (xqrs == "") {
		sweetAlert("请填写需求人数", "", "error");
		return false;
	}
	
	
	var dates =$('#startdatetest').val();
	if(dates==""){
		sweetAlert("请选择招聘日期有效期", "", "error");
		return false;
	}
	var qzxqfbsj=$('#startdatetest').val();
	
    var qzxqjssj=$('#enddatetest').val();
	var qtyq = $('#qtyq').val();
	if (qtyq == "") {
		sweetAlert("请填写职位描述", "", "error");
		return false;
	}
	var heightcode = $('#heightcode').val();
	var height = $('#height').html();
	if (heightcode == "") {
		sweetAlert("请选择身高要求", "", "error");
		return false;
	}
	var weightcode = $('#weightcode').val();
	var weight = $('#weight').html();
	if (weightcode == "") {
		sweetAlert("请选择体重要求", "", "error");
		return false;
	}
	var lefteyecode = $('#lefteyecode').val();
	var lefteye = $('#lefteye').html();
	if (lefteyecode == "") {
		sweetAlert("请选择左眼视力要求", "", "error");
		return false;
	}
	var righteyecode = $('#righteyecode').val();
	var righteye = $('#righteye').html();
	if (righteyecode == "") {
		sweetAlert("请选择右眼视力要求", "", "error");
		return false;
	}
	var sfsm = $("#sfsm .selected").html();
	var sfystcj = $("#sfystcj .selected").html();
	var sfjtkn = $("#sfjtkn .selected").html();
	var sfzfldjkpkh = $("#sfzfldjkpkh .selected").html();
	var sfssmz = $("#sfssmz .selected").html();
	var sf5tgzr = $("#sf5tgzr .selected").html();
	var sf6tgzr = $("#sf6tgzr .selected").html();
	var sfdbz = $("#sfdbz .selected").html();
	var sf8xsborjb = $("#sf8xsborjb .selected").html();
	var sf8xsbjdjb = $("#sf8xsbjdjb .selected").html();
	var sfdjyhjdzsndht = $("#sfdjyhjdzsndht .selected").html();
	var sfdwgmwxyj = $("#sfdwgmwxyj .selected").html();
	var accommodation = $("#accommodation .selected").html();
	var renting = $("#renting .selected").html();
	var canteen = $("#canteen .selected").html();
    var meals = $("#meals .selected").html();
    var zyzrzgz=$("#zyzrzgz .selected").html();
    var percent = $("#percent").val();
    var matchtime = $("#matchtime").val();
	kjsrgzhjms = kjsrgzhjms.replace(/\r\n/g, '<br/>'); //IE9、FF、chrome
	kjsrgzhjms = kjsrgzhjms.replace(/\n/g, '<br/>'); //IE7-8
	kjsrgzhjms = kjsrgzhjms.replace(/\s/g, ' '); //空格处理

	qtyq = qtyq.replace(/\r\n/g, '<br/>'); //IE9、FF、chrome
	qtyq = qtyq.replace(/\n/g, '<br/>'); //IE7-8
	qtyq = qtyq.replace(/\s/g, ' '); //空格处理

    var url=$("#url").val();

	$.ajax({
		type : "post",
		url : "{:U('company/jobs_zjy_add')}",
		data : {

            shtyxydm:shtyxydm,
            schoolcode:schoolcode,
            sfxwgk:sfxwgk,
            qzxqfbsj:qzxqfbsj,
            qzxqjssj:qzxqjssj,
            zpzw:zpzw,
            zpdw:zpdw,
            gzqydm:gzqydm,
            gzqy:gzqy,
            gzqy2dm:gzqy2dm,
            gzqy2:gzqy2,
            gzqy3dm:gzqy3dm,
            gzqy3:gzqy3,
            xzdm:xzdm,
            xz:xz,
            gzhjdm:gzhjdm,
            gzhj:gzhj,
            kjsrgzhjms:kjsrgzhjms,
            xb:xb,
            xlyqdm:xlyqdm,
            xlyq:xlyq,
            zygjz1:zygjz1,
            zygjz1dm:zygjz1dm,
            zygjz2:zygjz2,
            zygjz2dm:zygjz2dm,
            zygjz3:zygjz3,
            zygjz3dm:zygjz3dm,
            zygjz4:zygjz4,
            zygjz4dm:zygjz4dm,
            zygjz5:zygjz5,
            zygjz5dm:zygjz5dm,
            zpdwlbdm:zpdwlbdm,
            zpdwlb:zpdwlb,
            zpzwlb1dm:zpzwlb1dm,
            zpzwlb1:zpzwlb1,
            zpzwlb2dm:zpzwlb2dm,
            zpzwlb2:zpzwlb2,
            zpzwlb3dm:zpzwlb3dm,
            zpzwlb3:zpzwlb3,
            sf5tgzr:sf5tgzr,
            sf6tgzr:sf6tgzr,
            sfdbz:sfdbz,
            sf8xsborjb:sf8xsborjb,
            sf8xsbjdjb:sf8xsbjdjb,
            sfdjyhjdzsndht:sfdjyhjdzsndht,
            sfdwgmwxyj:sfdwgmwxyj,
            qtyq:qtyq,
            xqrs:xqrs,
            heightcode:heightcode,
            height:height,
            weightcode:weightcode,
            weight:weight,
            lefteyecode:lefteyecode,
            lefteye:lefteye,
            righteyecode:righteyecode,
            righteye:righteye,
            sfsm:sfsm,
            sfystcj:sfystcj,
            sfssmz:sfssmz,
            sfjtkn:sfjtkn,
            sfzfldjkpkh:sfzfldjkpkh,
            jsjdj:jsjdj,
            wydj:wydj,
            drivinglicense:drivinglicense,
            zyzrzgz:zyzrzgz,
            accommodation:accommodation,
            renting:renting,
            canteen:canteen,
            meals:meals,
            percent:percent,
            political:political,
            politicalid:politicalid,
            matchtime:matchtime
		},success: function(data){
			             var jsonData = JSON.parse(data);  
		             	 if(jsonData.code==1){	 
		             	    swal({title: "成功注册!",
             	                  text: "您的信息已经提交！",
             	                  type: "success",
             	               }, 
             	              function(){ 
             	            	 post(url+"/company!Allowlgn",{shtyxydm:shtyxydm});
              	                 });
		           	
		             		
			             }else{
			            	sweetAlert("注册失败，可能网络有问题，请稍后再注册!", "", "error");
			            }
					}
    });
    

    	
}




