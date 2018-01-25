<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('mystrom');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
           </ul>
       </div>
   </div>

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend>{{Mes équipements mystrom}}
    </legend>

    <div class="eqLogicThumbnailContainer">
    <?php
foreach ($eqLogics as $eqLogic) {
	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
	echo "<center>";
	if($eqLogic->getConfiguration('mystromType') == 'mst'){
		echo '<img src="plugins/mystrom/doc/images/ecn_mst_fr.png" height="105" />';
	} else {
		if($eqLogic->getConfiguration('mystromType') == 'eth'){
			echo '<img src="plugins/mystrom/doc/images/ecn_eth_fr.png" height="105" />';
		} else {
			if($eqLogic->getConfiguration('mystromType') == 'wsw'){
				echo '<img src="plugins/mystrom/doc/images/ecn_wsw.png" height="105" />';
			} else {
                if($eqLogic->getConfiguration('mystromType') == 'wbp'){
                    echo '<img src="plugins/mystrom/doc/images/wpb.png" height="105" />';
                } else {
                    if($eqLogic->getConfiguration('mystromType') == 'wbs'){
                        echo '<img src="plugins/mystrom/doc/images/wbs.png" height="105" />';
                    } else {
                        if($eqLogic->getConfiguration('mystromType') == 'wrb'){
                            echo '<img src="plugins/mystrom/doc/images/wrb.png" height="105" />';
                        } else {
                            echo '<img src="plugins/mystrom/doc/images/ecn_sw_fr.png" height="105" />';
                        }
                    }
                }
			}
		}
	}
	echo "</center>";
	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
	echo '</div>';
}
?>
</div>
</div>

<!-- edit/new form -->
<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <form class="form-horizontal">
        <fieldset>
            <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}  <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement MyStrom}}</label>
                <div class="col-sm-3">
                    <input id="eqLogicId" type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input id="eqLogicName" type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l\'équipement MyStrom}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Type}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="mystromType">
                        <option value="">{{Aucun}}</option>
                        <option value="mst">{{CPL master (rouge)}}</option>
                        <option value="sw">{{CPL escalve (blanc)}}</option>
                        <option value="eth">{{CPL avec internet (bleu)}}</option>
                        <option value="wsw">{{Interrupteur wifi swiss}}</option>
                        <option value="wse">{{Interrupteur wifi europe}}</option>
                        <option value="wbp">{{Wifi bouton plus}}</option>
                        <option value="wbs">{{Wifi bouton simple}}</option>
                        <option value="wrb">{{Ampoule}}</option>
                   </select>
               </div>
           </div>
            <div class="form-group" id="ipAddressCtrl">
                <label class="col-sm-3 control-label">{{Adresse IP}}</label>
                <div class="col-sm-3">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ipAddress" placeholder="{{192.168.1.12}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
                   </select>
               </div>
           </div>
       <div class="form-group">
            <label class="col-sm-3 control-label" >{{Activer}}</label>
            <div class="col-sm-9">
               <input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
           </div>           
       </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" >{{Visible}}</label>
            <div class="col-sm-9">
               <input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
           </div>
        </div>
			 <div class="form-group" id="logicalIdCtrl">
			 	<label class="col-sm-3 control-label">{{Identifiant}}</label>
			 	<div class="col-sm-9">
				 	<span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="logicalId"></span>
			 	</div>
		 </div>
</fieldset>
<script>    
    $('#eqLogicId').change(function(){
        var eqId = $('#eqLogicId').val();

        if(eqId !== '')
        {
            var currentEqLogic = jeedom.eqLogic.cache.byId[eqId].result;

            if(!currentEqLogic)
            {
                jeedom.eqLogic.byId({id:eqId, async: false});
                currentEqLogic = jeedom.eqLogic.cache.byId[eqId].result;
            }

            if(currentEqLogic.configuration.isLocal === true)
            {
                $('#eqLogicName').prop("disabled", false);
            } else {
                $('#eqLogicName').prop("disabled", true);
            }
        }
    });    
</script>
</form>

<legend>{{Equipement MyStrom}}</legend>
<a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th>{{Nom}}</th><th>{{Type}}</th><th>{{Action}}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<form class="form-horizontal">
    <fieldset>
        <div class="form-actions">
            <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
            <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
        </div>
    </fieldset>
</form>
</div>
</div>

<?php include_file('core', 'plugin.template', 'js');?>
<?php include_file('desktop', 'mystrom', 'js', 'mystrom');?>
