<?php

if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}

function formulaires_afficher_log_import_charger_dist() {
	$valeurs = array(
		'id_log' => '',
		'log' => ''
	);
	return $valeurs;
}

function formulaires_afficher_log_import_verifier_dist() {
	$erreurs = array();
	if (!_request('id_log')) {
		$erreurs['id_log'] = "Information obligatoire";
	}
	return $erreurs;
}

function formulaires_afficher_log_import_traiter_dist() {
	$res = array();
	$res['editable'] = true;
	return $res;
}
