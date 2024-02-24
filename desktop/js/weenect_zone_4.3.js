/** Pour jeedom < 4.4, la population de l'eqLogic n'est pas lancé sur le jeedom.loadPage
 * pour y pallier, load lancé sur l'event document.ready 
 * copy de "core/core/js/plugin.template.js" L202
 */

$(document).ready(function(){
    var zId = getUrlVars('id');

    jeedom.eqLogic.cache.getCmd = Array()
  if ('function' == typeof(prePrintEqLogic)) {
    prePrintEqLogic(zId)
  }
  jeedom.eqLogic.print({
    type: "weenect_zone",
    id: zId,
    status: 1,
    getCmdState : 1,
    error: function(error) {
      $.fn.showAlert({
        message: error.message,
        level: 'danger'
      })
    },
    success: function(data) {
      $('body .eqLogicAttr').value('')
      if (isset(data) && isset(data.timeout) && data.timeout == 0) {
        data.timeout = ''
      }
      $('body').setValues(data, '.eqLogicAttr')
      if (!isset(data.category.opening)) $('input[data-l2key="opening"]').prop('checked', false)

      if ('function' == typeof(printEqLogic)) {
        printEqLogic(data)
      }
      $('.cmd').remove()
      for (var i in data.cmd) {
        if(data.cmd[i].type == 'info'){
          data.cmd[i].state = String(data.cmd[i].state).replace(/<[^>]*>?/gm, '');
          data.cmd[i]['htmlstate'] =  '<span class="cmdTableState"';
          data.cmd[i]['htmlstate'] += 'data-cmd_id="' + data.cmd[i].id+ '"';
          data.cmd[i]['htmlstate'] += 'title="{{Date de valeur}} : ' + data.cmd[i].valueDate + '<br/>{{Date de collecte}} : ' + data.cmd[i].collectDate;
          if(data.cmd[i].state.length > 50){
            data.cmd[i]['htmlstate'] += '<br/>'+data.cmd[i].state.replaceAll('"','&quot;');
          }
          data.cmd[i]['htmlstate'] += '" >';
          data.cmd[i]['htmlstate'] += data.cmd[i].state.substring(0, 50) +  ' ' + data.cmd[i].unite;
          data.cmd[i]['htmlstate'] += '<span>';
        }else{
          data.cmd[i]['htmlstate'] = '';
        }
        addCmdToTable(data.cmd[i])
      }
      $('.cmdTableState').each(function() {
        jeedom.cmd.addUpdateFunction($(this).attr('data-cmd_id'), function(_options) {
          _options.display_value = String(_options.display_value).replace(/<[^>]*>?/gm, '');
          let cmd = $('.cmdTableState[data-cmd_id=' + _options.cmd_id + ']')
          let title = '{{Date de collecte}} : ' + _options.collectDate+' - {{Date de valeur}} ' + _options.valueDate;
          if(_options.display_value.length > 50){
            title += ' - '+_options.display_value;
          }
          cmd.attr('title', title)
            cmd.empty().append(_options.display_value.substring(0, 50) + ' ' + _options.unit);
          cmd.css('color','var(--logo-primary-color)');
          setTimeout(function(){
            cmd.css('color','');
          }, 1000);
        });
      })
      $('#div_pageContainer').on({
        'change': function(event) {
          jeedom.cmd.changeType($(this).closest('.cmd'))
        }
      }, '.cmd .cmdAttr[data-l1key=type]')

      $('#div_pageContainer').on({
        'change': function(event) {
          jeedom.cmd.changeSubType($(this).closest('.cmd'))
        }
      }, '.cmd .cmdAttr[data-l1key=subType]')

      jeedomUtils.addOrUpdateUrl('id', data.id)
      $.hideLoading()
      modifyWithoutSave = false
      setTimeout(function() {
        modifyWithoutSave = false
      }, 1000)
    }
  })
  return false

});
