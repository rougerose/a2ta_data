<?php

if (!defined("_ECRIRE_INC_VERSION")) {
  return;
}

// Répertoire des fichiers importés
if (!defined('_DIR_IMPORTS_ASSOCIATIONS')) {
	define('_DIR_IMPORTS_ASSOCIATIONS', _DIR_VAR);
}

// Type de coordonnées par défaut
if (!defined('_COORDONNEES_TYPE_DEFAUT')) {
	define('_COORDONNEES_TYPE_DEFAUT', 'work');
}


// debug
// error_reporting(E_ALL^E_NOTICE);
// ini_set ("display_errors", "On");
// define('SPIP_ERREUR_REPORT',E_ALL);
// define('_LOG_FILTRE_GRAVITE',8);
