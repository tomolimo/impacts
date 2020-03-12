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
   const DIRECTION_FORWARD  = 1;
   const DIRECTION_BACKWARD = 2;
   const DIRECTION_BOTH     = 3;

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
         self::getOppositeItems($item, $opposite);
         $nbimpacts = count($opposite);
      }

      return self::createTabEntry(_n('Impact', 'Impacts', Session::getPluralNumber(), 'impacts'), $nbimpacts);
   }

   /**
    * Summary of getOppositeItems
    * @param CommonGLPI $item
    * @param mixed $opitems
    * @param mixed $direction
    * @param mixed $level
    */
   static function getOppositeItems(CommonGLPI $item, &$opitems, $direction = self::DIRECTION_BOTH, $level = 1) {
      if (!isset($opitems)) {
         $opitems = [];
      }
      if ($level > 0) {
         if ($direction & self::DIRECTION_FORWARD) {
            $retchild = getAllDatasFromTable(self::getTable(), [
               "itemtype_1" => $item::getType(),
               'items_id_1' => $item->fields['id']
            ]);
            $opitems += $retchild;
            if ($level > 1) {
               foreach ($retchild as $child) {
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
            $retparent = getAllDatasFromTable(self::getTable(), [
               "itemtype_2" => $item::getType(),
               'items_id_2' => $item->fields['id']
            ]);
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
      return self::showForItem($item);
   }


   /**
    * Summary of showForItem
    * @param CommonGLPI $item
    * @return boolean
    */
   static function showForItem(CommonGLPI $item) {
      if ($item->isNewItem()) {
         return false;
      }

      if (!$item::canView()) {
         return false;
      }

      self::showOppositeListForItem($item, self::DIRECTION_BACKWARD, __('List of assets impacting %s', 'impacts'), [
         'rand' => mt_rand()
      ]);

      self::showOppositeListForItem($item, self::DIRECTION_FORWARD, __('List of assets impacted by %s', 'impacts'), [
         'rand' => mt_rand()
      ]);
      self::showImpactNetwork($item, PHP_INT_MAX);
      return true;
   }


   static function showImpactNetwork(CommonGLPI $item, $level = 1) {
      global $CFG_GLPI, $GLPI_CACHE;

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
         $oppositeItems[] = [
            'itemtype_1' => $item::getType(),
            'items_id_1' => $item->fields['id'],
            'itemtype_2' => $item::getType(),
            'items_id_2' => $item->fields['id']
         ];
      }

      $nodes = [];
      $edgestring = "";
      foreach ($oppositeItems as $data) {
         $idfrom = $data['itemtype_1']."[".$data['items_id_1']."]";
         $idto = $data['itemtype_2']."[".$data['items_id_2']."]";
         $nodes[$idfrom] = [
            'itemtype' => $data['itemtype_1'],
            'items_id' => $data['items_id_1']
         ];
         $nodes[$idto] = [
            'itemtype' => $data['itemtype_2'],
            'items_id' => $data['items_id_2']
         ];
         $edgestring .= "{ from: '".$idfrom."', to: '".$idto."', arrows: 'to'},";
      }

      if (count($nodes) == 1) {
         $edgestring = "";
      }

      $nodestring = "";
      $imgpath = $CFG_GLPI['root_doc']."/plugins/impacts/pics";
      foreach ($nodes as $id => $data) {
         $temp = new $data['itemtype'];
         $temp->getFromDB($data['items_id']);
         $link = "";
         if ($temp->can($temp->fields['id'], READ)) {
            $link = $temp->getLinkURL();
         }
         $addOptions = "";
         if ($data['itemtype'] == $item::getType()
             && $data['items_id'] == $item->fields['id']) {
            // will fixes this node
            $addOptions = ", shapeProperties: { useBorderWithImage: true } ";
         }
         if (!isset($temp->fields['name'])) {
            $temp->fields['name'] = $temp->getNameID();
         }
         //$GLPI_CACHE->delete("impact_pics".$data['itemtype']);
         $path = $imgpath."/undefined.png";
         if (Toolbox::useCache()) {
            if ($GLPI_CACHE->has("impact_pics".$data['itemtype'])) {
               $path = $GLPI_CACHE->get("impact_pics".$data['itemtype']);
            }
            if (file_exists(GLPI_ROOT."/".$imgpath."/".$data['itemtype'].".png") && !$GLPI_CACHE->has("impact_pics".$data['itemtype'])) {
               $GLPI_CACHE->set("impact_pics".$data['itemtype'], $imgpath."/".$data['itemtype'].".png");
               $path = $GLPI_CACHE->get("impact_pics".$data['itemtype']);
            }
         } else {
            if (file_exists(GLPI_ROOT."/".$imgpath."/".$data['itemtype'].".png")) {
               $path =  $imgpath."/".$data['itemtype'].".png";
            }
         }

         $nodestring .= "{ id: '$id', title: '".$temp->fields['name']."', label: '".substr($temp->fields['name'], 0, 10)."', image: '$path', shape: 'image', urllink: '$link' $addOptions},";
      }

      $currentID = $item::getType().'['.$item->fields['id'].']';
      $JS = <<<JAVASCRIPT
         // create an array with nodes
         var nodes = new vis.DataSet([{$nodestring}]);

         // create an array with edges
         var edges = new vis.DataSet([{$edgestring}]);

         // create a network
         var container = document.getElementById("mynetwork");
         var data = {
            nodes: nodes,
            edges: edges
         };
         var options = {
         };
         var network = new vis.Network(container, data, options);
         network.on("doubleClick", function (properties) {
            if (properties.nodes.length > 0) {
               var currentID = "{$currentID}";
               if (currentID != properties.nodes[0] && nodes.get(properties.nodes[0]).urllink != "") {
                  document.location.href = nodes.get(properties.nodes[0]).urllink ;
               }
            }
         });
         network.selectNodes(["{$currentID}"], true);
JAVASCRIPT;
      echo Html::scriptBlock($JS);

   }

   /**
    * Summary of showOppositeListForItem
    * @param CommonGLPI $item
    * @param mixed $direction
    * @param mixed $title
    * @param mixed $options
    */
   static function showOppositeListForItem(CommonGLPI $item, $direction, $title, $options = []) {
      global $DB;

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

      $itemtype  = $item::getType();
      $canupdate = $item->canUpdateItem() && $itemtype::canView() && $itemtype::canUpdate();

      $columns = [
         'itemtype'      => __('Item type'),
         'name'          => __('Name'),
         'date_creation' => __('Date')
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
      foreach ($oppositeItems as $rec) {
         $itemtypes[$rec[$itemtype_active]] = $rec[$itemtype_active];
      }

      $query = [];
      $subQueries = [];
      foreach ($itemtypes as $itemtype) {
         $temp = new $itemtype;
         $temp->getEmpty();
         $field = "it.name";
         if (!isset($temp->fields['name'])) {
            $field = "it.id";
         }
         $subQueries[] = [
               'SELECT'     => ["rel.id AS assocID", "rel.date_creation", "rel.$itemtype_active AS itemtype", "rel.$items_id_active AS items_id", $field],
               'FROM'       => self::getTable()." AS rel",
               'INNER JOIN' => [$itemtype::getTable()." AS it" => [
                  'FKEY' => [
                           'rel' => $items_id_active,
                           'it'  => 'id',
                           ['AND' => [
                              "rel.$itemtype_active" => $itemtype
                              ]
                           ]
                     ]
                  ]
               ],
               'WHERE'      => [
                  'AND' => [
                     "rel.$itemtype_passive" => $item::getType(),
                     "rel.$items_id_passive" => $item->fields['id']
                  ]
               ]
            ];
      }
      if (count($subQueries) > 1) {
         $query = new QueryUnion($subQueries, true);
      } else if (count($subQueries) == 1) {
         $query = $subQueries[0];
      }

      $number = 0; // by default
      if (!empty($query)) {
         if (!$query instanceof QueryUnion) {
            $result = $DB->request($query);
         } else {
            $result = $DB->request([
                           'FROM' => $query,
                           'ORDER' => ["$sort $order"]
               ]);
         }
         $number = count($result);
      }

      $impacts = [];
      if ($number) {
         foreach ($result as $row) {
            $impacts[$row['assocID']] = $row;
         }
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='2'>".sprintf($title, $item->fields['name'])."</th></tr>";
      echo "</table>";

      if ($canupdate) {
         echo "<div class='firstbloc'>";
         echo "<form name='relation_form$rand' id='relation_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";

         echo "<tr class='tab_bg_1'><td>".__('Add a new asset', 'impacts')."</td><td>";

         $used = [];
         $used[$item::getType()][] = $item->fields['id']; // to prevent re-use of current item on itself
         foreach ($oppositeItems  as $val) {
            $used[$val[$itemtype_active]][] = $val[$items_id_active];
         }

         self::dropdownAllDevices($itemtype_active, null, 0, 1, 0, -1, ['used' => $used, 'myname' => $items_id_active]);
         echo "<span id='item_ticket_selection_information'></span>";
         echo "</td><td class='center' width='30%'>";
         echo Html::submit(_sx('button', 'Add'), ['name' => 'add']);
         echo Html::hidden($itemtype_passive, ['value' => $item::getType()]);
         echo Html::hidden($items_id_passive, ['value' => $item->fields['id']]);
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
      global $CFG_GLPI;

      $params = [
         'used'     => [],
         'multiple' => 0,
         'rand'     => mt_rand(),
         'myname'   => 'items_id'
      ];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $rand = $params['rand'];

      echo "<div id='relation_all_devices$rand'>";
      $types = PluginImpactsConfig::getAssetList();
      $emptylabel = Dropdown::EMPTY_VALUE;
      Dropdown::showItemTypes($myname, array_keys($types), [
         'emptylabel'          => $emptylabel,
         'value'               => $itemtype,
         'rand'                => $rand,
         'display_emptychoice' => true
      ]);

      Ajax::updateItemOnSelectEvent(
         "dropdown_$myname$rand",
         "results_$myname$rand",
         $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",
         [
            'idtable'         => '__VALUE__',
            'name'            => $params['myname'],
            'rand'            => $rand,
            'used'            => $params['used'],
            'admin'           => $admin,
            'multiple'        => $params['multiple'],
            'entity_restrict' => $entity_restrict,
         ]
      );
      echo "<span id='results_$myname$rand'>\n";

      echo "</span>\n";
      echo "</div>";

      return $rand;
   }

}
