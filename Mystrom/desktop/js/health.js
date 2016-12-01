function display_health_info(){
  jeedom.mystrom.devices.getAll({
       error: function (error) {
           $('#div_mystromHealthAlert').showAlert({message: error.message, level: 'danger'});
       },
       success: function (data) {
         var tbody = '';
         for (var i = 0; i < data.length; i++) {
           tbody += data[i].isEnable === '0' ?  '<tr>' : '<tr class="active">';
           tbody += '<td>';
           tbody += data[i].state === 'offline' && data[i].isEnable === '1' ? '<span  class="label label-danger" style="font-size : 1em;">' : '<span  class="label label-success" style="font-size : 1em;">'
           tbody += data[i].name + '</span></td>';
           tbody += '<td>' + data[i].logicalId + '</td>';
           tbody += '<td>' + data[i].state + '</td>';
           tbody += '</tr>';
         }

         $('#table_healthMystrom tbody').empty().append(tbody)
       }});
}

display_health_info();
