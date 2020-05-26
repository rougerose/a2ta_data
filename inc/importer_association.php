<?php

if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}

include_spip('action/editer_objet');
include_spip('action/editer_liens');
include_spip('action/editer_gis');
include_spip('action/editer_rezosocio');
include_spip('inc/filtres');
include_spip('inc/modifier'); // collecter_requests()


/**
 * Importer les données d'une association
 * @param  integer  $id_association_import
 * 	A chaque import une ligne est ajoutée dans la table associations_imports
 * 	qui contiendra les id des associations importées. Cela permet a postériori
 * 	de consulter le log d'import de chaque association.
 * @param  array  $donnees
 * 	Les données relative à l'association
 * @param  integer $publier
 * 	Faut-il publier l'association ?
 * @return boolean
 */
function inc_importer_association($id_association_import, $donnees, $publier = 0) {
	$verifier = charger_fonction('verifier', 'inc');
	foreach ($donnees as $row) {
		$resultat = true;
		$log = array();

		$row = array_map('trim', $row);

		/*----------- NOM -----------*/

		// TODO: Actuellement il n'y a aucune vérification sur un éventuel doublon
		// entre le fichier source et les asso déjà enregistrées sur le site.
		// A modifier dans un second temps ?

		if (!$row['nom']) {
			// Pas de nom : on arrête ici
			$resultat = false;
			break;

		} else {
			$log['nom'] = "Nom: ".$row['nom']."\n";
		}

		/*----------- MEMBRE FRAAP -----------*/

		$membre = strtolower($row['membre']);

		if (!$membre or $membre == 'non') {
			$membre = 0;
			$log['membre'] = "Membre Fraap: Non | ";

		} else {
			$membre = 1;
			$log['membre'] = "Membre Fraap: Oui | ";
		}

		// membre_fraap est l'index attendu
		$row['membre_fraap'] = $membre;
		unset($row['membre']);

		/*----------- DATE DE CRÉATION -----------*/

		if ($date = $row['date_creation']) {
			// vérifier la date qui est au format 'amj'
			// mais ne pas normaliser avec l'API qui
			// le fait sur la base d'un format 'jma'
			// (est-ce un bug ? En tout cas, la contrainte n'est pas explicite).
			if ($erreur_date = $verifier($date, 'date', array('format' => 'amj'))) {
				$row['date_creation'] = '';
				$log['date_creation'] = "Date de création: ** $erreur_date ** | ";

			} else {
				// Mettre au format 'jma' et normaliser avec date()
				list($annee, $mois, $jour) = explode('-', $date);
				$date = $jour.'-'.$mois.'-'.$annee;
				$date = date("Y-m-d H:i:s", strtotime($date));
				$row['date_creation'] = $date;
				// Log : remettre la date au format de la source
				$log['date_creation'] = "Date de création: ".date('Y-m-d', strtotime($date))." | ";
			}
		}

		/*----------- URL SITE WEB PRINCIPAL -----------*/

		if ($url_site = $row['web1']) {
			// Vérifier si http(s) est bien présent
			$url_site = importer_verifier_protocole_url($url_site);
		}

		$row['url_site'] = $url_site;
		// url_site est l'index attendu pour l'insertion
		unset($row['web1']);
		$log['url_site'] = ($url_site) ? "Site principal: $url_site | " : "Site principal: Ø | ";

		/*----------- URL SITE WEB SECONDAIRE -----------*/

		if ($url_site_supp = $row['web2']) {
			$url_site_supp = importer_verifier_protocole_url($url_site_supp);
		}

		$row['url_site_supp'] = $url_site_supp;
		unset($row['web2']);
		$log['url_site_supp'] = ($url_site_supp) ? "Site secondaire: $url_site_supp | " : "Site secondaire: Ø | ";

		/*----------- L'ASSOCIATION -----------*/

		$asso_champs = array(
			'nom' => $row['nom'],
			'membre_fraap' => $row['membre_fraap'],
			'date_creation' => $row['date_creation'],
			'url_site' => $row['url_site'],
			'url_site_supp' => $row['url_site_supp']
		);

		$id_association = objet_inserer('association', null, $asso_champs);

		if (intval($id_association)) {

			// Traiter et insérer toutes les données
			// associées à une association :
			// - Adresse et géolocalisation
			// - Email
			// - Réseaux sociaux
			// - Activités

			/* Adresse */
			$log_adresse = importer_association_adresse($id_association, $row);
			$log += $log_adresse;

			/* Email */
			$log_email = importer_association_email($id_association, $row);
			$log += $log_email;

			/* Réseaux sociaux */

			$log_rezos = importer_association_rezos($id_association, $row);
			$log += $log_rezos;

			/* Activités */

			// Récupérer les activités et les insérer une à une dans un tableau.
			$activites = explode(';', $row['activites']);

			// Suppression des accents.
			$activites = array_map('importer_accents_convertir', $activites);

			// Récupérer les mots-clés Activités du site
			if (!isset($mots_cles_sql) and !isset($mots_cles)) {
				$mots_cles_sql = sql_allfetsel('id_mot, titre', 'spip_mots', 'id_groupe=1');

				// Insérer chaque mot-clé dans un tableau "simple" (id_mot => titre).
				// Et suppression des accents sur le titre des mots-clés.
				$mots_cles = array();

				foreach ($mots_cles_sql as $mot) {
					$mots_cles[$mot['id_mot']] = importer_accents_convertir($mot['titre']);
				}
			}

			$log_activites = importer_association_activites($id_association, $activites, $mots_cles);
			$log += $log_activites;

			/*----------- ENREGISTREMENT DES LOGS -----------*/

			if ($log and count($log)) {
				$log_text = '';

				foreach ($log as $l) {
					$log_text .= $l;
				}

				$asso_champs = array(
					'log_import' => $log_text
				);

				objet_modifier('association', $id_association, $asso_champs);

				if ($associations_ids = unserialize(sql_getfetsel('associations', 'spip_associations_imports', 'id_associations_import='.$id_association_import))) {
					$associations_ids[] = $id_association;

				} else {
					$associations_ids = array($id_association);
				}

				$import_champs = array(
					'associations' => serialize($associations_ids)
				);

				objet_modifier('associations_import', $id_association_import, $import_champs);
			}

			if ($publier) {
				objet_instituer('association', $id_association, array('statut' => 'publie'));
			}
		}
	}
	return $resultat;
}


