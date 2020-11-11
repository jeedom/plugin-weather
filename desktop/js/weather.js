
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

 $('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=refreshCron]').value(result.value);
    });
});

 function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}"></td>';
    tr += '<td>';
    if(!isset(_cmd.type) || _cmd.type == 'info' ){
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    }
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr').last().setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr').last(), init(_cmd.subType));
}
