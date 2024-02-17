<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Nom d'utilisateur}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le nom d'utilisateur du compte weenect}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="username"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mot de Passe}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le mot de passe du compte weenect}}"></i></sup>
      </label>
      <div class="col-md-4" style="display:flex;">
        <input type="password" class="configKey form-control" data-l1key="password"/>
        <a class="btn btn-danger  " id="bt_show_pass"><i class="fas fa-eye"></i></a>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Fréquence de mise à jour}}
        <sup><i class="fas fa-question-circle tooltips" title="{{fréquence d'interrogation de weenect}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select id="freq_selector" class="configKey form-control" data-l1key="param3">
          <option value="manual">{{Manuel}}</option>
          <option value="* * * * *">1 {{minute}}</option>
          <option value="*/2 * * * *">2 {{minute}}</option>
          <option value="*/3 * * * *">3 {{minute}}</option>
          <option value="*/5 * * * *">5 {{minute}}</option>
          <option value="*/10 * * * *">10 {{minute}}</option>

          <option value="prog">{{Custom}}</option>
        </select>
        <span class="warning_manualupdate" style="color: orange;">le rafraichissements des données ne sera effectué qu'avec l'appel à la commande 'rafraichir'</span>
      </div>
    </div>
    <div class="form-group mgh-actu-auto">
      <label class="col-md-4 control-label">{{Auto-actualisation}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Fréquence de rafraîchissement de l'équipement}}"></i></sup>
      </label>
      <div class="col-md-4">
        <div class="input-group">
          <input type="text" class="configKey form-control roundedLeft" data-l1key="autorefresh" placeholder="{{Cliquer sur ? pour afficher l'assistant cron}}"/>
          <span class="input-group-btn">
            <a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="Assistant cron">
              <i class="fas fa-question-circle"></i>
            </a>
          </span>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="col-md-4 control-label">{{token}}
        <sup><i class="fas fa-question-circle tooltips" title="{{token récupéré pour l'accès à l'API}}"></i></sup>
      </label>
      <div class="col-md-4" style="display:flex;">
        <span id="token_status" class="configKey form-control"></span>
        <input type="hidden" class="configKey form-control" data-l1key="token" />
        <a class="btn btn-success  " id="bt_get_token">{{Get Token}}</a>
      </div>
    </div>

  </fieldset>
</form>

<?php include_file('desktop', 'weenect_conf', 'js', 'weenect'); ?>