/**
 * Importer et créer les données relative à l'adresse d'une asso.
 * Si les données minimum (code postal ou ville) sont
 * présentes, on créé également un point de géo-localisation.
 * @param  integer $id_association
 * @param  array $row
 * 	Les données de l'association
 * @return array
 * 	Le log relatif aux données d'adresse
 */
function importer_association_adresse($id_association, $row) {
	$log = array();

	// code postal ou une ville (type de donnée actuellement présente dans le fichier source) sont le minimum pour créer une adresse.
	if ($row['code_postal'] or $row['adresse']) {
		// Les coordonnées sont partielles.
		// En l'état actuel du fichier csv, l'adresse contient
		// le plus souvent une ville, mais pas toujours.
		// Pour simplifier, on remplit le champs "Voie" avec l'élément
		// présent. Les données seront rectifiées ultérieurement.

		$adresse_champs = array(
			'titre' => $row['nom'],
			'code_postal' => $row['code_postal'],
			'voie' => $row['adresse'],
			'pays' => _COORDONNEES_PAYS_DEFAUT
		);

		$id_adresse = objet_inserer('adresse', null, $adresse_champs);

		if (intval($id_adresse)) {
			objet_associer(
				array('adresse' => $id_adresse),
				array('association' => $id_association),
				array('type' => _COORDONNEES_TYPE_DEFAUT)
			);

			$log['adresse'] = ($adresse_champs['voie']) ? "Voie: ".$adresse_champs['voie']." | " : "Voie: Ø | ";
			$log['adresse'] .= ($adresse_champs['code_postal']) ? "Code postal: ".$adresse_champs['code_postal']." | " : "Code postal: Ø | ";
			$log['adresse'] .= "Pays: ".$adresse_champs['pays']." | ";

			$gis_champs = importer_gis_donnees_collecter($row['nom'], $adresse_champs['code_postal'], $adresse_champs['voie']);

			// Vérifier si les données minimum d'un point GIS sont présentes
			if ($gis_champs['lat'] and $gis_champs['lon']) {
				// Ajouter le point GIS et les données de l'association
				$id_gis = gis_inserer();
				gis_modifier($id_gis, $gis_champs);

				// Liaison GIS et association
				objet_associer(
					array('gis' => $id_gis),
					array('association' => $id_association)
				);

				$log['geolocalisation'] = "Géo-localisation ajoutée: point #$id_gis | ";
			} else {
				$log['geolocalisation'] = "Géo-localisation: ** Erreur ** | ";
			}

			// À partir des éléments obtenus dans la requete de geo-localisation,
			// on peut compléter l'adresse de l'association.
			if ($gis_champs['ville'] or $gis_champs['code_postal'] or $gis_champs['region']) {
				$complements_adresse = importer_adresse_completer($gis_champs['region'], $gis_champs['code_postal']);
				$complements_adresse['ville'] = $gis_champs['ville'];

				objet_modifier('adresse', $id_adresse, $complements_adresse);

				if ($complements_adresse['ville']) {
					$ville = $complements_adresse['ville'];
				} else {
					$ville = 'Ø';
				}

				if ($complements_adresse['region']) {
					$region = extraire_multi(sql_getfetsel('nom', 'spip_subdivisions', 'id_subdivision='.sql_quote($complements_adresse['region'])), 'fr');
				} else {
					$region = 'Ø';
				}

				if ($complements_adresse['departement']) {
					$departement = extraire_multi(sql_getfetsel('nom', 'spip_subdivisions', 'id_subdivision='.sql_quote($complements_adresse['departement'])), 'fr');
				} else {
					$departement = 'Ø';
				}

				$log['adresse_complements'] = "Adresse complétée après géolocalisation: Ville => $ville / Département => $departement / Région => $region | ";
			}
		}
	} else {
		$log['adresse'] = "Adresse: ** erreur ** Les données sont insuffisantes | ";
	}
	return $log;
}


