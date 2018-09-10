<?php

// ----------------------------------------------------------------------
// Original Author of file: Frederic Mohier
// Purpose of file:
// ----------------------------------------------------------------------

include ("../../../inc/includes.php");

//Session::checkRight("config","r");

Html::header(__('Alignak - mail notification', 'alignak'),$_SERVER["PHP_SELF"], "plugins",
             "alignak", "mailnotification");

$pkMailNotification = new PluginAlignakMailNotification();
if (isset($_POST["copy"])) {
   $pkMailNotification->showForm(-1, -1, array( 'canedit'=>PluginAlignakMailNotification::canUpdate(), 'colspan'=>4 ), $_POST);
   Html::footer();
   exit;
} else if (isset ($_POST["add"])) {
   $pkMailNotification->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $pkMailNotification->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $pkMailNotification->delete($_POST);
   $pkMailNotification->redirectToList();
} else if (isset ($_POST["send"])) {
   Session::crongetCSVDailyCounters($_POST['id']);
   Html::back();
}

//Session::checkRight("config","w");
if (isset($_GET["id"])) {
  $pkMailNotification->showForm($_GET['id'], -1, array( 'canedit'=>PluginAlignakMailNotification::canUpdate(), 'colspan'=>4 ));
} else {
  $pkMailNotification->showForm();
}

Html::footer();
