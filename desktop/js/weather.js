
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

$(".li_eqLogic").on('click', function(event) {
    printWeather($(this).attr('data-eqLogic_id'));
    return false;
});

function addCmdToTable() {
}

function printWeather(_weatherEq_id) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/weather/core/ajax/weather.ajax.php", // url du fichier php
        data: {
            action: "getWeather",
            id: _weatherEq_id
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#table_weather tbody').empty();
            $('#div_weather').empty();
            for (var i in data.result.cmd) {
                var tr = '<tr>';
                tr += '<td>' + data.result.cmd[i].name + '</td>';
                tr += '<td>' + data.result.cmd[i].value;
                if (data.result.cmd[i].unite != null) {
                    tr += ' ' + data.result.cmd[i].unite;
                }
                tr += '</td>';
                tr += '</tr>';
                $('#table_weather tbody').append(tr);
            }

        }
    });
}