/**
 * Importer et créer un email d'une association
 * @param  integer $id_association
 * @param  array $row
 * 	Les données relative à l'association
 * @return array
 * 	Le log relatif au mail
 */
function importer_association_email($id_association, $row) {
	$log = array();

	if (!$row['email']) {
		$log['email'] = "Email: Ø | ";

	} else {
		$email_champs = array('email' => $row['email']);
		$email_champs['titre'] = $row['nom'];

		$id_email = objet_inserer('email', null, $email_champs);

		if (intval($id_email)) {
			objet_associer(
				array('email' => $id_email),
				array('association' => $id_association),
				array('type' => _COORDONNEES_TYPE_DEFAUT)
			);

			$log['email'] = "Email: ".$email_champs['email']." | ";
		}
	}

	return $log;
}


/**
 * Importer et créer les réseaux sociaux d'une association
 * @param  integer $id_association
 * @param  array $row
 * 	Les données relatives à l'association
 * @return array
 * 	Le log relatif aux réseaux sociaux.
 */
function importer_association_rezos($id_association, $row) {
	$log = array();

	$rezos = array(
		'facebook' => $row['facebook'],
		'twitter' => $row['twitter'],
		'instagram' => $row['instagram']
	);

	foreach ($rezos as $type_rezo => $rezo) {
		// Nom du réseau pour l'utilisateur
		$nom_rezo = rezosocios_nom($type_rezo);

		if ($rezo) {
			$rezo_champs = array(
				'titre' => $row['nom'],
				'nom_compte' => $rezo,
				'type_rezo' => $type_rezo
			);

			$id_rezo = rezosocio_inserer();

			if (intval($id_rezo)) {
				rezosocio_modifier($id_rezo, $rezo_champs);

				objet_associer(
					array('rezosocio' => $id_rezo),
					array('association' => $id_association)
				);
				$log[$type_rezo] = $nom_rezo.": ".$rezo." | ";
			}
		} else {
			$log[$type_rezo] = $nom_rezo.": Ø | ";
		}
	}
	return $log;
}


/**
 * Vérifier le protocole http/https d'une url,
 * et éventuellement l'ajouter.
 *
 * @param  string $url
 * 	L'url à vérifier
 * @return string
 * 	L'url éventuellement corrigée arbritrairement
 * 	par l'ajout d'un http://
 */
function importer_verifier_protocole_url($url) {
	$verifier = charger_fonction('verifier', 'inc');
	$erreur = $verifier($url, 'url', array('mode' => 'protocole_seul'));

	if ($erreur) {
		$url = substr_replace($url, 'http://', 0, 0);
	}

	return $url;
}


/**
 * Collecter les données nécessaires à la géolocalisation,
 * puis envoyer la requete.
 * @param  string  $nom
 * 		Le nom de l'association
 * @param  string  $code_postal
 * 		Le code postal
 * @param  string  $voie
 * 		Adresse disponible dans le fichier d'importation
 * @return boolean|array
 * 		null si la requete n'a pas aboutie
 * 		array contenant toutes les données de création d'un point GIS
 */
