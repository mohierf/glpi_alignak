<?php

/*
   ------------------------------------------------------------------------
   Plugin Kiosks for GLPI
   Copyright (C) 2011-2015 IPM France SAS

   ------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('alignak') || !$plugin->isActivated('alignak')) {
   Html::displayNotFoundError();
}

// Check if current user have config right
Session::checkRight("plugin_alignak_menu", READ);

// View is granted: display the list.
Html::header(__('Alignak plugin - main menu', 'alignak'), $_SERVER['PHP_SELF']);

PluginAlignakMenu::displayMenu();

Html::footer();
