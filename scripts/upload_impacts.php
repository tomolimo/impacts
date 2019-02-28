<?php

include "../../../inc/includes.php";

$types = ['C' => ['itemtype' => 'Computer', 'entities_id' => 0],
          'A' => ['itemtype' => 'PluginAppliancesAppliance', 'entities_id' => 0],
          'N' => ['itemtype' => 'NetworkEquipment', 'entities_id' => 0],
          'S' => ['itemtype' => 'Software', 'entities_id' => 0]
         ];


function getOrCreateAssset($itemtype, $item_name, $entities_id) {
   $asset = new $itemtype;
   $dbu = new DbUtils();
   $row = $dbu->getAllDataFromTable($asset->getTable(), "`name` = '$item_name'");
   if (count($row) == 0) {
      // will create the asset
      $asset_id = $asset->add([ 'name' => $item_name,
                  'entities_id' => $entities_id,
                  'is_recursive' => 1,
                  'is_deleted' => 0,
                  'states_id' => 1,
                  'is_helpdesk_visible' => 1,
                  'plugin_appliances_environments_id' => 1
      ]);
      echo "New asset: $itemtype, $item_name ($asset_id)\n";
   } else {
      $asset_id = array_shift($row)['id'];
   }
   return $asset_id;
}

// set new destination for $DB
$DB->dbhost = 'your mysql host name';
$DB->dbuser = 'glpi';
$DB->dbpassword = 'glpi';
$DB->connect();
if ($DB->connected) {
   if (($handle = fopen('impact_list.csv', "r")) !== false) {
      $line = 0;
      while (($data = fgetcsv($handle, 0, ";")) !== false) {
         // $data[0] is the itemtype_1: 'C', 'A', 'N', 'S'
         // $data[1] is the item_1 name
         // $item_1 impacts item_2
         // $data[2] is the itemtype_2: 'C', 'A', 'N', 'S'
         // $data[3] is the item_2 name
         $line++;

         if ($line == 1 || $data[0] == '' || $data[1] == '' || $data[2] == '' || $data[3] == '') {
            continue;
         }

         // get item_1 asset or create it
         $itemtype_1 = $types[$data[0]]['itemtype'];
         $items_id_1 = getOrCreateAssset($itemtype_1, $data[1], $types[$data[0]]['entities_id']);
         // get item_2 asset or create it
         $itemtype_2 = $types[$data[2]]['itemtype'];
         $items_id_2 = getOrCreateAssset($itemtype_2, $data[3], $types[$data[2]]['entities_id']);

         if ($items_id_1 && $items_id_2) {
            $impact = new PluginImpactsImpact;
            // is already existing?
            if (!$impact->getFromDBByQuery("WHERE `itemtype_1` = '$itemtype_1' AND `items_id_1` = $items_id_1 AND `itemtype_2` = '$itemtype_2' AND `items_id_2` = $items_id_2")) {
               $impact->add([
                  'itemtype_1' => $itemtype_1,
                  'items_id_1' => $items_id_1,
                  'itemtype_2' => $itemtype_2,
                  'items_id_2' => $items_id_2
               ]);
            }
         }
      }
   }
} else {
   echo "DB is not connected\n";
}
