<?php
/** Page des équipements weenect / tracker
 * pas de boutons nouveau, les équipements sont créés automatiquement selon les données de l'API
 */
require_once dirname(__FILE__) . '/../../core/class/weenect.class.php';
require_once dirname(__FILE__) . '/../../core/class/weenect_zone.class.php';
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('weenect');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
$confList = weenect::W_CONF_common;
?>
<?php include_file('desktop', 'weenect', 'css', 'weenect'); ?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-mobile"></i> {{Mes Trackers}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				
				echo '<span class=" displayTableRight">';
				echo '<span class="">{{id}} :'.$eqLogic->getConfiguration('tracker_id').'</span>';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-map-marked-alt"></i> {{Tracker}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
			<li role="presentation"><a href="#zonetab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-map-pin"></i> {{Zones}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
											echo '<label class="checkbox-inline">';
											echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
											echo '</label>';
										}									
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>
							<legend><i class="fas fa-map-marked-alt"></i> {{Widget}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Afficher l'historique}}
								<sup><i class="fas fa-question-circle tooltips" title="{{Affiche l'historique du tracker selon le temps défini dans la configuration du plugin}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="show_history" checked>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Zoom par défaut}}
								<sup><i class="fas fa-question-circle tooltips" title="{{zoom par défaut lors de l'affichage de la tuile}}"></i></sup>
								</label>
								<div class="col-sm-4">
									<input type="range" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="default_zoom" min="10" max="20" step="1">
									
								</div>
								<div class="col-sm-2">
									<span id="default_zoom_value"></span>
								</div>
							</div>

							
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche un champ de commentaire par défaut mais vous pouvez y mettre ce que vous voulez -->
						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Information Tracker}}</legend>
							<?php
							foreach ($confList as $key=>$param){
								echo '<div class="form-group"';
								if(array_key_exists('hidden', $param))echo 'hidden=';
								echo '>';
								echo '<label class="col-sm-4 control-label">';
								echo __($param['name'], __FILE__);
								if($param['info']){
									echo '<sup><i class="fas fa-question-circle tooltips" title="';
									echo $param['info'];
									echo '"></i></sup>';
								}
								echo '</label>';
								echo '<div class="col-sm-6">';
								echo '<span class="eqLogicAttr " data-l1key="configuration" data-l2key="';
								echo $key;
								echo '" disabled></span>';
								echo '</div>';
								echo '</div>';



							}
							?>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->
			<!-- Onglet des zones de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="zonetab">
				
				<div  class="col-lg-12	">
					<legend><i class="fas fa-map"></i> {{liste des zones affiliées à l'équipement}}</legend>
					<div  class="col-lg-12">
						<div id="zone_container" class="eqLogicThumbnailContainer" style="display:flex;">
						<!-- Ajax loaded Zones -->
						</div>
					</div>
				</div>
			</div><!-- /.tabpanel #zonetab-->


		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php 
$weenectJS=version_compare(jeedom::version(), '4.4.0', '<')?'weenect_4.3':'weenect_4.4';
log::add("weenect", 'debug','weenect - Load JS :'.$weenectJS);
include_file('desktop', $weenectJS, 'js', 'weenect');

include_file('desktop', 'weenect', 'js', 'weenect');
?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>
