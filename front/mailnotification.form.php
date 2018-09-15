<?php

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ("../../../inc/includes.php");

// Check if current user have the appropriate right
//Session::checkRight("plugin_alignak_mailnotification", READ);

Html::header(
   __('Alignak - mail notifications', 'alignak'),
   $_SERVER['PHP_SELF'],
   'admin',
   'pluginalignakmenu', 'mail_notification');

$paMailNotification = new PluginAlignakMailNotification();
if (isset($_POST["copy"])) {
   $paMailNotification->showForm(-1, -1, [ 'canedit'=>PluginAlignakMailNotification::canUpdate(), 'colspan'=>4 ], $_POST);
   Html::footer();
   exit;
} else if (isset ($_POST["add"])) {
   $paMailNotification->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $paMailNotification->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $paMailNotification->delete($_POST);
   $paMailNotification->redirectToList();
} else if (isset ($_POST["send"])) {
   Session::crongetCSVDailyCounters($_POST['id']);
   Html::back();
}

//Session::checkRight("config","w");
if (isset($_GET["id"])) {
   $paMailNotification->showForm($_GET['id'], -1, [ 'canedit'=>PluginAlignakMailNotification::canUpdate(), 'colspan'=>4 ]);
} else {
   $paMailNotification->showForm();
}

Html::footer();
