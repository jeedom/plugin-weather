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
      <label class="col-sm-3 control-label">{{Calcul des Degrés Jours Unifiés (DJU)}}</label>
      <div class="input-group col-sm-5">
        <input type="checkbox" class="eqLogicAttr form-control configKey" data-l1key="calculDJU" id="calculDJU" onchange="checkboxChanged()">
      </div>
    </div>

    <div id="divCalcul">
      <div class="form-group">
        <label class="col-sm-3 control-label">{{Méthode de calcul}}</label>
        <div class="input-group col-sm-5">
        <?php
            if(config::byKey('methodeDJU', 'weather') == '') {
              config::save('methodeDJU', 'meteo', 'weather');
            }
            ?>
          <select class="eqLogicAttr form-control configKey" data-l1key="methodeDJU">
              <option value="meteo">{{Météo}}</option>
              <option value="profEnergie">{{Professionnels de l'énergie}}</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-3 control-label">{{Température de référence}}</label>
        <div class="input-group col-sm-5">
          <input class="eqLogicAttr form-control configKey" data-l1key="temperatureReference" type="number">
          <?php
            if(config::byKey('temperatureReference', 'weather') == '') {
              config::save('temperatureReference', '18', 'weather');
            }
            ?>
        </div>
      </div>
    </div>
    
  </fieldset>
</form>

<script>
  function checkboxChanged() {
      var checkbox = document.getElementById("calculDJU");
      var div = document.getElementById("divCalcul");
      if (checkbox.checked) {
          div.style.display = "block";
      } else {
          div.style.display = "none";
      }
  }
</script>
