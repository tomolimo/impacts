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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginImpactsConfig extends CommonDBTM {

   static private $_instance = null;
   static $rightname = "config";

   static function canCreate() {
      return self::canUpdate();
   }

   /**
    * Summary of getTypeName
    * @param mixed $nb plural
    * @return mixed
    */
   static function getTypeName($nb = 0) {
      return __("Asset impact setup", "impacts");
   }

   /**
    * Summary of getName
    * @param mixed $with_comment with comment
    * @return mixed
    */
   function getName($with_comment = 0) {
      return self::getTypeName();
   }

   /**
    * Summary of getInstance
     * @return PluginImpactsConfig
    */
   static function getInstance() {
      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
         // convert asset_list into PHP array
         self::$_instance->fields['assets'] = importArrayFromDB(self::$_instance->fields['assets']);
      }
      return self::$_instance;
   }


   /**
    * Summary of showConfigForm
    * @param mixed $item is the config
    * @return boolean
    */
   static function showConfigForm($item) {
      $config = self::getInstance();
      $config->showFormHeader();

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Asset list', 'impacts') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('assets', self::getAssetList(true), [
         'values'   => $config->fields['assets'],
         'width'    => '100%',
         'multiple' => true
      ]);
      echo "</td></tr>\n";

      $config->showFormButtons(['candel'=>false]);

      return false;
   }

      /**
    * Prepare input datas for updating the item
    *
    * @see CommonDBTM::prepareInputForUpdate()
    *
    * @param $input array of datas used to update the item
    *
    * @return the modified $input array
   **/
   function prepareInputForUpdate($input) {
      // asset_list update
      $input['assets'] = exportArrayToDB((isset($input['assets'])
                                             ? $input['assets'] : []));
      return $input;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item::getType() == 'Config') {
         return self::getTypeName();
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item::getType() == 'Config') {
         self::showConfigForm($item);
      }
      return true;
   }


   static function getAssetList($completelist = false) {
      global $CFG_GLPI;

      $regex = '/types$/m';
      $listTemp = [];
      foreach ($CFG_GLPI as $k => $type) {
         if (is_array($type) && preg_match_all($regex, $k, $matches, PREG_SET_ORDER, 0)) {
            foreach ($type as $val) {
               if (strcmp($val, '*')) {
                  array_push($listTemp, $val);
               }
            }
         }
      }
      $list = array_unique($listTemp);

      if (!$completelist) {
         $config = self::getInstance();
         $list = $config->fields['assets'];
      }
      $ret = [];
      foreach ($list as $lo) {
         if (class_exists($lo)) {
            $ret[$lo] = $lo::getTypeName(Session::getPluralNumber())." ($lo)";
         }
      }
      asort($ret, SORT_STRING);
      return $ret;
   }

}
