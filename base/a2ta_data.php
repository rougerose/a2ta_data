<?php

if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}


function a2ta_data_declarer_champs_extras($champs = array()) {
  $champs['spip_adresses']['departement'] = array(
    'saisie' => 'subdivisions_departement',
    'options' => array(
      'nom' => 'departement',
      'label' => _T('association:label_departement'),
      'sql' => 'smallint(6) NOT NULL DEFAULT 0',
      'defaut' => ''
    )
  );
  $champs['spip_adresses']['region'] = array(
    'saisie' => 'subdivisions_region',
    'options' => array(
      'nom' => 'region',
      'label' => _T('association:label_region'),
      'sql' => 'smallint(6) NOT NULL DEFAULT 0',
      'defaut' => ''
    )
  );
  return $champs;
}


function a2ta_data_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['associations'] = 'associations';
	$interfaces['table_des_tables']['associations_imports'] = 'associations_imports';

	return $interfaces;
}


function a2ta_data_declarer_tables_objets_sql($tables) {

	$tables['spip_associations'] = array(
		'type' => 'association',
		'principale' => 'oui',
		'field'=> array(
			'id_association' => 'bigint(21) NOT NULL',
			'nom'            => 'tinytext NOT NULL DEFAULT ""',
			'membre_fraap'   => 'tinyint(1) NOT NULL DEFAULT 0',
			'url_site'       => 'tinytext NOT NULL DEFAULT ""',
			'url_site_supp'  => 'tinytext NOT NULL DEFAULT ""',
			'date_creation'  => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
			'date'           => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
			'statut'         => 'varchar(20)  DEFAULT "0" NOT NULL',
			'log_import' => 'text NOT NULL DEFAULT ""',
			'maj'            => 'TIMESTAMP'
		),
		'key' => array(
			'PRIMARY KEY'        => 'id_association',
			'KEY statut'         => 'statut',
		),
		'titre' => 'nom AS titre, "" AS lang',
		'date' => 'date',
		'champs_editables'  => array('nom', 'membre_fraap', 'date_creation', 'url_site', 'url_site_supp', 'log_import'),
		'champs_versionnes' => array('nom', 'membre_fraap', 'date_creation', 'url_site', 'url_site_supp', 'log_import'),
		'rechercher_champs' => array('nom' => 5),
		'tables_jointures'  => array(),
		'statut_textes_instituer' => array(
			'prepa'    => 'texte_statut_en_cours_redaction',
			'prop'     => 'texte_statut_propose_evaluation',
			'publie'   => 'texte_statut_publie',
			'refuse'   => 'texte_statut_refuse',
			'poubelle' => 'texte_statut_poubelle',
		),
		'statut'=> array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prop,prepa',
				'post_date' => 'date',
				'exception' => array('statut','tout')
			)
		),
		'texte_changer_statut' => 'association:texte_changer_statut_association',
	);

	$tables['spip_associations_imports'] = array(
		'type' => 'associations_import',
		'principale' => 'oui',
		'table_objet_surnoms' => array('associationsimport'), // table_objet('associations_importlog') => 'associations_import_logs'
		'field'=> array(
			'id_associations_import' => 'bigint(21) NOT NULL',
			'date'               => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
			'associations' => 'text NOT NULL DEFAULT ""',
			'maj'                => 'TIMESTAMP'
		),
		'key' => array(
			'PRIMARY KEY'        => 'id_associations_import',
		),
		'titre' => '"" AS titre, "" AS lang',
		 #'date' => '',
		'champs_editables'  => array(),
		'champs_versionnes' => array(),
		'rechercher_champs' => array(),
		'tables_jointures'  => array(),
	);

	return $tables;
}
