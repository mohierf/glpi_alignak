<?php

/*
   ------------------------------------------------------------------------
   Plugin Kiosks for GLPI
   Copyright (C) 2011-2015 IPM France SAS

   ------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

Html::header(__('Alignak - mail notifications', 'alignak'), $_SERVER["PHP_SELF"], "config",
             "pluginalignakmenu", "notification");

Session::checkRight("config","r");

Search::show('PluginAlignakMailNotification');

Html::footer();
