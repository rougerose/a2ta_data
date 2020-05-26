<?php

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

function formulaires_importer_associations_saisies_dist() {
	$saisies = array(
		array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'fichier',
				'label' => _T('a2ta_data:label_importer'),
				'type' => 'file'
			),
			'verifier' => array(
				'type' => 'fichiers',
				'options' => array(
					'mime' => 'specifique',
					'mime_specifique' => array('text/csv')
				)
			)
		),
		array(
			'saisie' => 'radio',
			'options' => array(
				'nom' => 'publier',
				'label' => _T('a2ta_data:label_importer_publier'),
				'defaut' => '0',
				'data' => array('1' => 'Oui', '0' => 'Non')
			)
		)
	);
	return $saisies;
}


function formulaires_importer_associations_charger_dist() {
	$valeurs = array(
		'fichier' => '',
		'publier' => ''
	);
	return $valeurs;
}


function formulaires_importer_associations_verifier_dist() {
	$erreurs = array();
	$fichier = $_FILES['fichier'];

	if (is_null($fichier)) {
		$erreurs['message_erreur'] = _T('a2ta_data:importer_pas_de_fichier');
	}
	return $erreurs;
}

function formulaires_importer_associations_traiter_dist() {
	$retour = array();

	$fichier = $_FILES['fichier'];

	// Copier le fichier dans un répertoire temporaire
	include_spip('action/editer_objet');
	$traiter_fichier = charger_fonction('traiter_fichier', 'inc');

	if ($infos_fichier = $traiter_fichier($fichier)) {
		// Importer les données CSV
		$importer_csv = charger_fonction('importer_csv', 'inc');
		$datas = $importer_csv($infos_fichier['tmp_name'], true);

		if (count($datas)) {
			//
			// Inspiré du script d'importation de La Fabrique
			//
			// Inserer les donnees en base.
			$nb_inseres = 0;
			$nb_deja_la = 0;
			$inserts = array();
			$publier = _request('publier');

			// Ne pas réimporter ceux déjà présents. La vérification se fait
			// uniquement sur le nom de l'association.
			foreach ($datas as $data) {
				if (sql_getfetsel('nom', 'spip_associations', 'nom='.sql_quote(trim($data['nom'])))) {
					$nb_deja_la++;
				} else {
					$inserts[] = $data;
				}
			}

			$nb_a_inserer = count($inserts);

			if ($nb_a_inserer) {
				$champs_import = array('date' => date("Y-m-d H:i:s"));
				$id_association_import = objet_inserer('associations_import', null, $champs_import);

				// Découper par lot de 50
				$inserts = array_chunk($inserts, 50);

				$importer_association = charger_fonction('importer_association', 'inc');

				foreach ($inserts as $insert) {
					$resultat_insert = $importer_association($id_association_import, $insert, $publier);
					$nb_inseres += count($insert);

					// Problème de nom sur une association
					if (!$resultat_insert) {
						$retour['message_erreur'] = "Le nom d'une association est manquant. Veuillez vérifier les données source.";
						break;
					} else {
						// Arrêter la boucle à 50
						if ($nb_inseres == 50) {
							$a_faire = $nb_a_inserer - $nb_inseres;
							$message = "Relancer l'importation.<br>$nb_deja_la associations sont déjà importées.<br>$nb_inseres associations viennent d'être importées.<br>$a_faire restent à importer.";
							$retour['message_erreur'] = $message;
							break;
						}
						$retour['message_ok'] = "Fichier importé.";
					}
				}
			} else {
				$retour['message_erreur'] = "Les associations issues de ce fichier sont déjà importées.";
			}
		} else {
			$retour['message_erreur'] = _T('a2ta_data:importer_pas_de_donnees');
		}
	} else {
		$retour['message_erreur'] = _T('a2ta_data:importer_probleme_fichier');
	}

	// effacer le fichier
	$traiter_fichier($fichier, true);

	$retour['editable'] = true;
	return $retour;
}
