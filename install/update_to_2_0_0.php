<?php
/*
 * -------------------------------------------------------------------------
Impacts plugin
Copyright (C) 2021 by Raynet SAS a company of A.Raymond Network.

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

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// ----------------------------------------------------------------------

function update_to_2_0_0() :String {

   global $DB;

   if (!$DB->tableExists('glpi_plugin_impacts_itemtypes')) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_impacts_itemtypes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `url_path_pics` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT COLLATE='utf8_general_ci';";

      $DB->queryOrDie($query, "Error during the database query: create table glpi_plugin_impact_itemtypes");
   }

   if ($DB->tableExists('glpi_plugin_impacts_configs') && $DB->fieldExists('glpi_plugin_impacts_configs', 'assets')) {

      // get data of glpi_configs
      $fieldsConfig = Config::getConfigurationValues('core', [Impact::CONF_ENABLED]);
      $fieldsConfig = importArrayFromDB($fieldsConfig[Impact::CONF_ENABLED]);

      // get data of glpi_plugin_impacts_configs
      $config = PluginImpactsConfig::getInstance();
      $fields = importArrayFromDB($config->fields['assets']);
      $in_array = array_search('PluginAppliancesAppliance', $fields);
      if ($in_array !== false) {
         $fields[$in_array] = 'Appliance';
      }
      $fieldsConfig = array_unique(array_merge($fieldsConfig, $fields));

      // Update data in glpi_configs
      Config::setConfigurationValues('core', [Impact::CONF_ENABLED => exportArrayToDB($fieldsConfig)]);

      $query = "ALTER TABLE `glpi_plugin_impacts_configs`
	            DROP COLUMN `assets`;";
      $DB->queryOrDie($query, "Error during deletion of assets field in glpi_plugin_impacts_configs table!");
   }

   if ($DB->tableExists('glpi_plugin_impacts_impacts')) {

      $used_types = [];
      $dbu = new DbUtils;
      $items = $dbu->getAllDataFromTable('glpi_plugin_impacts_impacts');
      foreach ($items as $item) {
         if ($item['itemtype_1'] == 'PluginAppliancesAppliance') {
            $item['itemtype_1'] = 'Appliance';
         }
         if ($item['itemtype_2'] == 'PluginAppliancesAppliance') {
            $item['itemtype_2'] = 'Appliance';
         }
         $used_types[] = $item['itemtype_1'];
         $used_types[] = $item['itemtype_2'];

         $DB->query("
            REPLACE INTO `glpi_impactrelations`
            SET `itemtype_source`   = '{$item["itemtype_1"]}',
                `items_id_source`   = '{$item["items_id_1"]}',
                `itemtype_impacted` = '{$item["itemtype_2"]}',
                `items_id_impacted` =  '{$item['items_id_2']}';"
            );
      }

      // add in the itemtype the types that were used in the glpi_plugin_impacts_impacts table.
      // so that after migration GLPI users will get the former icons that were used in previous plugin versions
      $used_types = array_unique($used_types);
      foreach ($used_types as $tp) {
         $img = $tp;
         if ($tp == 'Appliance') {
            $img = 'PluginAppliancesAppliance';
         }
         $DB->query("
            REPLACE INTO `glpi_plugin_impacts_itemtypes`
            SET `name`          = '{$tp}',
                `url_path_pics` = 'plugins/impacts/pics/{$img}.png';"
            );

      }

      $query = "RENAME TABLE `glpi_plugin_impacts_impacts` TO `backup_glpi_plugin_impacts_impacts`;";
      $DB->queryOrDie($query, "Error during renaming of glpi_plugin_impacts_impacts to backup_glpi_plugin_impacts_impacts table!");

   }

   return '2.0.0';
}