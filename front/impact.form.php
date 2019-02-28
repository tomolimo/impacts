<?php
/*
 * -------------------------------------------------------------------------
Impacts plugin
Copyright (C) 2018 by Raynet SAS a company of A.Raymond Network.

http://www.araymond.com
-------------------------------------------------------------------------

LICENSE

This file is part of Impacts plugin for GLPI.

This file is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
 */

include ( "../../../inc/includes.php");

Session::checkLoginUser();


if (isset($_POST['add'])
   && isset($_POST['itemtype_1']) && isset($_POST['items_id_1'])
   && isset($_POST['itemtype_2']) && isset($_POST['items_id_2'])) {

   $item = new $_POST['itemtype_1'];

   $item->check(-1, UPDATE, $_POST);

   $item = new PluginImpactsImpact;
   if (isset($_POST['add'])) {

      if ($item->add($_POST)) {
         Event::log($_POST["items_id_1"], $_POST["itemtype_1"], 4, "inventory",
                     //TRANS: %s is the user login
                     sprintf(__('%s adds an impact with %s (%s)'), $_SESSION["glpiname"], $_POST["itemtype_2"]::getTypeName(1), $_POST["items_id_2"]));
      }
      Html::back();
   }

}

Html::displayErrorAndDie("lost");
