<?php


if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}

/**
 * Traiter le fichier : soit le copier, soit le supprimer
 * @param  array  $fichier
 * @param  boolean $supprimer [description]
 * @return void|array
 */
function inc_traiter_fichier($fichier, $supprimer = false) {
	$infos_fichier = array();

	if ($supprimer) {
		traiter_fichier_supprimer();
	} else {
		return $infos_fichier = traiter_fichier_deplacer($fichier);
	}
}


/**
 * Déplacer le fichier d'import dans le dossier temporaire
 *
 * @param  array $fichier
 * 		Le fichier posté
 * @return array
 * 		Description du fichier copié dans le dossier temporaire
 */
function traiter_fichier_deplacer($fichier) {
	if(!function_exists('deplacer_fichier_upload')){
		include_spip('inc/documents');
	}

	if(!function_exists('sous_repertoire')){
		include_spip('inc/flock');
	}

	$repertoire = sous_repertoire(_DIR_IMPORTS_ASSOCIATIONS.'imports_associations/');

	$infos_fichier = array();
	$hash = substr(md5(time()), 0, 5);
	$chemin = $fichier['tmp_name'];
	$nom = basename($fichier['name']);

	if ($nom != null and $fichier['error'] == 0 and $chemin_aleatoire = tempnam($repertoire, $hash.'_')) {
		$extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
		$extension_old = $extension;
		$extension = corriger_extension($extension);
		$nom = str_replace(".$extension_old", ".$extension", $nom);
		$chemin_aleatoire = $chemin_aleatoire.".$extension";

		if (deplacer_fichier_upload($chemin, $chemin_aleatoire, false)) {
			$infos_fichier['tmp_name'] = $chemin_aleatoire;
			$infos_fichier['name'] = $nom;
			$infos_fichier['extension'] = $extension;
			$infos_fichier['size'] = $fichier['size'];
			$infos_fichier['type'] = $fichier['type'];
		}
	}

	return $infos_fichier;
}


/**
 * Supprimer le fichier après l'importation des données
 * @return void
 */
function traiter_fichier_supprimer() {
	if(!function_exists('sous_repertoire')){
		include_spip('inc/flock');
	}
	$repertoire = sous_repertoire(_DIR_IMPORTS_ASSOCIATIONS.'imports_associations/');

	// Si on entre bien dans le répertoire
	if ($ressource_repertoire = opendir($repertoire)) {
		$fichiers = array();

		// On commence par supprimer les plus vieux
		while ($fichier = readdir($ressource_repertoire)) {
			if (!in_array($fichier, array('.', '..', '.ok'))) {
				$chemin_fichier = $repertoire.$fichier;

				if (is_file($chemin_fichier)) {
					supprimer_fichier($chemin_fichier);
				}
			}
		}
	}
}
