<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Setting up script that can be run through cli_dispatch.phpsh
if (TYPO3_MODE == 'BE') {
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array(
		'EXT:' . $_EXTKEY . '/Classes/CLI/PHPUnit.php',
		'_CLI_phpunit'
	);
}
?>