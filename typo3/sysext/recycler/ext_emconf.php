<?php

########################################################################
# Extension Manager/Repository config file for ext "recycler".
#
# Auto generated 30-11-2009 00:43
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Recycler',
	'description' => 'The recycler offers the possibility to restore deleted records or remove them from the database permanently. These actions can be applied to a single record, multiple records, and recursively to child records (ex. restoring a page can restore all content elements on that page). Filtering by page and by table provides a quick overview of deleted records before taking action on them.',
	'category' => 'module',
	'author' => 'Julian Kleinhans',
	'author_email' => 'typo3@kj187.de',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.1',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:36:{s:9:"ChangeLog";s:4:"7b94";s:12:"ext_icon.gif";s:4:"7dd8";s:17:"ext_localconf.php";s:4:"cee4";s:14:"ext_tables.php";s:4:"1a0d";s:16:"locallang_db.xml";s:4:"a06e";s:56:"classes/controller/class.tx_recycler_controller_ajax.php";s:4:"9159";s:43:"classes/helper/class.tx_recycler_helper.php";s:4:"ab45";s:56:"classes/model/class.tx_recycler_model_deletedRecords.php";s:4:"aa8a";s:48:"classes/model/class.tx_recycler_model_tables.php";s:4:"48a0";s:54:"classes/view/class.tx_recycler_view_deletedRecords.php";s:4:"0685";s:14:"doc/manual.sxw";s:4:"3528";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"e060";s:14:"mod1/index.php";s:4:"6b6a";s:18:"mod1/locallang.xml";s:4:"7f2d";s:22:"mod1/locallang_mod.xml";s:4:"3a26";s:22:"mod1/mod_template.html";s:4:"7c59";s:19:"mod1/moduleicon.gif";s:4:"7dd8";s:23:"res/css/customExtJs.css";s:4:"39ba";s:20:"res/icons/accept.png";s:4:"8bfe";s:24:"res/icons/arrow_redo.png";s:4:"343b";s:40:"res/icons/arrow_rotate_anticlockwise.png";s:4:"a7db";s:17:"res/icons/bin.png";s:4:"728a";s:24:"res/icons/bin_closed.png";s:4:"c5b3";s:23:"res/icons/bin_empty.png";s:4:"2e76";s:27:"res/icons/database_save.png";s:4:"8303";s:20:"res/icons/delete.gif";s:4:"5a2a";s:26:"res/icons/filter_clear.png";s:4:"3862";s:28:"res/icons/filter_refresh.png";s:4:"b051";s:21:"res/icons/loading.gif";s:4:"00ef";s:22:"res/icons/recycler.gif";s:4:"7b41";s:23:"res/icons/recycler2.gif";s:4:"cf3b";s:26:"res/icons/x_toolbar_bg.gif";s:4:"91c4";s:22:"res/js/ext_expander.js";s:4:"bb02";s:22:"res/js/search_field.js";s:4:"efae";s:21:"res/js/t3_recycler.js";s:4:"309d";}',
	'suggests' => array(
	),
);

?>