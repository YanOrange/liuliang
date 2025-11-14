$(document).ready(function(){

    $('.qq-login').css('display','block').removeClass('mins')


    $('.login-but').on("click",function(){
        if($('.login-txt').find('input').val() == ''){
            alert('请输入账号或者密码')
        }else if($('login-txt input').val() != ''){
            var name = $('input[name="username"]').val();
            var password = $('input[name="password"]').val();
            var login_url = $('input[name="login_url"]').val();
            var index_url = $('input[name="index_url"]').val();
            //登录请求，成功后跳转到聊天首页
            $.ajax({
                type: 'POST',
                url : login_url,
                dataType: 'json',
                data: {username:name,password:password},
                success: function(result){
                    console.log(result);
                    if(result.u_id != 0){

                        window.location.href = index_url;
                    }else{
                        alert(result.msg);
                        return false;
                    }
                }
            });

        }
    });

    $('.login-txt input').keydown(function(e){
        if(e.keyCode == 13){
            if($('.login-txt').find('input').val() == ''){
                alert('请输入账号或者密码')
            }else if($('login-txt input').val() != ''){
                var name = $('input[name="username"]').val();
                var password = $('input[name="password"]').val();
                var login_url = $('input[name="login_url"]').val();
                var index_url = $('input[name="index_url"]').val();

                $.ajax({
                    type: 'POST',
                    url : login_url,
                    dataType: 'json',
                    data: {username:name,password:password},
                    success: function(result){
                        console.log(result);
                        if(result.u_id != 0){
                            $('.qq').css('display','block').removeClass('mins');
                            $('.qq-login').css('display','none');
                            window.location.href = index_url;
                        }else{
                            alert(result.msg);
                            return false;
                        }
                    }
                });
            }
        }
    });
});