$(document).ready(function(){
	$("#mnavh").click(function(){
		$("#starlist").toggle();
		$("#mnavh").toggleClass("open");
	});
	$(".quickReplay").click(function(){
		var a=$(this).parents(".comment_msg").eq(0),
			b=a.data("id"),
			c=$(".comment_post");
		b && $("#topId").val(b),c.find(".comment_title").append('<a rel="nofollow" id="cancelReply" href="javascript:;" class="cancelReply">取消回复</a>'),a.find(".comment_content").get(0).appendChild(c.get(0));
	})
	$("body").on("click","#cancelReply",function(){
		var a=c=$(".comment_post");
		$("#topId").val(0),a.find("#cancelReply").remove(),$("main").get(0).appendChild(a.get(0));
	})
	$(".praise").click(function(){
		var a=$(this).data("val");
		a && $.ajaxpost('/praise',{"id":a},function(res){
			$.Msg(res.msg)
			res.code == 200 && $(".praise").find("i").text(res.data);
		})
	})
	
	var offset = 300,
        offset_opacity = 1200,
        scroll_top_duration = 700,
        $back_to_top = $('.cd-top');

    $(window).scroll(function () {
        ($(this).scrollTop() > offset) ? $back_to_top.addClass('cd-is-visible') : $back_to_top.removeClass('cd-is-visible cd-fade-out');
        if ($(this).scrollTop() > offset_opacity) {
            $back_to_top.addClass('cd-fade-out');
        }
    });
    $back_to_top.on('click', function (event) {
        event.preventDefault();
        $('body,html').animate({
                scrollTop: 0,
            }, scroll_top_duration
        );
    });
})
function commentPost(){
	var a=$('#commentSumbit').serializeArray(),
		postUrl=$('#commentSumbit').attr('action'),
		param={};
	$.each(a, function(b,c){
		param[c.name] = c.value;
    });
	if(!param.username){
		$.Msg('评论名称不可为空');return !1;
	}
	if('undefined' != typeof param.verifyCode && !param.verifyCode){
		$.Msg('验证码不可为空');return !1;
	}
	if(param.email && !$.checkform(param.email,'email')){
		$.Msg('邮箱格式错误');return !1;
	}
	if(param.home && !$.checkform(param.home,'url')){
		$.Msg('主页网址格式错误');return !1;
	}
	if(!param.content){
		$.Msg('评论内容不可为空');return !1;
	}
	$.ajaxpost(postUrl,param,function(res){
		$.Msg(res.msg);
		if(res.code == 200){
			setTimeout(function(){window.location.reload()},2200);
		}else if(res.code == 101){
			$("#commentVcode").length > 0 && $("#commentVcode").click();
		}
	})
}