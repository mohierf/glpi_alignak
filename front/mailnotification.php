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

// Check for ACLs
if (PluginAlignakMailNotification::canView()) {
   // View is granted: display the list.

   Html::header(__('Alignak - mail notifications', 'alignak'), $_SERVER["PHP_SELF"], "plugins",
      "alignak", "notification");

   Search::show('PluginAlignakMailNotification');

   Html::footer();
} else {
   // View is not granted.
   Html::displayRightError();
}