<?php

if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}


function a2ta_data_optimiser_base_disparus($flux) {

	sql_delete('spip_associations', "statut='poubelle' AND maj < " . $flux['args']['date']);

	return $flux;
}


/**
 * Modifier les saisies adresse/email/téléphone :
 *  - suppression du titre
 *  - type "professionnel" par défaut
 *
 * @param  array $flux Le flux du pipeline
 * @return array       Le flux modifié
 */
function a2ta_data_formulaire_saisies($flux) {
  $forms = array('editer_adresse', 'editer_email', 'editer_numero');

  if (in_array($flux['args']['form'], $forms)) {
    include_spip('inc/saisies');
    $flux['data'] = saisies_supprimer($flux['data'], 'titre');
    $flux['data'] = saisies_modifier($flux['data'], 'type', array('options' => array('defaut' => _COORDONNEES_TYPE_DEFAUT)));
  }

  return $flux;
}

/**
 * Réduire la liste des réseaux sociaux à (facebook, twitter, instagram)
 * qui seront les seuls pris en compte dans les exports json des associations.
 *
 * Utilise le pipeline du plugin rezosocios
 *
 * @param  array $flux
 * @return array
 */
function a2ta_data_rezosocios_liste($flux) {
	$rezos = array(
		'facebook' => $flux['facebook'],
		'twitter' => $flux['twitter'],
		'instagram' => $flux['instagram']
	);
	$flux = $rezos;
	return $flux;
}
