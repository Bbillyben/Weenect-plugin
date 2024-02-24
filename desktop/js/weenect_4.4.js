/**-----  appel ajax pour récupération des zone du tracker
 * _eqLogic : json de l'eqlogic en cours d'impression
*/
function weenect_load_zones(_eqLogic){
    $.ajax({
      type: "POST", 
      url: "plugins/weenect/core/ajax/weenect.ajax.php", 
      data: {
          action: "load_zone", 
          eqlogic: _eqLogic,
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
          $("#zone_container").html(
            "Error Loading Zones"
          );
      },
      success: function (data) { // si l'appel a bien fonctionné
          // console.log("success :"+JSON.stringify(data));
          if(data.state =="error"){
              jeedomUtils.showAlert({
                  message: "error load zone :"+data.result,
                  level: 'danger'
                })
                return;
          }else{
            $("#zone_container").html(
              data.result
            );
            bind_weenect_zone();
          }
          
      }
  });
  }