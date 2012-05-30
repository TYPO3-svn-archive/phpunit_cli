<?php

########################################################################
# Extension Manager/Repository config file for ext "phpunit_cli".
#
# Auto generated 30-05-2012 22:00
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'PHPUnit CLI',
	'description' => 'CLI PHPUnit TYPO3 testing',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.1.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Nikola Stojiljkovic',
	'author_email' => 'nikola.stojiljkovic@essentialdots.com',
	'author_company' => 'Essential Dots',
	'doNotLoadInFE' => 1,
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.3-0.0.0',
			'typo3' => '4.2.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'phpunit' => '',
		),
	),
	'suggests' => array(
		'phpunit' => '',
	),
	'_md5_values_when_last_written' => 'a:4:{s:12:"ext_icon.gif";s:4:"b280";s:17:"ext_localconf.php";s:4:"2105";s:23:"Classes/CLI/PHPUnit.php";s:4:"9ad0";s:26:"Classes/TextUI/Command.php";s:4:"1e45";}',
);

?>