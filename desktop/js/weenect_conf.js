// show hide password button
$("#bt_show_pass").on('mousedown', function(){
    $("input[data-l1key='password']").attr('type', 'text');
    $(this).find("i").removeClass('fa-eye').addClass('fa-eye-slash')

})
$("#bt_show_pass").on('mouseup mouseleave', function(){
    $("input[data-l1key='password']").attr('type', 'password');
    $(this).find("i").removeClass('fa-eye-slash').addClass('fa-eye')
})

// hide la programmation du cron.
$("#freq_selector").on('change', function () {
    // console.log("freq_selector change :"+$(this).val() );
    if($(this).val() != 'prog'){
      $(".mgh-actu-auto").hide();
    }else{
      $(".mgh-actu-auto").show();
    }
    if($(this).val() != 'manual'){
      $(".warning_manualupdate").hide();
    }else{
      $(".warning_manualupdate").show();
    }
  });

  function check_token_status(){
    // console.log("check_token_status :"+$("input[data-l1key='token']").val() );
    if($("input[data-l1key='token']").val()!= ""){
      $("#token_status").html('<i class="fa fa-check" style="color:green;"></i>');
    }else{
      $("#token_status").html('<i class="fa fa-x" style="color:red;">X</i>');
    }
    
  }


  // refresh du token manually
  $("#bt_get_token").on('click', function(){
    var uname = $('.configKey[data-l1key="username"]').val();
    var pass = $('.configKey[data-l1key="password"]').val();
    // console.log("refresh token asked : "+uname+" @ "+pass);
    if(uname=="" || pass=="" || uname == undefined || pass == undefined){
        jeedomUtils.showAlert({
            message: "Vous devez renseigner le nom d'utilisateur et le mot de passe",
            level: 'danger'
          })
          check_token_status();
          return;
    }
    $.ajax({
        type: "POST", 
        url: "plugins/weenect/core/ajax/weenect.ajax.php", 
        data: {
            action: "refresh_token", 
            username:uname,
            password:pass
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
            check_token_status();
        },
        success: function (data) { // si l'appel a bien fonctionné
            // console.log(JSON.stringify(data));
            if(data.state =="error"){
                jeedomUtils.showAlert({
                    message: data.result,
                    level: 'danger'
                  })
                  return;
            }
            $(".configKey[data-l1key='token']").val(data.result);
            jeedomUtils.showAlert({
                message: "token mis à jour",
                level: 'success'
              })
              check_token_status();
        }
    });

  })

  $(document).ready(function () {
    check_token_status();
  });
 

  // post save ajax call 
  // => to launch the update and create eqLogic
  function weenect_postSaveConfiguration() {
    console.log("weenect_postSaveConfiguration call");
    $.ajax({
      type: "POST", 
      url: "plugins/weenect/core/ajax/weenect.ajax.php", 
      data: {
          action: "update_data", 
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) { // si l'appel a bien fonctionné
          console.log(JSON.stringify(data));
          if(data.state =="error" || data.result == false){
              jeedomUtils.showAlert({
                  message: data.result || "Error in Data Update",
                  level: 'danger'
                })
                return;
          }
          jeedomUtils.showAlert({
              message: "Equipement mis à jour",
              level: 'success'
            })
            check_token_status();
      }
  });
  
  
  }