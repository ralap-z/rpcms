var bodyUE,attrClick=false;
$.fn.toggleClick = function(){
    var functions = arguments ;
    return this.click(function(){
            var iteration = $(this).data('iteration') || 0;
            functions[iteration].apply(this, arguments);
            iteration = (iteration + 1) % functions.length ;
            $(this).data('iteration', iteration);
    });
};
function getChecked(dom){
    var re = [];
    $('input.'+dom).each(function(a){
        if($(this).is(':checked')){
            re.push($(this).val());
        }
    });
    return re.join(',');
}
function isalias(a){
    var reg1=/^[A-Za-z0-9\-]*$/;
    if(!reg1.test(a)){
        return 1;
    }else{
        return 0;
    }
}
function selectAttr(param){
	param && $.post(attrSelectUrl,param,function(res){
		var data=res.data;
		$(".attrNums").text(data.length);
		$(".attr_list").html('');
		for(var a in data){
			$(".attr_list").append('<a href="javascript:;" title="点击插入 '+data[a].filename+'" class="attr_item" data-id="'+data[a].id+'" data-downurl="'+data[a].downurl+'" data-name="'+data[a].filename+'"><img src="/static/images/'+data[a].filetype+'.jpg"/><p>'+data[a].filename+'</p><i class="me-icon me-icon-close attr_dele" title="点击删除文件"></i></a>');
		}
	},"json");
}
$(document).ready(function(){
	var extendLeft=0;
	$(".me_extend_menu").each(function(a,b){
		$(b).css("left",extendLeft);
		extendLeft+=$(b).outerWidth();
	})
	
	$("body").on("click",".menu_son",function(e){
		e.preventDefault(), e.stopPropagation();
		$(this).hasClass("active") ? $(this).removeClass("active") : $(this).addClass("active");
	})
	$("body").on("click",".menu_child a",function(e){
		e.stopPropagation();
	})
	$("body").on("click",".fullScreen",function(e){
		e.preventDefault(), e.stopPropagation();
		var a=$(this).data('screen') || false;
		$(this).data('screen',!a),$.fullScreen(document.documentElement,a);
	})
	$("body").on("click","*[data-extend-box]",function(e){
		e.preventDefault(), e.stopPropagation();
		var a=$(this).data("extend-box"),
			b=".me_extendBox_"+a;
		if(a == 'attrBox' && (('undefined' != typeof logid && !logid) || ('undefined' != typeof pageId && !pageId))){
			if(!$("input[name='title']").val() || (!$("textarea[name='content']").val() && (!bodyUE || !bodyUE.getContent()))){
				$.Msg('请先填写标题和内容后再上传附件');return !1;
			}
			attrClick=true;
			window['sendPostFrom'] && sendPostFrom();
		}
		$(b).length > 0 && ($(b).slideToggle("fast"));
	})
	$("body").on("click",".attr_item",function(){
		var _this=$(this),
			a=_this.data('downurl'),
			b=_this.data('name');
		console.log(a);
		a && b && bodyUE && bodyUE.execCommand('insertHtml', '<span class="attachment"><a target="_blank" href="'+a+'" title="'+b+'" rel="external nofollow">'+b+'</a></span>');
	})
	$("body").on("click",".attr_dele",function(e){
		e.preventDefault(), e.stopPropagation();
		if(('undefined' == typeof logid || !logid) && ('undefined' == typeof pageId || !pageId)){
			$.Msg('请在文章/单页编辑页面进行操作');return !1;
		}
		var a=$(this).parents(".attr_item").data('id');
		if(logid){
			var b='logs',c=logid;
		}else{
			var b='pages',c=pageId;
		}
		attrReload = attrReload || '';
		a && c && $.ajaxpost(attrDeleUrl,{"type":b,"id":c,"attrId":a},function(res){
			attrReload ? selectAttr(attrReload) : $(".attr_item[data-id='"+a+"']").remove();
		});
	})
	
	$("body").on("click","*[data-model-type]",function(e){
		e.preventDefault(), e.stopPropagation();
		var a=$(this).data("model-type"),
			b=".me_model_"+a;
		var maxH=$(window).height() * 0.7;
		$(b).length > 0 && ($(".me_model").hide(),$(".veil").show(),$(b).show(),$(b).outerHeight() >= maxH && $(b).find(".contentes").css("height",(maxH - $(b).find(".title").outerHeight())+"px"));
	})
	$("body").on("click",".me_model_close",function(){
		var callback=$(this).data('callback');
		$(".me_model").hide(),$(".veil").hide(),callback && window[callback] && window[callback]();
	})
	$("body").on("click",".veil",function(){
		var model;
		$(".me_model").each(function(a,b){
			!$(b).is(":hidden") && (model=$(b));
		})
		model.find('.me_model_close').click();
	})
	$("body").on("click",".sendPost_updateAdmin",function(){
		var param={
			"nickname":$.trim($(".upA_nickname").val()),
			"password":$.trim($(".upA_password").val()),
			"password2":$.trim($(".upA_password2").val()),
		};
		if(!param.nickname){
			$.Msg("昵称不可为空");return !1;
		}
		if(param.password && (param.password).length < 6){
			$.Msg("密码至少6位");return !1;
		}
		if(param.password != param.password2){
			$.Msg("两次密码输入不一致");return !1;
		}
		$.ajaxpost(adminUpdatePsw,param,function(res){
			if(res.code == 200){
				res.data == 1 ? $.confirm("是否退出重新登录","密码已修改成功",function(){
					$(".loginOut").get(0).click();
				},function(){
					window.location.reload();
				}) : ($.Msg("资料修改成功"),$(".veil").click());
			}else{
				$.Msg(res.msg);
			}
		});
	})
})