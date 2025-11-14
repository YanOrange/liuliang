
$(document).ready(function(){
    var u_id = $('input[name="u_id"]').val();
    //连接服务器tcp
    var ws_url = "ws://118.24.117.76:9502?u_id="+u_id;
    var ws = new WebSocket(ws_url);

    /*
    这部分js我不做解释了，请大家自己对面js效果来看
    * */
    $('.qq').css('display','block').removeClass('mins');
    $('.qq-login').css('display','none');
    
    $('.qq-xuan li').click(function(){
        $(this).addClass('qq-xuan-li').siblings().removeClass('qq-xuan-li')
    });
    $('.qq-hui-txt').hover(function(){
        var aa = $(this).html()                                                                                                                  
        $('.qq-hui-txt').attr('title',aa)
    });
    $('.login-close').click(function(){
        $(this).parent().parent().css('display','none')
    });
    /*
    * end
    * */

    /*点击事件弹开聊天窗口*/
    $('.qq-hui li').dblclick(function(){
        $('.qq-chat').css('display','block').removeClass('mins');
        $('.qq-chat-t-name').html($(this).find('span').html());
        $('.qq-chat-t-head img').attr('src',$(this).find('img').attr('src'));
        $('.qq-chat-you span').html($(this).find('span').html());
        $('.qq-chat-you i').html($(this).find('.qq-hui-name i').html());
        $('.qq-chat-ner').html($(this).find('.qq-hui-txt').html());
        $("#qq-chat-text").trigger("focus");
        var gu_id =  $(this).find('.qq-hui-uid').val();
        $('.fasong').attr('uid',gu_id);
        $('.my').remove();
        //做一步聊天栏标识
        $(".chat-note-box ul").attr('class','chat-note-box-'+gu_id);
        //去获取聊天记录，通过用户列表获取当前用户与列表中的用户聊天记录
        var note_url = $('input[name="get_note_url"]').val();
        $.ajax({
            type: 'POST',
            url : note_url,
            dataType: 'json',
            data: {'u_id':u_id,'gu_id':gu_id},
            success: function(result){
                console.log(str);
                var str = '';
                //将拿出来的数据进行拼接到聊天栏中
                $.each(result.data,function(index, data){
                    str += '<li class="my"><div class="qq-chat-my';
                    if(data.u_id == gu_id){
                     str += ' font-red';
                    }
                    str += '"><span>'+data.u_name+'</span><i>'+timestampToTime(data.add_time)+'</i></div><div class="qq-chat-ner">'+data.desc+'</div></li>';
                });
                //在聊天栏中添加拼接好的数据
                $(".chat-note-box-"+gu_id).append(str);
                //将滚条更新，防止数据添加时，记录页面不上升
                $(".qq-chat-txt").scrollTop($(".qq-chat-txt")[0].scrollHeight);
            }
        });
        
    });


    $('.qq-exe img').dblclick(function(){
        $('.qq-login').css('display','block').removeClass('mins')
    });

    /**
     * 修改名称
     */
    $('.u_name').blur(function(){
        var u_name = $(this).val();
        if(u_name == ''){
            alert('用户名称不能为空');
        }
        var update_url = $('input[name="update_url"]').val();
        $.ajax({
            type: 'POST',
            url : update_url,
            dataType: 'json',
            data: {u_name:u_name},
            success: function(result){
                console.log(result);
            }
        });
        
    });

    $('.close').click(function(){
        $(this).parent().parent().parent().css('display','none')
    });
    $('.min').click(function(){
        $(this).parent().parent().parent().addClass('mins')
    });
    $('.qq .close').click(function(){
        $('.qq-chat').css('display','none')
    });
    $('#qq-chat-text').keydown(function(e){
        if(e.keyCode == 27){
            $('.qq-chat').css('display','none')
        }
    });
    //发送信息
    ws.onopen = function(evt){
        $('.fasong').click(function(){
            if($('#qq-chat-text').val()==''){
                alert("发送内容不能为空,请输入内容")
            }else if($('#qq-chat-text').val()!=''){
                //获取消息
                var ner = $('#qq-chat-text').val();
                var ners = ner.replace(/\n/g,'<br>');
                //获取接收人的u_id
                var uid = $(this).attr('uid');
                //获取发送人的name
                var uname = $('input[name="u_name"]').val();
                //转化为json进行传送数据
                var data = JSON.stringify({'gu_id':uid,'desc':ners,'u_id':u_id,'u_name':uname});

                // console.log("发送信息的地方");
                ws.send(data);

                var now=new Date();
                //将发送的数据拼接到聊天记录栏中
                var t_div = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+' '+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
                $(".li-"+uid+" .qq-hui-txt").text(ners);
                $(".li-"+uid+" .qq-hui-name i").text(t_div);

                //将数据拼接到用户列表的最新消息记录展示
                $(".chat-note-box-"+uid).append('<li class="my"><div class="qq-chat-my"><span>'+uname+'</span><i>'+t_div+'</i></div><div class="qq-chat-ner">'+ners+'</div></li>')
                $(".qq-chat-txt").scrollTop($(".qq-chat-txt")[0].scrollHeight);
                $('#qq-chat-text').val('').trigger("focus");
            }
        });
    };

    //接收信息
    ws.onmessage = function (evt) {
        var data = evt.data;
        var json_data = jQuery.parseJSON(data);
        console.log(data);

        //将接收到的数据放在用户列表的最新消息
        $(".li-"+json_data.request_id+" .qq-hui-txt").text(json_data.msg);
        //将接收到的数据放在正在聊天的聊天栏中
        var now=new Date();
        var t_div = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+' '+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
        var str = '<li class="my"><div class="qq-chat-my font-red"><span>'+json_data.uname+'</span><i>'+t_div+'</i></div><div class="qq-chat-ner">'+json_data.msg+'</div></li>';
        $(".chat-note-box-"+json_data.request_id).append(str);
        $(".qq-chat-txt").scrollTop($(".qq-chat-txt")[0].scrollHeight);
    };
    
    $('.close-chat').click(function(){
        $('.qq-chat').css('display','none')
    });
    $(".qq-hui").niceScroll({
        touchbehavior:false,cursorcolor:"#ccc",cursoropacitymax:1,cursorwidth:6,horizrailenabled:true,cursorborderradius:3,autohidemode:true,background:'none',cursorborder:'none'
    });


    //时间戳转为日期
    function timestampToTime(timestamp) {
        var date = new Date(timestamp * 1000);//时间戳为10位需*1000，时间戳为13位的话不需乘1000
        Y = date.getFullYear() + '-';
        M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
        D = date.getDate() + ' ';
        h = date.getHours() + ':';
        m = (date.getMinutes() < 10 ? '0'+(date.getMinutes()) : date.getMinutes()) + ':';
        s = (date.getSeconds() < 10 ? '0'+(date.getSeconds()) : date.getSeconds());
        return Y+M+D+h+m+s;
    }

});