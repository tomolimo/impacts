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

/**
 * Summary of PluginImpactsImpact
 * Manage impactship between assets
 */
class PluginImpactsImpact extends CommonDBRelation {
   const DIRECTION_FORWARD = 1;
   const DIRECTION_BACKWARD = 2;
   const DIRECTION_BOTH = 3;

   static public $itemtype_1 = 'itemtype_1'; // Type ref or field name (must start with itemtype)
   static public $items_id_1 = 'items_id_1'; // Field name
   static public $itemtype_2 = 'itemtype_2'; // Type ref or field name (must start with itemtype)
   static public $items_id_2 = 'items_id_2'; // Field name

   static function getTypeName($nb = 0) {
      return _n('Asset impact', 'Asset impacts', $nb);
   }

   /**
    * Summary of getTabNameForItem
    * @param CommonGLPI $item
    * @param mixed $withtemplate
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nbimpacts = 0;
      if ($_SESSION['glpishow_count_on_tabs']) {
         //$nbimpacts  = count(self::getImpactedByItem($item));
         self::getOppositeItems($item, $opposite);
         $nbimpacts = count($opposite);
      }

      return self::createTabEntry(_n('Impact', 'Impacts', Session::getPluralNumber(), 'impacts'), $nbimpacts);
   }

   ///**
   // * Summary of getSonsOf
   // * @param mixed $itemtype
   // * @param mixed $items_id
   // * @param mixed $addme
   // * @param mixed $complete
   // * @return array
   // */
   //static function getSonsOf($itemtype, $items_id, $addme=true, $complete=true) {
   //   global $DB;

   //   $table = self::getTable();
   //   $parentIDfield = getForeignKeyFieldForTable($table);

   //   $item = new PluginImpactsItem;
   //   $item->getFromDBByQuery("WHERE itemtype='$itemtype' AND items_id=$items_id");
   //   $IDf = $item->getID();

   //   $id_found = [];
   //   if ($addme) {
   //      // IDs to be present in the final array
   //      $id_found[$IDf] = $IDf;
   //   }

   //   // current ID found to be added
   //   $found = array();
   //   // First request init the  variables
   //   $query = "SELECT `id`
   //             FROM `$table`
   //             WHERE `$parentIDfield` = '$IDf'
   //             ";

   //   if (($result = $DB->query($query))
   //       && ($DB->numrows($result) > 0)) {
   //      while ($row = $DB->fetch_assoc($result)) {
   //         $id_found[$row['id']] = $row['id'];
   //         $found[$row['id']]    = $row['id'];
   //      }
   //   }

   //   // Get the leafs of previous founded item
   //   while ($complete && count($found) > 0) {
   //      $first = true;
   //      // Get next elements
   //      $query = "SELECT `id`
   //                FROM `$table`
   //                WHERE `$parentIDfield` IN ('" . implode("','",$found) . "')";

   //      // CLear the found array
   //      unset($found);
   //      $found = array();

   //      $result = $DB->query($query);
   //      if ($DB->numrows($result) > 0) {
   //         while ($row = $DB->fetch_assoc($result)) {
   //            if (!isset($id_found[$row['id']])) {
   //               $id_found[$row['id']] = $row['id'];
   //               $found[$row['id']]    = $row['id'];
   //            }
   //         }
   //      }
   //   }
   //   return $id_found;
   //}

