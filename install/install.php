<?php

function impacts_install() {
   global $DB;

   // installation from scratch
   include_once(GLPI_ROOT."/plugins/impacts/setup.php");
   $info = plugin_version_impacts();
   $DB->runFile(GLPI_ROOT . "/plugins/impacts/install/mysql/empty.sql");

}
