<?php
/**
 * Gestion du formulaire de d'édition de association
 *
 * @plugin     A2TA Associations
 * @copyright  2020
 * @author     christophe le drean
 * @licence    GNU/GPL v3
 * @package    SPIP\A2ta_associations\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');


function formulaires_editer_association_saisies_dist($id_association='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden='') {
	$saisies = array(
		array(
			'saisie' => 'hidden',
			'options' => array(
				'nom' => 'id_association',
				'defaut' => $id_association
			)
		),
		array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'nom',
				'label' => _T('association:champ_nom_label'),
				'obligatoire' => 'oui'
			)
		),
		array(
			'saisie' => 'radio',
			'options' => array(
				'nom' => 'membre_fraap',
				'label' => _T('association:champ_membre_fraap_label'),
				'defaut' => '0',
				'data' => array('1' => 'Oui', '0' => 'Non')
			)
		),
		array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'url_site',
				'label' => _T('association:champ_url_site_label'),

			),
			'verifier' => array(
				'type' => 'url',
				'options' => array(
					'mode' => 'protocole_seul'
				)
			)
		),
		array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'url_site_supp',
				'label' => _T('association:champ_url_site_supp_label'),

			),
			'verifier' => array(
				'type' => 'url',
				'options' => array(
					'mode' => 'protocole_seul'
				)
			)
		),
		array(
			'saisie' => 'date',
			'options' => array(
				'nom' => 'date_creation',
				'label' => _T('association:champ_date_creation_label'),

			)
		),
	);
	return $saisies;
}


/**
 * Identifier le formulaire en faisant abstraction des paramètres qui ne représentent pas l'objet edité
 *
 * @param int|string $id_association
 *     Identifiant du association. 'new' pour un nouveau association.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un association source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du association, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return string
 *     Hash du formulaire
 */
function formulaires_editer_association_identifier_dist($id_association = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = array(), $hidden = '') {
	return serialize(array(intval($id_association)));
}

/**
 * Chargement du formulaire d'édition de association
 *
 * Déclarer les champs postés et y intégrer les valeurs par défaut
 *
 * @uses formulaires_editer_objet_charger()
 *
 * @param int|string $id_association
 *     Identifiant du association. 'new' pour un nouveau association.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un association source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du association, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Environnement du formulaire
 */
function formulaires_editer_association_charger_dist($id_association = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = array(), $hidden = '') {
	$valeurs = formulaires_editer_objet_charger('association', $id_association, '', $lier_trad, $retour, $config_fonc, $row, $hidden);
	return $valeurs;
}

/**
 * Vérifications du formulaire d'édition de association
 *
 * Vérifier les champs postés et signaler d'éventuelles erreurs
 *
 * @uses formulaires_editer_objet_verifier()
 *
 * @param int|string $id_association
 *     Identifiant du association. 'new' pour un nouveau association.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un association source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du association, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Tableau des erreurs
 */
function formulaires_editer_association_verifier_dist($id_association = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = array(), $hidden = '') {
	$erreurs = array();

	$verifier = charger_fonction('verifier', 'inc');

	foreach (array('date_creation') AS $champ) {
		$normaliser = null;
		if ($erreur = $verifier(_request($champ), 'date', array('normaliser'=>'datetime'), $normaliser)) {
			$erreurs[$champ] = $erreur;
		// si une valeur de normalisation a ete transmis, la prendre.
		} elseif (!is_null($normaliser)) {
			set_request($champ, $normaliser);
		// si pas de normalisation ET pas de date soumise, il ne faut pas tenter d'enregistrer ''
		} else {
			set_request($champ, null);
		}
	}

	$erreurs += formulaires_editer_objet_verifier('association', $id_association, array('nom', 'membre_fraap'));

	return $erreurs;
}

/**
 * Traitement du formulaire d'édition de association
 *
 * Traiter les champs postés
 *
 * @uses formulaires_editer_objet_traiter()
 *
 * @param int|string $id_association
 *     Identifiant du association. 'new' pour un nouveau association.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un association source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du association, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Retours des traitements
 */
function formulaires_editer_association_traiter_dist($id_association = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = array(), $hidden = '') {
	$retours = formulaires_editer_objet_traiter('association', $id_association, '', $lier_trad, $retour, $config_fonc, $row, $hidden);
	return $retours;
}
