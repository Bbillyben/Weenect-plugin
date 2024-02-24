/* Javascript load in weenect plugin conf page
*/


/**-----  bind des évènement pour le bouton afficher/masquer le mot de passe
*/
$("#bt_show_pass").on('mousedown', function(){
    $("input[data-l1key='password']").attr('type', 'text');
    $(this).find("i").removeClass('fa-eye').addClass('fa-eye-slash')

})
$("#bt_show_pass").on('mouseup mouseleave', function(){
    $("input[data-l1key='password']").attr('type', 'password');
    $(this).find("i").removeClass('fa-eye-slash').addClass('fa-eye')
})

/**-----  bind des évènement pour l'affichage du cron personnalisé selon la configuiration du dropdown
*/
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

/**-----  vérifie si le token est renseigné dans l'input de la configuration (masqué)
*/
function check_token_status(){
  if($("input[data-l1key='token']").val()!= ""){
    $("#token_status").html('<i class="fa fa-check" style="color:green;"></i>');
  }else{
    $("#token_status").html('<i class="fa fa-x" style="color:red;">X</i>');
  }
  
  }

  $(document).ready(function () {
    $('.configKey[data-l1key="token"]').on('change', function(){
      check_token_status();
    })
  });
