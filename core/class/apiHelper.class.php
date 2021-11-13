<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

class apiHelper {

  // CMD FUNCTIONS

  public static function getInfoCmdList($config) {
    $return = array();
    foreach ($config['payload']['widgets'] as $widget) {
      foreach ($widget as $item => $value) {
        if (is_array($value)) {
          if (array_key_exists('type', $value)) {
            if ($value['type'] == 'info') {
              array_push($return, $value['id']);
            }
          }
          if ($item == 'moreInfos') {
            foreach ($value as $i => $info) {
              array_push($return, $info['id']);
            }
          }
        }
      }
    }
    if (array_key_exists('background', $config['payload'])) {
      foreach ($config['payload']['background']['condBackgrounds'] as $cond) {
        preg_match_all("/#([a-zA-Z0-9]*)#/", $cond['condition'], $matches);
        if (count($matches) > 0) {
          $matches = array_unique($matches[0]);
          foreach ($matches as $match) {
            $cmd = cmd::byId(str_replace('#', '', $match));
            if (is_object($cmd)) {
              array_push($return, str_replace('#', '', $match));
            }
          }
        }
      }
    }
    if (array_key_exists('weather', $config['payload'])) {
      $return = array_merge($return, array(
        $config['payload']['weather']['condition'],
        $config['payload']['weather']['condition_id'],
        $config['payload']['weather']['sunrise'],
        $config['payload']['weather']['sunset'],
        $config['payload']['weather']['temperature'],
        $config['payload']['weather']['temperature_min'],
        $config['payload']['weather']['temperature_max']
      ));
    }

    foreach ($config['payload']['customData']['widgets'] as $widgetId => $widget) {
      foreach ($widget as $item => $value) {
        if ($item == 'moreInfos') {
          foreach ($value as $i => $info) {
            array_push($return, $info['id']);
          }
        }
      }
    }

    return array_unique($return);
  }

  public static function getCmdInfoData($config) {
    $cmds = cmd::byIds(self::getInfoCmdList($config));
    $result = array();

    foreach ($cmds as $cmd) {
      $state = $cmd->getCache(array('valueDate', 'value'));
      $cmd_info = array(
        'id' => $cmd->getId(),
        'value' => $state['value'],
        'modified' => strtotime($state['valueDate'])
      );
      array_push($result, $cmd_info);
    }
    return $result;
  }

  // SCEANRIO FUNCTIONS

  public static function getScenarioList($config) {
    $return = array();
    foreach ($config['payload']['widgets'] as $widget) {
      if (array_key_exists('type', $widget)) {
        if ($widget['type'] == 'scenario') {
          array_push($return, $widget['scenarioId']);
        }
      }
    }
    return array_unique($return);
  }

  public static function getScenarioData($config, $all = false) {
    $scIds = self::getScenarioList($config);
    $result = array();

    foreach (scenario::all() as $sc) {
      if (in_array($sc->getId(), $scIds) || $all) {
        $state = $sc->getCache(array('state', 'lastLaunch'));
        $sc_info = array(
          'id' => $sc->getId(),
          'name' => $sc->getName(),
          'object' => $sc->getObject() == null ? 'Aucun' : $sc->getObject()->getName(),
          'group' => $sc->getGroup() == '' ? 'Aucun' : $sc->getGroup(),
          'status' => $state['state'],
          'lastLaunch' => strtotime($state['lastLaunch']),
          'active' => $sc->getIsActive() ? 1 : 0,
          'icon' => self::getScenarioIcon($sc)
        );
        array_push($result, $sc_info);
      }
    }
    return $result;
  }

  public static function getScenarioIcon($sc) {
    if (strpos($sc->getDisplay('icon'), '<i') === 0) {
      return trim(str_replace(array('<i', 'class=', '"', '/>', '></i>'), '', $sc->getDisplay('icon')));
    }
    return null;
  }

  // OBJECT FUNCTIONS
  public static function getObjectList($config) {
    $return = array();
    foreach ($config['payload']['rooms'] as $room) {
      if (array_key_exists("id", $room)) {
        array_push($return, $room['id']);
      }
    }
    return array_unique($return);
  }

  public static function getObjectData($config) {
    $objIds = self::getObjectList($config);
    $result = array();

    foreach (jeeObject::all() as $object) {
      if (in_array($object->getId(), $objIds)) {
        $object_info = array(
          'object_id' => $object->getId(),
          'keys' => array()
        );
        foreach ($object->getConfiguration('summary') as $key => $value) {
          $sum = $object->getSummary($key);
          array_push($object_info['keys'], array(
            $key => array('value' => $sum, 'cmds' => $value)
          ));
        }
        array_push($result, $object_info);
      }
    }
    $global_info = array(
      'object_id' => 'global',
      'keys' => array()
    );
    $globalSum = config::byKey('object:summary');
    foreach ($globalSum as $key => $value) {
      array_push($global_info['keys'], array(
        $key => array('value' => jeeObject::getGlobalSummary($key))
      ));
    }
    array_push($result, $global_info);
    return $result;
  }