function importer_gis_donnees_collecter($nom = '', $code_postal = '', $voie = '') {
	// Ecrire la requête de géo-localisation
	$query = '';

	$query_pays = sql_getfetsel('nom', 'spip_pays', 'code='.sql_quote('FR'));
	$query_pays = extraire_multi($query_pays);

	$query = ($code_postal) ? $code_postal." " : '';
	$query .= ($voie) ? $voie." " : '';
	$query .= ($query_pays);

	$query_langue = 'fr';

	$reponse = array('');
	set_request("mode","search");
	set_request("q", $query);
	set_request("accept-language", $query_langue);
	set_request("format","json");

	$arguments = collecter_requests(array('json_callback', 'format', 'q', 'limit', 'addressdetails', 'accept-language', 'lat', 'lon'), array());

	// Envoi de la requête
	$requete = importer_gis_geocoder_rechercher();

	$gis_champs = null;

	if (is_array($requete) and count($requete['features'])) {
		foreach ($requete['features'] as $key => $feature) {
			// On recherche dans la réponse le premier élément
			// dont les données sont town, city, village ou
			// suburb
			//
			// suburb : pour les arrondissements

			if ($feature['properties']['osm_key'] === 'place'
				and preg_match(
					'/town|city|village|suburb/',
					$feature['properties']['osm_value']
				))
			{
				$req_pays = $feature['properties']['country'];
				$req_region = $feature['properties']['state'];

				// Vérifier qu'il ne s'agit pas d'un arrondissement,
				// le nom de la ville est sur une clé différente.
				if ($feature['properties']['osm_value'] == 'suburb') {
					$req_ville = $feature['properties']['city'];
				} else {
					$req_ville = $feature['properties']['name'];
				}

				$req_code_postal = $feature['properties']['postcode'];
				$req_longitude = $feature['geometry']['coordinates'][0];
				$req_latitude = $feature['geometry']['coordinates'][1];

				$config_zoom = lire_config('gis/zoom');

				// Données du point GIS à enregistrer en base
				$gis_champs = array(
					'titre' => $nom,
					'lat' => $req_latitude,
					'lon' => $req_longitude,
					'zoom' => $config_zoom,
					'adresse' => $voie,
					'pays' => $req_pays,
					'region' => $req_region,
					'ville' => $req_ville,
					'code_postal' => $req_code_postal
				);

				// Arrêt de la boucle
				break;
			}
		}
	}
	return $gis_champs;
}


/**
 * Géo-localisation à partir des données de l'adresse
 * Repris et adapté depuis action/gis_geocoder_rechercher du plugin GIS
 * et de https://contrib.spip.net/Astuces-GIS#Utiliser-le-geocoder-depuis-PHP
 *
 * @return boolean [description]
 */
function importer_gis_geocoder_rechercher() {
	include_spip("inc/distant");

	$mode = _request('mode');
	if (!$mode || !in_array($mode, array('search', 'reverse'))) {
		return;
	}

	/* On filtre les arguments à renvoyer à Nomatim (liste blanche) */
	$arguments = collecter_requests(array('json_callback', 'format', 'q', 'limit', 'addressdetails', 'accept-language', 'lat', 'lon'), array());

	$geocoder = defined('_GIS_GEOCODER') ? _GIS_GEOCODER : 'photon';

	if ($geocoder == 'photon') {
		unset($arguments['format']);
		unset($arguments['addressdetails']);
	}

	if (!empty($arguments) && in_array($geocoder, array('photon','nominatim'))) {
		if ($geocoder == 'photon') {
			if (isset($arguments['accept-language'])) {
				// ne garder que les deux premiers caractères du code de langue, car les variantes spipiennes comme fr_fem posent problème
				$arguments['lang'] = substr($arguments['accept-language'], 0, 2);
				unset($arguments['accept-language']);
			}
			if ($mode == 'search') {
				$mode = 'api/';
			} else {
				$mode = 'reverse';
			}
			$url = 'http://photon.komoot.de/';
		} else {
			$url = 'http://nominatim.openstreetmap.org/';
		}

		$url = defined('_GIS_GEOCODER_URL') ? _GIS_GEOCODER_URL : $url;
		$data = recuperer_page("{$url}{$mode}?" . http_build_query($arguments));
		$data = json_decode($data,true);

		return $data;
	}
}


