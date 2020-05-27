<?php

if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}

include_spip('inc/cextras');
include_spip('base/a2ta_data');


function a2ta_data_upgrade($nom_meta_base_version, $version_cible) {
  $maj = array();

  cextras_api_upgrade(a2ta_data_declarer_champs_extras(), $maj['create']);

  $maj['create'][] = array('maj_tables', array('spip_associations', 'spip_associations_imports'));

  $maj['create'][] = array('a2ta_data_configurer_dependances');

  include_spip('base/upgrade');
  maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


function a2ta_data_vider_tables($nom_meta_base_version) {
  cextras_api_vider_tables(a2ta_data_declarer_champs_extras());
  sql_drop_table('spip_associations');
  sql_drop_table('spip_associations_imports');

  # Nettoyer les liens courants (le génie optimiser_base_disparus se chargera de nettoyer toutes les tables de liens)
  sql_delete('spip_documents_liens', sql_in('objet', array('association')));
  sql_delete('spip_mots_liens', sql_in('objet', array('association')));
  sql_delete('spip_auteurs_liens', sql_in('objet', array('association')));
  sql_delete('spip_adresses_liens', sql_in('objet', array('association')));
  sql_delete('spip_emails_liens', sql_in('objet', array('association')));
	sql_delete('spip_numeros_liens', sql_in('objet', array('association')));
  sql_delete('spip_gis_liens', sql_in('objet', array('association')));
  sql_delete('spip_rezosocios_liens', sql_in('objet', array('association')));

  # Nettoyer les versionnages et forums
  sql_delete('spip_versions', sql_in('objet', array('association')));
  sql_delete('spip_versions_fragments', sql_in('objet', array('association')));
  sql_delete('spip_forum', sql_in('objet', array('association')));

  effacer_meta($nom_meta_base_version);
}

/**
 * Adapter la configuration des plugins nécessaires
 * (GIS, Coordonnées et Réseaux sociaux)
 * @return void
 */
function a2ta_data_configurer_dependances() {
	include_spip('inc/config');

	// GIS
	$gis_conf = lire_config('gis', array());
	$gis_conf_associations = array(
    // Plus ou moins le centre de la France
		'lat' => '46.4947387',
		'lon' => '2.6028326',
		'zoom' => '6',
		'geocoder' => 'on',
		'adresse' => 'on',
    'layer_defaut' => 'cartodb_positron',
    'plugins_desactives' => array('KML.js', 'GPX.js', 'TOPOJSON.js', 'Control.FullScreen.js', 'Control.MiniMap.js'),
		'gis_objets' => array('spip_associations'),
	);
	$gis_conf = array_merge($gis_conf, $gis_conf_associations);
	ecrire_config('gis', $gis_conf);

	// Coordonnées
	$coord_conf = lire_config('coordonnees', array());
	$coord_conf['objets'] = array_merge($coord_conf['objets'], array('spip_associations'));
	ecrire_config('coordonnees', $coord_conf);

	// Réseaux sociaux
	$rezos_conf = lire_config('rezosocios', array());
  if (!empty($rezos_conf)) {
    $rezos_conf['rezosocios_objets'] = array_merge($rezos_conf['rezosocios_objets'], array('spip_associations'));
  } else {
    $rezos_conf['rezosocios_objets'] = array('spip_associations');
  }
	ecrire_config('rezosocios', $rezos_conf);
}
