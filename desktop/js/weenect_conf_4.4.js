/**-----  bind de l'évent sur le bouton pour lancer la requete de login via l'api pour récupération du token.
 * via appel ajax
*/
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


 
  /**-----  bhook du post save pour mettre à jour ou créer les équipementn tracker et zone
 * via appel ajax
  */
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