   /**
    * Summary of getOppositeItems
    * @param CommonGLPI $item
    * @param mixed $opitems
    * @param mixed $direction
    * @param mixed $level
    */
   static function getOppositeItems(CommonGLPI $item, &$opitems, $direction = self::DIRECTION_BOTH, $level = 1) {

      $dbu = new DbUtils;

      if (!isset($opitems)) {
         $opitems = [];
      }
      if ($level > 0) {
         if ($direction & self::DIRECTION_FORWARD) {
            $retchild = $dbu->getAllDataFromTable(self::getTable(), "itemtype_1 = '".$item->getType()."' AND items_id_1=".$item->getID());
            $opitems += $retchild;
            if ($level > 1) {
               foreach ($retchild as $child) {
                  // if child is already as parent in my list do not browse children for it otherwise we are going to loop
                  $found = false;
                  foreach ($opitems as $elts) {
                     if ($child['itemtype_2'] == $elts['itemtype_1'] && $child['items_id_2'] == $elts['items_id_1']) {
                        $found = true;
                        break;
                     }
                  }
                  if (!$found) {
                     $tmp = new $child['itemtype_2'];
                     $tmp->fields['id'] = $child['items_id_2'];
                     self::getOppositeItems($tmp, $opitems, self::DIRECTION_FORWARD, $level - 1);
                  }
               }
            }
         }
         if ($direction & self::DIRECTION_BACKWARD) {
            $retparent = $dbu->getAllDataFromTable(self::getTable(), "itemtype_2 = '".$item->getType()."' AND items_id_2=".$item->getID());
            $opitems += $retparent;
            if ($level > 1) {
               foreach ($retparent as $parent) {
                  // if parent is already as child in my list do not browse parents for it otherwise we are going to loop
                  $found = false;
                  foreach ($opitems as $elts) {
                     if ($parent['itemtype_1'] == $elts['itemtype_2'] && $parent['items_id_1'] == $elts['items_id_2']) {
                        $found = true;
                        break;
                     }
                  }
                  if (!$found) {
                     $tmp = new $parent['itemtype_1'];
                     $tmp->fields['id'] = $parent['items_id_1'];
                     self::getOppositeItems($tmp, $opitems, self::DIRECTION_BACKWARD, $level - 1);
                  }
               }
            }
         }
      }
   }


   /**
    * Summary of displayTabContentForItem
    * @param CommonGLPI $item         is the item
    * @param mixed      $tabnum       is the tab num
    * @param mixed      $withtemplate has template
    * @return mixed
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      self::showForItem($item);
      return true;
   }


   /**
    * Summary of showForItem
    * @param CommonGLPI $item
    * @return boolean
    */
   static function showForItem(CommonGLPI $item) {
      $ID = $item->getID();

      if ($item->isNewID($ID)) {
         return false;
      }

      $itemtype = $item->getType();
      if (!$itemtype::canView()) {
         return false;
      }

      $params         = [];
      $params['rand'] = mt_rand();

      self::showOppositeListForItem($item, self::DIRECTION_BACKWARD, __('List of assets impacting %s', 'impacts'), $params);

      $params['rand'] = mt_rand();
      self::showOppositeListForItem($item, self::DIRECTION_FORWARD, __('List of assets impacted by %s', 'impacts'), $params);
      self::showImpactNetwork($item, PHP_INT_MAX);
      return true;
   }


   static function showImpactNetwork(CommonGLPI $item, $level = 1) {
      global $CFG_GLPI;

      echo '<script type="text/javascript" src="'.$CFG_GLPI['root_doc'].'/plugins/impacts/lib/vis-4.21.0/dist/vis.js"></script>
            <link href="'.$CFG_GLPI['root_doc'].'/plugins/impacts/lib/vis-4.21.0/dist/vis-network.min.css" rel="stylesheet" type="text/css" />

            <style type="text/css">
               #mynetwork {
                  width: 100%;
                  height: 800px;
                  border: 1px solid lightgray;
               }
            </style>';

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th>".__('Impact graph', 'impacts')."</th></tr>";
      echo "<tr><td>";
      echo '<div id="mynetwork"></div>';
      echo "</td></tr>";
      echo "</table>";

      self::getOppositeItems($item, $oppositeItems, self::DIRECTION_BOTH, $level);
      if (count($oppositeItems) == 0) {
         // there is no impacts
         // must add current item
         $oppositeItems[] = ['itemtype_1' => $item->getType(), 'items_id_1' => $item->getID(), 'itemtype_2' => $item->getType(), 'items_id_2' => $item->getID()];
      }

      $nodes = [];
      $edgestring = "";
      foreach ($oppositeItems as $data) {
         $idfrom = $data['itemtype_1']."[".$data['items_id_1']."]";
         $idto = $data['itemtype_2']."[".$data['items_id_2']."]";
         $nodes[$idfrom] = ['itemtype' => $data['itemtype_1'], 'items_id' => $data['items_id_1']];
         $nodes[$idto] = ['itemtype' => $data['itemtype_2'], 'items_id' => $data['items_id_2']];
         $edgestring .= "{ from: '".$idfrom."', to: '".$idto."', arrows: 'to' },";
      }

      if (count($nodes) == 1) {
         $edgestring = "";
      }