/**
 * Lors de l'importation des données d'une association
 * et après géo-localisation, les données Ville, Région et Département
 * peuvent être enregistrées dans l'adresse liée à l'association.
 * Avant cet enregistrement, obtenir le code de la région et du département
 *
 * @param  string $ville       Nom de la ville
 * @param  string $region      Nom de la région
 * @param  string $code_postal Code postal
 * @return array               Code du département, code de la région et nom de la ville
 */
function importer_adresse_completer($region = '', $code_postal = '') {

	$complements_adresse = array();
	$region_code = '';
	$departement_code = '';

  if ($region) {
    $id_subdivision_region = sql_getfetsel('id_subdivision', 'spip_subdivisions', 'nom LIKE '.sql_quote("%$region%"));

    if (!$id_subdivision_region) {
      $mots = preg_split("/[\s,-]+/", $region);
      $id_subdivision_region = sql_getfetsel('id_subdivision', 'spip_subdivisions', 'nom LIKE '.sql_quote("%$mots[0]%"));
    }
		$complements_adresse['region'] = ($id_subdivision_region) ? $id_subdivision_region : 0;
	}

  if ($code_postal = substr($code_postal, 0, 2))
	{
    $id_subdivision_dpt = sql_getfetsel('id_subdivision', 'spip_subdivisions', 'code='.sql_quote("FR-$code_postal"));
		$complements_adresse['departement'] = ($id_subdivision_dpt) ? $id_subdivision_dpt : 0;
	}

	return $complements_adresse;
}


/**
 * Importer les données relatives aux activités de l'association.
 * @param  integer $id_association
 * @param  array $activites
 * 	Le tableau des activités de l'association
 * @param  array $mots_cles
 * 	Le tableau des mots-clés du site relatifs aux activités
 * @return array
 * 	Le log des activités
 */
function importer_association_activites($id_association, $activites, $mots_cles) {
	$log = array();

	// Tableau des id_mot qui seront liés à l'association
	$ids_mot = array();

	$pattern = "/\([^)]*\)|(\s*,\s*)/";

	foreach ($activites as $activite) {
		// Pour éviter des recherches ambigues,
		// on supprime les mots éventuellement
		// entre parenthèses dans le libellé de
		// l'activité (fichier CSV). Ce qui permet
		// de faire une recherche plus sélective.
		// Néanmoins, la recherche reste un peu trop
		// large compte tenu des libellés utilisés
		// dans le fichier CSV sont différents de ceux
		// utilisés sur le site.

		// TODO: à partir du moment, où les titres
		// seront définis, il faudra adapter le script
		// pour faire une recherche exacte
		// titre source/titre mot-clé.

		$activite = preg_replace($pattern, " ", $activite);

		if ($index = array_search_partiel($mots_cles, $activite)) {
			$ids_mot[] = $index;
		}
	}

	// Associer les mots-clés à l'association.
	if (count($ids_mot)) {
		$liaisons_mots = objet_associer(
			array('mot' => $ids_mot),
			array('association' => $id_association)
		);

		if (intval($liaisons_mots) and $liaisons_mots >= 0) {
			$titres = '';
			foreach ($ids_mot as $id_mot) {
				$titres .= generer_info_entite($id_mot, 'mot', 'titre').", ";
			}

			$nb_mots = count($ids_mot);
			$log['activites'] = "Activités: ".$nb_mots;

			if ($nb_mots > 1) {
				$log['activites'] .= " activités enregistrées";
			} else {
				$log['activites'] .= " activité enregistrée";
			}

			$log['activites'] .= " [".$titres."] | ";

		}
	} else {
		$log['activites'] = "Activités: Ø | ";

	}
	return $log;
}


/**
 * Supprimer les caractères accentués
 * https://www.php.net/manual/fr/function.mb-ereg-replace.php#123589
 *
 * @param  string $texte
 * @return string
 */
function importer_accents_convertir($texte){
	$transliterator = Transliterator::create("NFD; [:Nonspacing Mark:] Remove; NFC;");
	return $transliterator->transliterate($texte);
}


/**
 * Recherche sur le tableau des titres de mots-clés.
 * On ne garde comme critère de recherche que le premier mot
 * du titre du mot-clé.
 * @param  [type] $mots  [description]
 * @param  [type] $texte [description]
 * @return [type]        [description]
 */
function array_search_partiel($mots, $texte) {
	foreach($mots as $index => $mot) {
		$pattern = explode(' ', $mot)[0];
		if (preg_match("/$pattern/i", $texte)) {
			return $index;
		}
	}
}
