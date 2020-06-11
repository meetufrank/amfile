$(function(){

        $(".medical-form").validate({
            rules: {
                familyRela:{
                    required: !0
                },
                username: {
                    required: !0
                },
                birthday: {
                    required: !0,
                },
                language:{
                    required: !0
                },
                phone:{
                    required: !0,
                    digits:true
                },
                email:{
                    required: !0
                },
                select_language:{
                    required: !0
                },
                select_time:{
                    required: !0
                },
                select_house:{
                    required: !0
                }
            },
            messages: {
                familyRela:{
                    required: "此项为必填项",
                },
                username: {
                    required: "此项为必填项"
                },
                birthday: {
                    required: "此项为必填项"
                },
                language:{
                    required: "此项为必填项"
                },
                phone:{
                    required: "此项为必填项",
                    phone: "请填写正确的手机号",
                     digits:"请输入正确的手机号"
                },
                email:{
                    required: "此项为必填项",
                    email: "请填写合法的email地址"
                },
                select_language:{
                    required: "此项为必填项"
                },
                select_time:{
                    required: "此项为必填项"
                },
                select_house:{
                    required: "此项为必填项"
                }
            },
    })

    $(".outBtn").click(function(){
        $(".submit").click();
    });


$(".medical-form").submit(function(e) {
    e.preventDefault();
        if ($(".medical-form").valid()) {
          
            var t=$(".medical-form").serializeArray();
            var layerload = layer.load();
            $.ajax({
                url: window.__editurl__,
                type: "POST",
                data: $.param(t),
                success: function(re) {
                 
                   
                   
                    layer.close(layerload);
                                  
                          if(re.code==1){
                              
                             window.location.href=re.url;
                          }else{
                              layer.alert(re.msg);
                          }
                }
            })
            
        }
      
        return false;
    })
});