      $nodestring = "";
      $imgpath = $CFG_GLPI['root_doc']."/plugins/impacts/pics/";
      foreach ($nodes as $id => $data) {
         $temp = new $data['itemtype'];
         $temp->getFromDB($data['items_id']);
         $link = $temp->getLinkURL();
         $addOptions = "";
         if ($data['itemtype'] == $item->getType() && $data['items_id'] == $item->getID()) {
            // will fixes this node
            $addOptions = ", shapeProperties: { useBorderWithImage: true } "; // x:0, y: 0, fixed: {x: true, y:true},
         }
         $nodestring .= "{ id: '$id', label: '"./*$data['itemtype']::getTypeName(1).'\n'.*/$temp->fields['name']."', image: '$imgpath/".$data['itemtype'].".png', shape: 'image', urllink: '$link' $addOptions},";
      }

      echo '<script type="text/javascript">
         // create an array with nodes
         var nodes = new vis.DataSet(['.$nodestring.']);

         // create an array with edges
         var edges = new vis.DataSet(['.$edgestring.']);

         // create a network
         var container = document.getElementById("mynetwork");
         var data = {
            nodes: nodes,
            edges: edges
         };
         var options = {
            //layout: {
            //   randomSeed: 501383,

            //   //hierarchical: {
            //   //   direction: "UD"
            //   //}
            //},
            //nodes: {
            //   borderWidth:0,
            //   //size:30,
            //   //color: {
            //   //   border: "#406897",
            //   //   //background: "#6AAFFF"
            //   //},
            //   shapeProperties: {
            //      useBorderWithImage:false
            //   }
            //}
         };
         var network = new vis.Network(container, data, options);
         network.on("doubleClick", function (properties) {
               if (properties.nodes.length > 0) {
                  var currentID = "'.$item->getType().'['.$item->getID().']";
                  if (currentID != properties.nodes[0]) {
                     //debugger;
                     document.location.href = nodes.get(properties.nodes[0]).urllink ;
                  }
              }
            });
         //network.on("stabilized", function(event) {
         //      //debugger;
         //      var locSeed = this.getSeed();
         //      //this.focus("'.$item->getType().'['.$item->getID().']'.'");
         //   });
         network.selectNodes(["'.$item->getType().'['.$item->getID().']'.'"], true);
      </script>';

   }

   /**
    * Summary of showOppositeListForItem
    * @param CommonGLPI $item
    * @param mixed $direction
    * @param mixed $title
    * @param mixed $options
    */
   static function showOppositeListForItem(CommonGLPI $item, $direction, $title, $options = []) {
      global $DB, $CFG_GLPI;

      $dbu = new DbUtils;

      // by default for self::DIRECTION_FORWARD
      $itemtype_active = 'itemtype_2';
      $items_id_active = 'items_id_2';
      $itemtype_passive = 'itemtype_1';
      $items_id_passive = 'items_id_1';

      if ($direction == self::DIRECTION_BACKWARD) {
         $itemtype_active = 'itemtype_1';
         $items_id_active = 'items_id_1';
         $itemtype_passive = 'itemtype_2';
         $items_id_passive = 'items_id_2';
      }
      //default options
      $params['rand'] = mt_rand();
      $rand = $params['rand'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $itemtype = $item->getType();
      $canupdate = $item->canUpdateItem() && $itemtype::canView();

      $columns = ['itemtype'   => __('Item type'),
                       'name'  => __('Name'),
                       'date_creation'   => __('Date')
                       ];

      if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      if ((isset($_GET["sort"]) && !empty($_GET["sort"]))
         && isset($columns[$_GET["sort"]])) {
         $sort = "`".$_GET["sort"]."`";
      } else {
         $sort = "`itemtype`";
      }

      self::getOppositeItems($item, $oppositeItems, $direction);

      $itemtypes = [];
      foreach ($oppositeItems  as $rec) {
         $itemtypes[$rec[$itemtype_active]] = $rec[$itemtype_active];
      }

      $query = "";

      foreach ($itemtypes as $itemtype) {
         if ($query != '') {
            $query .= "\nUNION\n";
         }
         $query .= "SELECT rel.id as assocID, rel.date_creation, rel.$itemtype_active as itemtype, rel.$items_id_active as items_id, it.`name`
               FROM ".self::getTable()." AS rel
               JOIN `".$dbu->getTableForItemType($itemtype)."` AS it ON rel.`$itemtype_active`='$itemtype' AND rel.`$items_id_active`=it.`id`
               WHERE rel.$itemtype_passive = '". $item->getType()."' AND rel.$items_id_passive = ".$item->getID();
      }

      if ($query != '') {
         $query = "SELECT * FROM (\n$query\n) AS elts\nORDER BY $sort $order";
      }

      $number = 0; // by default
      if ($query != '') {         
         $result = $DB->request($query);
         $number = count($result);
      }

      $impacts = [];
      if ($number) {
         foreach ($result as $id => $row) {
            $impacts[$row['assocID']] = $row;
         }
         //while ($data = $DB->fetch_assoc($result)) {
         //   $impacts[$data['assocID']] = $data;
         //}
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='2'>".sprintf($title, $item->fields['name'])."</th></tr>";
      echo "</table>";

      if ($canupdate) {
         echo "<div class='firstbloc'>";
         echo "<form name='relation_form$rand' id='relation_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         //         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a child')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>".sprintf(__('Add a new asset', 'impacts'), $item->fields['name'])."</td><td>";

         $used = [];
         $used[$item->getType()][] = $item->getID(); // to prevent re-use of current item on itself
         foreach ($oppositeItems  as $val) {
            $used[$val[$itemtype_active]][] = $val[$items_id_active];
         }

         self::dropdownAllDevices($itemtype_active, null, 0, 1, 0, -1, ['used' => $used, 'myname' => $items_id_active]);
         echo "<span id='item_ticket_selection_information'></span>";
         echo "</td><td class='center' width='30%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='$itemtype_passive' value='".$item->getType()."'>";
         echo "<input type='hidden' name='$items_id_passive' value='".$item->getID()."'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";

      if ($canupdate
          && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$params['rand']);
         $massiveactionparams = ['num_displayed'  => $number,
                                      'container'      => 'mass'.__CLASS__.$params['rand']];
         Html::showMassiveActions($massiveactionparams);
      }

      $sortClass = ($order == "DESC") ? "order_DESC" : "order_ASC";
      echo "<table class='tab_cadre_fixehov'>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canupdate
          && $number) {
         $header_top    .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
         $header_bottom .= "</th>";
      }

      foreach ($columns as $key => $val) {
         $header_end .= "<th class=\"".(($sort == "`$key`") ?$sortClass:"")."\">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                          (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
      }

      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      if ($number) {
         foreach ($impacts as $data) {
            $items_id = $data["items_id"];
            $itemtype = $data['itemtype'];
            $link         = NOT_AVAILABLE;
            $subitem = new $itemtype;

            if ($subitem->getFromDB($items_id)) {
               $link         = $subitem->getLink();
            }

            echo "<tr class='tab_bg_1".($subitem->fields["is_deleted"]?"_2":"")."'>";
            if ($canupdate) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
               echo "</td>";
            }
            echo "<td class='center'>".$data['itemtype']::getTypeName(1)."</td>";
            echo "<td class='center'>".$link."</td>";
            echo "<td class='center'>".Html::convDateTime($data["date_creation"])."</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if (!$number) {
         echo __('Empty list', 'impacts');
      }

      if ($canupdate && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }



   ///**
   // * Summary of getAllItemtypes
   // * return an array of itemtypes for
   // * 1st level children of the given item
   // * @param mixed $relItem a PluginImpactsItem object
   // * @return array
   // */
   //static function getAllParentItemtypes(PluginImpactsItem $relItem) {
   //   $data = self::getAllParentsForItem($relItem);
   //   $ret = [];
   //   foreach($data as $rec) {
   //      $ret[$rec['itemtype']] = $rec['itemtype'];
   //   }
   //   return $ret;
   //}


   //static function showParentListForItem(CommonDBTM $item, $options=array()) {
   //   global $DB, $CFG_GLPI;
   //   $dbu = new DbUtils;
   //   //default options
   //   $params['rand'] = mt_rand();

   //   if (is_array($options) && count($options)) {
   //      foreach ($options as $key => $val) {
   //         $params[$key] = $val;
   //      }
   //   }

   //   $canupdate = $item->canUpdateItem() && $item->getType()::canView();

   //   $columns = array('itemtype'   => __('Item type'),
   //                    'name'  => __('Name'),
   //                    'date_creation'   => __('Date')
   //                    );

   //   if (isset($_GET["order"]) && ($_GET["order"] == "ASC")) {
   //      $order = "ASC";
   //   } else {
   //      $order = "DESC";
   //   }

   //   if ((isset($_GET["sort"]) && !empty($_GET["sort"]))
   //      && isset($columns[$_GET["sort"]])) {
   //      $sort = "`".$_GET["sort"]."`";
   //   } else {
   //      $sort = "`name`";
   //   }

   //   $relID = -1;
   //   if ($relitem = PluginImpactsItem::getItemFromDB($item->getType(), $item->getID())) {
   //      $relID = $relitem->getID();
   //   }

   //   $itemtypes = self::getAllParentItemtypes($relitem);
   //   $query = "";

   //   foreach($itemtypes as $itemtype){
   //      if ($query != '') {
   //         $query .= "\nUNION\n";
   //      }
   //      $query .= "SELECT rel.id as assocID, rel.date_creation, relit.itemtype, relit.items_id, it.`name`
   //            FROM glpi_plugin_impacts_impacts AS rel
   //            JOIN glpi_plugin_impacts_items AS relit ON relit.id=rel.plugin_impacts_items_id
   //            JOIN `".$dbu->getTableForItemType($itemtype)."` AS it ON relit.`itemtype`='$itemtype' AND it.`id`=relit.`items_id`
   //            WHERE rel.id IN (
   //                  SELECT rel.plugin_impacts_impacts_id
   //                  FROM glpi_plugin_impacts_impacts AS rel
   //                  WHERE rel.id=$relID
   //               )";
   //   }

   //   if ($query != '') {
   //      $query = "SELECT * FROM (\n$query\n) AS elts\nORDER BY $sort $order";
   //   }

   //   $result = $DB->query($query);
   //   $number = $DB->numrows($result);

   //   $impacts = array();
   //   if ($number) {
   //      while ($data = $DB->fetch_assoc($result)) {
   //         $impacts[$data['assocID']] = $data;
   //      }
   //   }
   //   echo "<table class='tab_cadre_fixe'>";
   //   echo "<tr class='tab_bg_2'><th colspan='2'>".__('Parent list')."</th></tr>";
   //   echo "</table>";

   //   if ($canupdate) {
   //      echo "<div class='firstbloc'>";
   //      echo "<form name='relation_form$rand' id='relation_form$rand' method='post'
   //             action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

   //      echo "<table class='tab_cadre_fixe'>";
   //      //         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a Parent')."</th></tr>";

   //      echo "<tr class='tab_bg_1'><td>";

   //      $allItems = self::getAllParentsForItem($relitem);
   //      $used = array();
   //      if (!empty($allItems)) {
   //         foreach ($allItems as $val) {
   //            $used[$val['itemtype']][] = $val['items_id'];
   //         }
   //      }

   //      self::dropdownAllDevices("add_itemtype", null, 0, 1, 0, -1, ['used' => $used, 'myname' => 'add_items_id']);
   //      echo "<span id='item_ticket_selection_information'></span>";
   //      echo "</td><td class='center' width='30%'>";
   //      echo "<input type='submit' name='add_parent' value=\""._sx('button', 'Add')."\" class='submit'>";
   //      echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
   //      echo "<input type='hidden' name='items_id' value='".$item->getID()."'>";
   //      echo "</td></tr>";
   //      echo "</table>";
   //      Html::closeForm();
   //      echo "</div>";
   //   }

   //   echo "<div class='spaced'>";

   //   if ($canupdate
   //       && $number) {
   //      Html::openMassiveActionsForm('mass'.__CLASS__.$params['rand']);
   //      $massiveactionparams = array('num_displayed'  => $number,
   //                                   'container'      => 'mass'.__CLASS__.$params['rand']);
   //      Html::showMassiveActions($massiveactionparams);
   //   }

   //   $sort_img = "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
   //                 (($order == "DESC") ? "puce-down.png" : "puce-up.png") ."\" alt='' title=''>";

   //   echo "<table class='tab_cadre_fixehov'>";

   //   $header_begin  = "<tr>";
   //   $header_top    = '';
   //   $header_bottom = '';
   //   $header_end    = '';
   //   if ($canupdate
   //       && $number) {
   //      $header_top    .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
   //      $header_top    .= "</th>";
   //      $header_bottom .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
   //      $header_bottom .= "</th>";
   //   }

   //   foreach ($columns as $key => $val) {
   //      $header_end .= "<th>".(($sort == "`$key`") ?$sort_img:"").
   //                     "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
   //                       (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
   //   }

   //   $header_end .= "</tr>";
   //   echo $header_begin.$header_top.$header_end;

   //   if ($number) {
   //      foreach  ($impacts as $data) {
   //         $items_id = $data["items_id"];
   //         $itemtype = $data['itemtype'];
   //         $link         = NOT_AVAILABLE;
   //         $subitem = new $itemtype;

   //         if ($subitem->getFromDB($items_id)) {
   //            $link         = $subitem->getLink();
   //         }

   //         echo "<tr class='tab_bg_1".($subitem->fields["is_deleted"]?"_2":"")."'>";
   //         if ($canupdate) {
   //            echo "<td width='10'>";
   //            Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
   //            echo "</td>";
   //         }
   //         echo "<td class='center'>".$data['itemtype']::getTypeName(1)."</td>";
   //         echo "<td class='center'>".$link."</td>";
   //         echo "<td class='center'>".Html::convDateTime($data["date_creation"])."</td>";
   //         echo "</tr>";
   //      }
   //      echo $header_begin.$header_bottom.$header_end;
   //   }

   //   echo "</table>";
   //   if ($canupdate && $number) {
   //      $massiveactionparams['ontop'] = false;
   //      Html::showMassiveActions($massiveactionparams);
   //      Html::closeForm();
   //   }
   //   echo "</div>";
   //}


   /**
    * Show a select box for adding All Devices
    *
    * @param $myname             select name
    * @param $itemtype           preselected value.for item type
    * @param $items_id           preselected value for item ID (default 0)
    * @param $admin              is an admin access ? (default 0)
    * @param $users_id           user ID used to display my devices (default 0
    * @param $entity_restrict    Restrict to a defined entity (default -1)
    * @param $options   array of possible options:
    *    - tickets_id : ID of the ticket
    *    - used       : ID of the requester user
    *    - multiple   : allow multiple choice
    *    - rand       : random number
    *
    * @return nothing (print out an HTML select box)
    **/
   static function dropdownAllDevices($myname, $itemtype, $items_id = 0, $admin = 0, $users_id = 0,
                                      $entity_restrict = -1, $options = []) {
      global $CFG_GLPI, $DB;

      $params = ['used'       => [],
                      'multiple'   => 0,
                      'rand'       => mt_rand(),
                      'myname'     => 'items_id'];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $rand = $params['rand'];

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] == 0) {
         echo "<input type='hidden' name='$myname' value=''>";
         echo "<input type='hidden' name='{$params['myname']}' value='0'>";

      } else {
         echo "<div id='relation_all_devices$rand'>";
         if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,
                                                                     Ticket::HELPDESK_ALL_HARDWARE)) {
            $types = PluginImpactsConfig::getAssetList(); // Ticket::getAllTypesForHelpdesk();
            $emptylabel = Dropdown::EMPTY_VALUE;
            //if ($params['tickets_id'] > 0) {
            //   $emptylabel = Dropdown::EMPTY_VALUE;
            //}
            Dropdown::showItemTypes($myname, array_keys($types),
                                    ['emptylabel' => $emptylabel,
                                          'value'      => $itemtype,
                                          'rand'       => $rand, 'display_emptychoice' => true]);
            //$found_type = isset($types[$itemtype]);

            $p = ['itemtype'        => '__VALUE__',
                       'entity_restrict' => $entity_restrict,
                       'admin'           => $admin,
                       'used'            => $params['used'],
                       'multiple'        => $params['multiple'],
                       'rand'            => $rand,
                       'myname'          => $params['myname']];

            Ajax::updateItemOnSelectEvent("dropdown_$myname$rand", "results_$myname$rand",
                                          $CFG_GLPI["root_doc"].
                                             "/ajax/dropdownTrackingDeviceType.php",
                                          $p);
            echo "<span id='results_$myname$rand'>\n";

            echo "</span>\n";
         }
         echo "</div>";
      }
      return $rand;
   }

}