  // GEOFENCE FUNCTIONS
  public static function getGeofencesData($eqLogic) {
    $result = array(
      'type' => 'SET_GEOFENCES',
      'payload' => array(
        'geofences' => array()
      )
    );
    foreach ($eqLogic->getCmd('info') as $cmd) {
      if (substr($cmd->getLogicalId(), 0, 8) === "geofence") {
        array_push($result['payload']['geofences'], array(
          'identifier' => substr($cmd->getLogicalId(), 9),
          'extras' => array(
            'name' => $cmd->getName()
          ),
          'radius' => $cmd->getConfiguration('radius'),
          'latitude' => $cmd->getConfiguration('latitude'),
          'longitude' => $cmd->getConfiguration('longitude'),
          'notifyOnEntry' => true,
          'notifyOnExit' => true
        ));
      }
    }
    return $result;
  }

  //PLUGIN CONF FUNCTIONS
  public static function getPluginConfig() {
    $plugin = update::byLogicalId('JeedomConnect');
    return array(
      'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
      'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
      'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
      'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'),
      'pluginJeedomVersion' => $plugin->getLocalVersion()
    );
  }

  // REGISTER FUNCTION
  public static function registerUser($eqLogic, $userHash, $rdk, $user = null) {
    if ($user == null) {
      $user = user::byHash($userHash);
    }
    if (!isset($user)) {
      return null;
    }
    $eqLogic->setConfiguration('userHash', $userHash);
    $eqLogic->save();

    $registerDevice = $user->getOptions('registerDevice', array());
    if (!is_array($registerDevice)) {
      $registerDevice = array();
    }
    if (!isset($rdk) || !isset($registerDevice[sha512($rdk)])) {
      $rdk = config::genKey();
    }
    $registerDevice[sha512($rdk)] = array();
    $registerDevice[sha512($rdk)]['datetime'] = date('Y-m-d H:i:s');
    $registerDevice[sha512($rdk)]['ip'] = getClientIp();
    $registerDevice[sha512($rdk)]['session_id'] = session_id();
    if (version_compare(PHP_VERSION, '7.3') >= 0) {
      setcookie('registerDevice', $userHash . '-' . $rdk, ['expires' => time() + 365 * 24 * 3600, 'samesite' => 'Strict', 'httponly' => true, 'path' => '/', 'secure' => (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')]);
    } else {
      setcookie('registerDevice', $userHash . '-' . $rdk, time() + 365 * 24 * 3600, "/; samesite=strict", '', (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'), true);
    }
    @session_start();
    $user->refresh();
    $user->setOptions('registerDevice', $registerDevice);
    $user->save();
    @session_write_close();
    return $rdk;
  }

  // Config Watcher
  public static function lookForNewConfig($eqLogic, $prevConfig) {
    $configVersion = $eqLogic->getConfiguration('configVersion');
    //log::add('JeedomConnect', 'debug',   "apiHelper : Look for new config, compare ".$configVersion." and ".$prevConfig);
    if ($configVersion != $prevConfig) {
      log::add('JeedomConnect', 'debug', "apiHelper : New configuration");
      return $eqLogic->getGeneratedConfigFile();
    }
    return false;
  }

  // JEEDOM FULL DATA
  public static function getFullJeedomData() {
    $result = array(
      'type' => 'SET_JEEDOM_DATA',
      'payload' => array(
        'cmds' => array(),
        'eqLogics' => array(),
        'objects' => array(),
        'scenarios' => array(),
        'summariesConfig' => array()
      )
    );

    foreach (cmd::all() as $item) {
      $array = utils::o2a($item);
      $cmd = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'type' => $array['type'],
        'subType' => $array['subType'],
        'eqLogic_id' => $array['eqLogic_id'],
        'unite' => $array['unite'],
        'isHistorized' => $array['isHistorized'],
        'configuration' => $array['configuration'],
      );
      array_push($result['payload']['cmds'], $cmd);
    }
    foreach (eqLogic::all() as $item) {
      $array = utils::o2a($item);
      $eqLogic = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'object_id' => $array['object_id'],
        'isEnable' => $array['isEnable']
      );
      array_push($result['payload']['eqLogics'], $eqLogic);
    }
    foreach (jeeObject::all() as $item) {
      $array = utils::o2a($item);
      $jeeObject = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'display' => self::getIconAndColor($array['display']['icon'])
      );
      array_push($result['payload']['objects'], $jeeObject);
    }
    foreach (scenario::all() as $item) {
      $array = utils::o2a($item);
      $scenario = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'group' => $array['group'],
        'object_id' => $array['object_id'],
      );
      array_push($result['payload']['scenarios'], $scenario);
    }

    foreach (config::byKey('object:summary') as $item) {
      $item['display'] = self::getIconAndColor($item['icon']);
      $item['icon'] = trim(preg_replace('/ icon_(red|yellow|blue|green|orange)/', '', $item['icon']));
      array_push($result['payload']['summariesConfig'], $item);
    }

    return $result;
  }

  public static function getIconAndColor($iconClass) {
    $newIconClass = trim(preg_replace('/ icon_(red|yellow|blue|green|orange)/', '', $iconClass));
    $matches = array();
    preg_match('/(.*)class=\"(.*)\"(.*)/', $iconClass, $matches);

    if (count($matches) > 3) {
      list($iconType, $iconImg) = explode(" ", $matches[2], 2);
      $iconType = ($iconType == 'icon') ? 'jeedom' : 'fa';
      $iconImg = ($iconType == 'fa') ? trim(str_replace('fa-', '', $iconImg)) : trim($iconImg);

      preg_match('/(.*) icon_(.*)/', $iconImg, $matches);
      $color = '';
      if (count($matches) > 2) {
        switch ($matches[2]) {
          case 'blue':
            $color = '#0000FF';
            break;
          case 'yellow':
            $color = '#FFFF00';
            break;
          case 'orange':
            $color = '#FFA500';
            break;
          case 'red':
            $color = '#FF0000';
            break;
          case 'green':
            $color = '#008000';
            break;
          default:
            $color = '';
            break;
        }
        $iconImg = trim(str_replace('icon_' . $matches[2], '', $iconImg));
      }

      return array('icon' => $newIconClass, 'source' => $iconType, 'name' => $iconImg, 'color' => $color);
    }

    return array('icon' => $newIconClass, 'source' => '', 'name' => '', 'color' => '');
  }

  //WIDGET DATA
  public static function getWidgetData() {
    $result = array(
      'type' => 'SET_WIDGET_DATA',
      'payload' => array(
        'widgets' => JeedomConnectWidget::getWidgets()
      )
    );

    return $result;
  }

  // EDIT FUNCTIONS
  public static function setWidget($widget) {
    log::add('JeedomConnect', 'debug', 'save widget data');
    JeedomConnectWidget::updateWidgetConfig($widget);
  }

  public static function addWidgets($eqLogic, $widgets, $parentId, $index) {
    $curConfig = $eqLogic->getConfig();

    foreach ($curConfig['payload']['widgets'] as $i => $widget) {
      if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
        $curConfig['payload']['widgets'][$i]['index'] += count($widgets);
      }
    }
    foreach ($curConfig['payload']['groups'] as $i => $group) {
      if ($group['parentId'] == $parentId && $group['index'] > $index) {
        $curConfig['payload']['groups'][$i]['index'] += count($widgets);
      }
    }

    $curIndex = $index + 1;
    foreach ($widgets as $widget) {
      $newWidget = array(
        'index' => $curIndex,
        'widgetId' => $curConfig['idCounter'],
        'id' => $widget['id']
      );
      if (!is_null($parentId)) {
        $newWidget['parentId'] = $parentId;
      }
      array_push($curConfig['payload']['widgets'], $newWidget);
      $curIndex++;
      $curConfig['idCounter'] = $curConfig['idCounter'] + 1;
    }

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function removeWidget($eqLogic, $widgetId) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($widgetId, array_column($curConfig['payload']['widgets'], 'widgetId'));
    if ($toRemove !== false) {
      $parentId = $curConfig['payload']['widgets'][$toRemove]['parentId'];
      $index = $curConfig['payload']['widgets'][$toRemove]['index'];
      unset($curConfig['payload']['widgets'][$toRemove]);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $parentId && $group['index'] > $index) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
      }

      $eqLogic->saveConfig($curConfig);
      $eqLogic->cleanCustomData();
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function moveWidget($eqLogic, $widgetId, $destinationId, $destinationIndex) {
    $curConfig = $eqLogic->getConfig();
    $widgetIndex = array_search($widgetId, array_column($curConfig['payload']['widgets'], 'widgetId'));
    if ($widgetIndex === false) {
      return;
    }

    $moved = false;

    $oldIndex = $curConfig['payload']['widgets'][$widgetIndex]['index'];
    $oldParentId = $curConfig['payload']['widgets'][$widgetIndex]['parentId'];
    if (isset($destinationIndex)) {
      $newIndex = $destinationIndex;
    } else {
      $newIndex = 0;
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
    }


    $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['tabs'], 'id'));
    if ($destinationParentIndex !== false) { //destination is a bottom tab
      $moved = true;
    } else {
      $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['sections'], 'id'));
      if ($destinationParentIndex !== false) { //destination is a top tab  
        $moved = true;
      } else {
        $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['groups'], 'id'));
        if ($destinationParentIndex !== false) { //destination is a group  
          $moved = true;
        }
      }
    }

    if ($moved) {
      //re-index pages
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['widgets'][$i]['index'] += 1;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['groups'][$i]['index'] += 1;
        }
      }

      $curConfig['payload']['widgets'][$widgetIndex]['parentId'] = $destinationId;
      $curConfig['payload']['widgets'][$widgetIndex]['index'] = $newIndex;


      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function setCustomWidgetList($eqLogic, $customWidgetList) {
    $apiKey = $eqLogic->getConfiguration('apiKey');
    foreach ($customWidgetList as $customWidget) {
      log::add('JeedomConnect', 'debug', 'save custom data for widget [' . $customWidget['widgetId'] . '] : ' . json_encode($customWidget));
      config::save('customData::' . $apiKey . '::' . $customWidget['widgetId'], json_encode($customWidget), 'JeedomConnect');
    }
    $eqLogic->generateNewConfigVersion();
  }

  public static function setGroup($eqLogic, $group) {
    $curConfig = $eqLogic->getConfig();
    $toEdit = array_search($group['id'], array_column($curConfig['payload']['groups'], 'id'));
    if ($toEdit !== false) {
      $curConfig['payload']['groups'][$toEdit] = $group;
      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function removeGroup($eqLogic, $id) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($id, array_column($curConfig['payload']['groups'], 'id'));
    if ($toRemove !== false) {
      $parentId = $curConfig['payload']['groups'][$toRemove]['parentId'];
      $index = $curConfig['payload']['groups'][$toRemove]['index'];
      unset($curConfig['payload']['groups'][$toRemove]);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);

      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
        if ($widget['parentId'] == $id) {
          unset($curConfig['payload']['widgets'][$i]);
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $parentId && $group['index'] > $index) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
      }
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function addGroup($eqLogic, $curGroup) {
    $curConfig = $eqLogic->getConfig();
    $parentId = $curGroup['parentId'];
    $index = $curGroup['index'];
    $maxId = 999000;

    foreach ($curConfig['payload']['widgets'] as $i => $widget) {
      if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
        $curConfig['payload']['widgets'][$i]['index'] += 1;
      }
    }
    foreach ($curConfig['payload']['groups'] as $i => $group) {
      if ($group['parentId'] == $parentId && $group['index'] > $index) {
        $curConfig['payload']['groups'][$i]['index'] += 1;
      }
      if ($group['id'] >= $maxId) {
        $maxId = $group['id'] + 1;
      }
    }

    $curGroup['id'] = $maxId;
    array_push($curConfig['payload']['groups'], $curGroup);
    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function moveGroup($eqLogic, $groupId, $destinationId, $destinationIndex) {
    $curConfig = $eqLogic->getConfig();
    $groupIndex = array_search($groupId, array_column($curConfig['payload']['groups'], 'id'));
    if ($groupIndex === false) {
      return;
    }

    $moved = false;

    $oldIndex = $curConfig['payload']['groups'][$groupIndex]['index'];
    $oldParentId = $curConfig['payload']['groups'][$groupIndex]['parentId'];

    if (isset($destinationIndex)) {
      $newIndex = $destinationIndex;
    } else {
      $newIndex = 0;
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
    }

    $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['tabs'], 'id'));
    if ($destinationParentIndex !== false) { //destination is a bottom tab
      $moved = true;
    } else {
      $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['sections'], 'id'));
      if ($destinationParentIndex !== false) { //destination is a top tab  
        $moved = true;
      }
    }

    if ($moved) {
      //re-index pages
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['widgets'][$i]['index'] += 1;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['groups'][$i]['index'] += 1;
        }
      }

      $curConfig['payload']['groups'][$groupIndex]['parentId'] = $destinationId;
      $curConfig['payload']['groups'][$groupIndex]['index'] = $newIndex;

      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function removeGlobalWidget($id) {
    JeedomConnectWidget::removeWidget($id);
  }

  public static function addGlobalWidget($widget) {
    $newConfWidget = array();
    $widgetsConfigJonFile = json_decode(file_get_contents(JeedomConnect::$_plugin_config_dir . 'widgetsConfig.json'), true);
    $imgPath = '';
    foreach ($widgetsConfigJonFile['widgets'] as $config) {
      if ($config['type'] == $widget['type']) {
        $imgPath = 'plugins/JeedomConnect/data/img/' . $config['img'];
        break;
      }
    }
    $widgetId = JeedomConnectWidget::incrementIndex();
    $widget['id'] = $widgetId;

    $newConfWidget['imgPath'] = $imgPath;
    $newConfWidget['widgetJC'] = json_encode($widget);

    config::save('widget::' . $widgetId, $newConfWidget, JeedomConnectWidget::$_plugin_id);

    return array(
      'type' => 'SET_SINGLE_WIDGET_DATA',
      'payload' => $widget
    );
  }

  public static function setBottomTabList($eqLogic, $tabs, $migrate = false, $idCounter) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['idCounter'] = $idCounter;

    //remove children
    foreach ($tabs as $tab) {
      $oldTabIndex = array_search($tab['id'], array_column($curConfig['payload']['tabs'], 'id'));
      if ($tab['index'] < 0) { //tabs with negative index have to be removed
        if ($oldTabIndex !== false) {
          unset($curConfig['payload']['tabs'][$oldTabIndex]);
        }
        $sectionsToRemove = array();
        foreach ($curConfig['payload']['sections'] as $i => $section) {
          if ($section['parentId'] == $tab['id']) {
            array_push($sectionsToRemove, $section['id']);
            unset($curConfig['payload']['sections'][$i]);
          }
        }
        $groupsToRemove = array();
        foreach ($curConfig['payload']['groups'] as $i => $group) {
          if ($group['parentId'] == $tab['id'] || in_array($group['parentId'], $sectionsToRemove)) {
            array_push($groupsToRemove, $group['id']);
            unset($curConfig['payload']['groups'][$i]);
          }
        }
        foreach ($curConfig['payload']['widgets'] as $i => $widget) {
          if ($widget['parentId'] == $tab['id'] || in_array($widget['parentId'], $groupsToRemove) || in_array($widget['parentId'], $sectionsToRemove)) {
            unset($curConfig['payload']['widgets'][$i]);
          }
        }
      } else {
        if ($oldTabIndex !== false) {
          $curConfig['payload']['tabs'][$oldTabIndex] = $tab;
        } else {
          array_push($curConfig['payload']['tabs'], $tab);
        }
      }
      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
    }

    if ($migrate) {
      $firstTab = array_search(0, array_column($tabs, 'index'));
      $id = $tabs[$firstTab]['id'];
      if (count($curConfig['payload']['sections']) > 0) {
        foreach ($curConfig['payload']['sections'] as $i => $section) {
          $curConfig['payload']['sections'][$i]['parentId'] = $id;
        }
      } else {
        foreach ($curConfig['payload']['groups'] as $i => $group) {
          $curConfig['payload']['groups'][$i]['parentId'] = $id;
        }
        foreach ($curConfig['payload']['widgets'] as $i => $widget) {
          if ($widget['parentId'] < 999000) {
            $curConfig['payload']['widgets'][$i]['parentId'] = $id;
          }
        }
      }
    }

    $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
    $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
    $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
    $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function removeBottomTab($eqLogic, $id) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($id, array_column($curConfig['payload']['tabs'], 'id'));
    if ($toRemove !== false) {
      $index = $curConfig['payload']['tabs'][$toRemove]['index'];
      $parentId = $curConfig['payload']['tabs'][$toRemove]['parentId'];
      unset($curConfig['payload']['tabs'][$toRemove]);

      // remove children
      $sectionsToRemove = array();
      foreach ($curConfig['payload']['sections'] as $i => $section) {
        if ($section['parentId'] == $id) {
          array_push($sectionsToRemove, $section['id']);
          unset($curConfig['payload']['sections'][$i]);
        }
      }
      $groupsToRemove = array();
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $id || in_array($group['parentId'], $sectionsToRemove)) {
          array_push($groupsToRemove, $group['id']);
          unset($curConfig['payload']['groups'][$i]);
        }
      }
      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $id || in_array($widget['parentId'], $groupsToRemove) || in_array($widget['parentId'], $sectionsToRemove)) {
          unset($curConfig['payload']['widgets'][$i]);
        }
      }
      //re-index tabs
      foreach ($curConfig['payload']['tabs'] as $i => $tab) {
        if ($tab['parentId'] == $parentId && $tab['index'] > $index) {
          $curConfig['payload']['tabs'][$i]['index'] -= 1;
        }
      }

      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->cleanCustomData();
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function setTopTabList($eqLogic, $tabs, $migrate = false, $idCounter) {
    if (count($tabs) == 0) {
      return;
    }
    $curConfig = $eqLogic->getConfig();
    $curConfig['idCounter'] = $idCounter;

    //remove children
    foreach ($tabs as $tab) {
      $oldTabIndex = array_search($tab['id'], array_column($curConfig['payload']['sections'], 'id'));
      if ($tab['index'] < 0) { //tabs with negative index have to be removed
        if ($oldTabIndex !== false) {
          unset($curConfig['payload']['sections'][$oldTabIndex]);
        }
        $groupsToRemove = array();
        foreach ($curConfig['payload']['groups'] as $i => $group) {
          if ($group['parentId'] == $tab['id']) {
            array_push($groupsToRemove, $group['id']);
            unset($curConfig['payload']['groups'][$i]);
          }
        }
        foreach ($curConfig['payload']['widgets'] as $i => $widget) {
          if ($widget['parentId'] == $tab['id'] || in_array($widget['parentId'], $groupsToRemove)) {
            unset($curConfig['payload']['widgets'][$i]);
          }
        }
      } else {
        if ($oldTabIndex !== false) {
          $curConfig['payload']['sections'][$oldTabIndex] = $tab;
        } else {
          array_push($curConfig['payload']['sections'], $tab);
        }
      }
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
    }

    if ($migrate) {
      $firstTab = array_search(0, array_column($tabs, 'index'));
      $id = $tabs[$firstTab]['id'];
      $parentId = $tabs[$firstTab]['parentId'];
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if (!isset($group['parentId']) || $group['parentId'] == $parentId) {
          $curConfig['payload']['groups'][$i]['parentId'] = $id;
        }
      }
      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if (!isset($widget['parentId']) || $widget['parentId'] == $parentId) {
          $curConfig['payload']['widgets'][$i]['parentId'] = $id;
        }
      }
    }

    $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
    $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
    $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

    $eqLogic->saveConfig($curConfig);
    $eqLogic->cleanCustomData();
    $eqLogic->generateNewConfigVersion();
  }

  public static function removeTopTab($eqLogic, $id) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($id, array_column($curConfig['payload']['sections'], 'id'));
    if ($toRemove !== false) {
      $parentId = $curConfig['payload']['sections'][$toRemove]['parentId'];
      $index = $curConfig['payload']['sections'][$toRemove]['index'];
      unset($curConfig['payload']['sections'][$toRemove]);

      // remove children
      $groupsToRemove = array();
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $id) {
          array_push($groupsToRemove, $group['id']);
          unset($curConfig['payload']['groups'][$i]);
        }
      }
      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $id || in_array($widget['parentId'], $groupsToRemove)) {
          unset($curConfig['payload']['widgets'][$i]);
        }
      }
      //re-index sections
      foreach ($curConfig['payload']['sections'] as $i => $section) {
        if ($section['parentId'] == $parentId && $section['index'] > $index) {
          $curConfig['payload']['sections'][$i]['index'] -= 1;
        }
      }

      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->cleanCustomData();
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function moveTopTab($eqLogic, $sectionId, $destinationId) {
    $curConfig = $eqLogic->getConfig();
    $sectionIndex = array_search($sectionId, array_column($curConfig['payload']['sections'], 'id'));
    if ($sectionIndex === false) {
      return;
    }

    $moved = false;

    $oldIndex = $curConfig['payload']['sections'][$sectionIndex]['index'];
    $oldParentId = $curConfig['payload']['sections'][$sectionIndex]['parentId'];

    $newIndex = 0;
    foreach ($curConfig['payload']['sections'] as $i => $item) {
      if ($item['parentId'] == $destinationId) {
        $newIndex++;
      }
    }

    $destinationIndex = array_search($destinationId, array_column($curConfig['payload']['tabs'], 'id'));
    if ($destinationIndex !== false) { //destination is a bottom tab
      $moved = true;
    }

    if ($moved) {
      $curConfig['payload']['sections'][$sectionIndex]['parentId'] = $destinationId;
      $curConfig['payload']['sections'][$sectionIndex]['index'] = $newIndex;
      //re-index initial tab
      foreach ($curConfig['payload']['sections'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['sections'][$i]['index'] -= 1;
        }
      }

      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  // Receive root data (already ordered) (widgets, groups) for a page view (index < 0 ==> remove)
  public static function setPageData($eqLogic, $rootData, $idCounter) {
    if (count($rootData) == 0) {
      return;
    }
    $curConfig = $eqLogic->getConfig();
    $curConfig['idCounter'] = $idCounter;
    $parentId = $rootData[0]['parentId'] ?? null;

    foreach ($rootData as $element) {
      $type = 'groups';
      $id = 'id';
      if (isset($element['widgetId'])) { //this is a widget
        $type = 'widgets';
        $id = 'widgetId';
      }
      $oldWidgetIndex = array_search($element[$id], array_column($curConfig['payload'][$type], $id));
      if ($element['index'] < 0) {
        if ($oldWidgetIndex !== false) {
          unset($curConfig['payload'][$type][$oldWidgetIndex]);
        }
      } else {
        if ($oldWidgetIndex !== false) { //update element
          $curConfig['payload'][$type][$oldWidgetIndex] = $element;
        } else { //add element
          array_push($curConfig['payload'][$type], $element);
        }
      }
      $curConfig['payload'][$type] = array_values($curConfig['payload'][$type]);
    }

    $eqLogic->saveConfig($curConfig);
    $eqLogic->cleanCustomData();
    $eqLogic->generateNewConfigVersion();
  }

  public static function setRooms($eqLogic, $rooms) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['payload']['rooms'] = $rooms;

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function setSummaries($eqLogic, $summaries) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['payload']['summaries'] = $summaries;

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function setBackgrounds($eqLogic, $backgrounds) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['payload']['background'] = $backgrounds;

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }



  public static function generateWidgetWithGenType($_widget_type, $_eqLogicId) {
    $result = array(
      'type' => 'SET_WIDGET_WITH_GEN_TYPE',
      'payload' => null
    );

    if ($_widget_type == null) return $result;

    $widgetConfigParam = JeedomConnect::getWidgetParam(false, array($_widget_type));
    $widgetConfig = $widgetConfigParam[$_widget_type] ?? null;

    if ($widgetConfig == null) return $result;

    $genericTypes = array_unique(JeedomConnectUtils::getGenericType($widgetConfig));
    if ($genericTypes == null) return $result;

    $cmdGeneric = JeedomConnectUtils::getCmdForGenericType($genericTypes, $_eqLogicId);

    $result['payload'] = JeedomConnectUtils::createAutoWidget($_widget_type, $widgetConfig, $cmdGeneric);
    return $result;
  }



  // EVENTS FUNCTION
  public static function getEvents($events, $config, $scAll = false) {
    $result_cmd = array(
      'type' => 'CMD_INFO',
      'payload' => array()
    );
    $infoIds = self::getInfoCmdList($config);
    $result_sc = array(
      'type' => 'SC_INFO',
      'payload' => array()
    );
    $scIds = self::getScenarioList($config);
    $result_obj = array(
      'type' => 'OBJ_INFO',
      'payload' => array()
    );
    $objIds = self::getObjectList($config);

    foreach ($events['result'] as $event) {
      if ($event['name'] == 'jeeObject::summary::update') {
        array_push($result_obj['payload'], $event['option']);
      }
      if ($event['name'] == 'scenario::update') {
        if (in_array($event['option']['scenario_id'], $scIds) || $scAll) {
          $sc_info = array(
            'id' => $event['option']['scenario_id'],
            'status' => $event['option']['state'],
            'lastLaunch' => strtotime($event['option']['lastLaunch'])
          );
          if (array_key_exists('isActive', $event['option'])) {
            $sc_info['active'] = $event['option']['isActive'];
          }
          array_push($result_sc['payload'], $sc_info);
        }
      }
      if ($event['name'] == 'cmd::update') {
        if (in_array($event['option']['cmd_id'], $infoIds)) {
          $cmd_info = array(
            'id' => $event['option']['cmd_id'],
            'value' => $event['option']['value'],
            'modified' => strtotime($event['option']['valueDate'])
          );
          array_push($result_cmd['payload'], $cmd_info);
        }
      }
    }
    return array($result_cmd, $result_sc, $result_obj);
  }

  //HISTORY
  public static function getHistory($id, $options = null) {
    $history = array();
    if ($options == null) {
      $history = history::all($id);
    } else {
      $startTime = date('Y-m-d H:i:s', $options['startTime']);
      $endTime = date('Y-m-d H:i:s', $options['endTime']);
      log::add('JeedomConnect', 'info', 'Get history from: ' . $startTime . ' to ' . $endTime);
      $history = history::all($id, $startTime, $endTime);
    }

    $result = array(
      'type' => 'SET_HISTORY',
      'payload' => array(
        'id' => $id,
        'data' => array()
      )
    );

    foreach ($history as $h) {
      array_push($result['payload']['data'], array(
        'time' => strtotime($h->getDateTime()),
        'value' => $h->getValue()
      ));
    }
    log::add('JeedomConnect', 'info', 'Send history (' . count($result['payload']['data']) . ' points)');
    return $result;
  }

  // BATTERIES
  public static function getBatteries() {
    $result = array(
      'type' => 'SET_BATTERIES',
      'payload' => JeedomConnect::getBatteryAllEquipements()
    );
    log::add('JeedomConnect', 'debug', 'Send batteries =>' . json_encode($result));
    return $result;
  }


  public static function saveBatteryEquipment($apiKey, $level) {

    $eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

    if (is_object($eqLogic)) {

      $batteryCmd = $eqLogic->getCmd(null, 'battery');

      if (is_object($batteryCmd)) {
        $batteryCmd->event($level);
      }

      if (!$eqLogic->getConfiguration('hideBattery') || $eqLogic->getConfiguration('hideBattery', -2) == -2) {
        $eqLogic->setStatus("battery", $level);
        $eqLogic->setStatus("batteryDatetime", date('Y-m-d H:i:s'));
        //  log::add('JeedomConnect', 'warning', 'saveBatteryEquipment | SAVING battery saved on equipment page '); 
      }
    } else {
      log::add('JeedomConnect', 'warning', 'saveBatteryEquipment | not able to retrieve an equipment for apiKey ' . $apiKey);
    }
  }


  // PLUGINS UPDATE

  public static function doUpdate($pluginId) {
    try {
      $update = update::byLogicalId($pluginId);

      if (!is_object($update)) {
        log::add('JeedomConnect', 'warning', 'doUpdate -- cannot update plugin ' . $pluginId);
        return false;
      }

      $update->doUpdate();
      return true;
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', 'doUpdate -- ' . $e->getMessage());
      return false;
    }
  }

  public static function getPluginsUpdate() {
    try {
      $result = array(
        'type' => 'SET_PLUGINS_UPDATE',
        'payload' => JeedomConnect::getPluginsUpdate()
      );
      log::add('JeedomConnect', 'debug', 'Send plugins update =>' . json_encode($result));
      return $result;
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', 'getUpdates -- ' . $e->getMessage());
      return false;
    }
  }

  // BACKUPS
  public static function setAppConfig($apiKey, $config) {
    $_backup_dir = __DIR__ . '/../../data/backups/';
    if (!is_dir($_backup_dir)) {
      mkdir($_backup_dir);
    }
    $eqDir = $_backup_dir . $apiKey . '/';
    if (!is_dir($eqDir)) {
      mkdir($eqDir);
    }
    $backupIndex = config::byKey('backupIndex::' . $apiKey, 'JeedomConnect');
    if (empty($backupIndex)) {
      $backupIndex = 0;
    }
    $backupIndex++;
    config::save('backupIndex::' . $apiKey, $backupIndex, 'JeedomConnect');

    $config_file = $eqDir . urlencode($config['name']) . '-' . $backupIndex . '.json';
    try {
      log::add('JeedomConnect', 'debug', 'Saving backup in file : ' . $config_file);
      file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', 'Unable to write file : ' . $e->getMessage());
    }
  }

  public static function getAppConfig($apiKey, $configId) {
    $_backup_dir = '/plugins/JeedomConnect/data/backups/';
    $eqDir = $_backup_dir . $apiKey;
    $files = self::getFiles($eqDir);
    $endFile = '-' . $configId . '.json';
    foreach ($files['payload']['files'] as $file) {
      if (substr_compare($file['path'], $endFile, -strlen($endFile)) === 0) {
        $config_file = file_get_contents($file['path']);
        return array(
          'type' => 'SET_APP_CONFIG',
          'payload' => array('config' => json_decode($config_file))
        );
      }
    }
    return false;
  }

  // JEEDOM & PLUGINS HEALTH

  public static function restartDaemon($userId, $pluginId) {
    $_plugin = \plugin::byId($pluginId);
    if (is_object($_plugin)) {
      log::add('JeedomConnect', 'debug', 'DAEMON restart by [' . $userId . '] =>' . $pluginId);
      $_plugin->deamon_start(true);
      return true;
    }
    return false;
  }

  public static function stopDaemon($userId, $pluginId) {
    $_plugin = \plugin::byId($pluginId);
    if (is_object($_plugin)) {
      log::add('JeedomConnect', 'debug', 'DAEMON stopped by [' . $userId . '] =>' . $pluginId);
      $_plugin->deamon_stop();
      return true;
    }
    return false;
  }

  public static function getJeedomHealthDetails($apiKey) {

    $result = array(
      'type' => 'SET_JEEDOM_GLOBAL_HEALTH',
      'payload' => JeedomConnect::getHealthDetails($apiKey)
    );
    log::add('JeedomConnect', 'debug', 'Send health =>' . json_encode($result));
    return $result;
  }


  //EXEC ACTIONS
  public static function execCmd($id, $options = null) {
    $cmd = cmd::byId($id);
    if (!is_object($cmd)) {
      log::add('JeedomConnect', 'error', "Can't find command [id=" . $id . "]");
      return;
    }
    try {
      $options = array_merge($options ?? array(), array('comingFrom' => 'JeedomConnect'));
      $cmd->execCmd($options);
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  public static function execMultipleCmd($cmdList) {
    foreach ($cmdList as $cmd) {
      self::execCmd($cmd['id'], $cmd['options']);
    }
  }

  public static function execSc($id, $options = null) {
    if ($options == null) {
      $options = array(
        'action' => 'start',
        'scenario_id' => $id
      );
    }
    try {
      scenarioExpression::createAndExec('action', 'scenario', $options);
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  public static function stopSc($id) {
    try {
      $sc = scenario::byId($id);
      $sc->stop();
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  public static function setActiveSc($id, $active) {
    try {
      $sc = scenario::byId($id);
      $sc->setIsActive($active);
      $sc->save();
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  // FILES
  public static function getFiles($folder, $recursive = false) {
    $dir = __DIR__ . '/../../../..' . $folder;
    $result = array();
    try {
      if (is_dir($dir)) {
        $dh = new DirectoryIterator($dir);
        foreach ($dh as $item) {
          if (!$item->isDot() && substr($item, 0, 1) != '.') {
            if (!$item->isDir()) {
              array_push($result, array(
                'path' =>  $item->getPathname(),
                'timestamp' => $item->getMTime()
              ));
            } else if ($recursive) {
              $subFolderFiles = self::getFiles(str_replace(__DIR__ . '/../../../..', '', preg_replace('#/+#', '/', $item->getPathname())), true);
              $result = array_merge($result, $subFolderFiles['payload']['files']);
            }
          }
        }
      }
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }

    return  array(
      'type' => 'SET_FILES',
      'payload' => array(
        'path' => $folder,
        'files' => $result
      )
    );
  }


  public static function removeFile($file) {
    $pathInfo = pathinfo($file);
    unlink($file);
    return
      self::getFiles(str_replace(__DIR__ . '/../../../..', '', preg_replace('#/+#', '/', $pathInfo['dirname'])), true);
  }
}
