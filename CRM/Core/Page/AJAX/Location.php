<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Core_Page_AJAX_Location {

  /**
   * FIXME: we should make this method like getLocBlock() OR use the same method and
   * remove this one.
   *
   * Function to obtain the location of given contact-id.
   * This method is used by on-behalf-of form to dynamically generate poulate the
   * location field values for selected permissioned contact.
   */
  static function getPermissionedLocation() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);
    $ufId = CRM_Utils_Request::retrieve('ufId', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);
    $relContact = CRM_Utils_Type::escape($_GET['relContact'], 'Integer', CRM_Core_DAO::$_nullObject, TRUE);

    // Verify user id
    $user = CRM_Utils_Request::retrieve('uid', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, CRM_Core_Session::singleton()->get('userID'));
    if (empty($user) || (CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE) && !CRM_Contact_BAO_Contact_Permission::validateChecksumContact($user, CRM_Core_DAO::$_nullObject, FALSE))
    ) {
      CRM_Utils_System::civiExit();
    }

    // Verify user permission on related contact
    $employers = CRM_Contact_BAO_Relationship::getPermissionedEmployer($user);
    if (!isset($employers[$cid])) {
      CRM_Utils_System::civiExit();
    }

    $values      = array();
    $entityBlock = array('contact_id' => $cid);
    $location    = CRM_Core_BAO_Location::getValues($entityBlock);

    $config = CRM_Core_Config::singleton();
    $addressSequence = array_flip($config->addressSequence());

    if (!empty($relContact)) {
      $elements = array(
        "phone_1_phone" =>
        $location['phone'][1]['phone'],
        "email_1_email" =>
        $location['email'][1]['email'],
      );
      
      $locationElements = array(
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'city',
        'postal_code',
        'postal_code_suffix',
        'country',
        'state_province',
      );
      
      foreach ($locationElements as $value) {
        if (array_key_exists($value, $addressSequence)) {
          if (in_array($value, array('country', 'state_province'))) {
            $value .= '_id';
          }
          $elements["address_1_{$value}"] =  $location['address'][1][$value];
        }
      }
    }
    else {
      $profileFields = CRM_Core_BAO_UFGroup::getFields($ufId, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE,
        NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
      );
      $website = CRM_Core_BAO_Website::getValues($entityBlock, $values);

      foreach ($location as $fld => $values) {
        if (is_array($values) && !empty($values)) {
          $locType = $values[1]['location_type_id'];
          if ($fld == 'email') {
            $elements["onbehalf_{$fld}-{$locType}"] = array(
              'type' => 'Text',
              'value' => $location[$fld][1][$fld],
            );
            unset($profileFields["{$fld}-{$locType}"]);
          }
          elseif ($fld == 'phone') {
            $phoneTypeId = $values[1]['phone_type_id'];
            $elements["onbehalf_{$fld}-{$locType}-{$phoneTypeId}"] = array(
              'type' => 'Text',
              'value' => $location[$fld][1][$fld],
            );
            unset($profileFields["{$fld}-{$locType}-{$phoneTypeId}"]);
          }
          elseif ($fld == 'im') {
            $providerId = $values[1]['provider_id'];
            $elements["onbehalf_{$fld}-{$locType}"] = array(
              'type' => 'Text',
              'value' => $location[$fld][1][$fld],
            );
            $elements["onbehalf_{$fld}-{$locType}provider_id"] = array(
              'type' => 'Select',
              'value' => $location[$fld][1]['provider_id'],
            );
            unset($profileFields["{$fld}-{$locType}-{$providerId}"]);
          }
        }
      }

      if (!empty($website)) {
        foreach ($website as $key => $val) {
          $websiteTypeId = $values[1]['website_type_id'];
          $elements["onbehalf_url-1"] = array(
            'type' => 'Text',
            'value' => $website[1]['url'],
          );
          $elements["onbehalf_url-1-website_type_id"] = array(
            'type' => 'Select',
            'value' => $website[1]['website_type_id'],
          );
          unset($profileFields["url-1"]);
        }
      }

      $locTypeId = isset($location['address'][1]) ? $location['address'][1]['location_type_id'] : NULL;
      $addressFields = array(
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'city',
        'postal_code',
        'country',
        'state_province',
      );

      foreach ($addressFields as $field) {
        if (array_key_exists($field, $addressSequence)) {
          $addField = $field;
          $type = 'Text';
          if (in_array($field, array(
            'state_province', 'country'))) {
            $addField = "{$field}_id";
            $type = 'Select2';
          }
          $elements["onbehalf_{$field}-{$locTypeId}"] = array(
            'fld' => $field,
            'locTypeId' => $locTypeId,
            'type' => $type,
            'value' =>  isset($location['address'][1]) ? $location['address'][1][$addField] : null,
          );
          unset($profileFields["{$field}-{$locTypeId}"]);
        }
      }

      //set custom field defaults
      $defaults = array();
      CRM_Core_BAO_UFGroup::setProfileDefaults($cid, $profileFields, $defaults, TRUE, NULL, NULL, TRUE);

      if (!empty($defaults)) {
        foreach ($profileFields as $key => $val) {

          if (array_key_exists($key, $defaults)) {
            $htmlType = CRM_Utils_Array::value('html_type', $val);
            if ($htmlType == 'Radio') {
              $elements["onbehalf[{$key}]"]['type'] = $htmlType;
              $elements["onbehalf[{$key}]"]['value'] = $defaults[$key];
            }
            elseif ($htmlType == 'CheckBox') {
              foreach ($defaults[$key] as $k => $v) {
                $elements["onbehalf[{$key}][{$k}]"]['type'] = $htmlType;
                $elements["onbehalf[{$key}][{$k}]"]['value'] = $v;
              }
            }
            elseif ($htmlType == 'Multi-Select') {
              foreach ($defaults[$key] as $k => $v) {
                $elements["onbehalf_{$key}"]['type'] = $htmlType;
                $elements["onbehalf_{$key}"]['value'][$k] = $v;
              }
            }
            elseif ($htmlType == 'Autocomplete-Select') {
              $elements["onbehalf_{$key}"]['type'] = $htmlType;
              $elements["onbehalf_{$key}"]['value'] = $defaults[$key];
              $elements["onbehalf_{$key}"]['id'] = $defaults["{$key}_id"];
            }            
            elseif ($htmlType == 'File') {
              $elements["onbehalf_{$key}"]['type'] = $htmlType;
              $elements["onbehalf_{$key}"]['value'] = '';
              $cFid = substr($key, strpos($key, "_") + 1);
              $file = CRM_Core_BAO_CustomField::getFileURL($cid, $cFid, $defaults[$key]);
              $elements["onbehalf_{$key}"]['fileId'] = $file['file_url'];
            }
            elseif ($htmlType == 'Select Date') {
              $elements["onbehalf_{$key}"]['type'] = $htmlType;
              $elements["onbehalf_{$key}"]['value'] = $defaults[$key];
              $elements["onbehalf_{$key}_display"]['value'] = $defaults[$key];
            }
            else {
              $elements["onbehalf_{$key}"]['type'] = $htmlType;
              $elements["onbehalf_{$key}"]['value'] = $defaults[$key];
            }
          }
          else {
            $elements["onbehalf_{$key}"]['value'] = '';
          }
        }
      }
    }
    CRM_Utils_JSON::output($elements);
  }

  static function jqState() {
    CRM_Utils_JSON::output(CRM_Core_BAO_Location::getChainSelectValues($_GET['_value'], 'country'));
  }

  static function jqCounty() {
    CRM_Utils_JSON::output(CRM_Core_BAO_Location::getChainSelectValues($_GET['_value'], 'stateProvince'));
  }

  static function getLocBlock() {
    // i wish i could retrieve loc block info based on loc_block_id,
    // Anyway, lets retrieve an event which has loc_block_id set to 'lbid'.
    if ($_REQUEST['lbid']) {
      $params = array('1' => array($_REQUEST['lbid'], 'Integer'));
      $eventId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_event WHERE loc_block_id=%1 LIMIT 1', $params);
    }
    // now lets use the event-id obtained above, to retrieve loc block information.
    if ($eventId) {
      $params = array('entity_id' => $eventId, 'entity_table' => 'civicrm_event');
      // second parameter is of no use, but since required, lets use the same variable.
      $location = CRM_Core_BAO_Location::getValues($params, $params);
    }

    $result = array();
    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );
    // lets output only required fields.
    foreach ($addressOptions as $element => $isSet) {
      if ($isSet && (!in_array($element, array(
        'im', 'openid')))) {
        if (in_array($element, array(
          'country', 'state_province', 'county'))) {
          $element .= '_id';
        }
        elseif ($element == 'address_name') {
          $element = 'name';
        }
        $fld = "address[1][{$element}]";
        $value = CRM_Utils_Array::value($element, $location['address'][1]);
        $value = $value ? $value : "";
        $result[str_replace(array(
          '][', '[', "]"), array('_', '_', ''), $fld)] = $value;
      }
    }

    foreach (array(
      'email', 'phone_type_id', 'phone') as $element) {
      $block = ($element == 'phone_type_id') ? 'phone' : $element;
      for ($i = 1; $i < 3; $i++) {
        $fld = "{$block}[{$i}][{$element}]";
        $value = CRM_Utils_Array::value($element, $location[$block][$i]);
        $value = $value ? $value : "";
        $result[str_replace(array(
          '][', '[', "]"), array('_', '_', ''), $fld)] = $value;
      }
    }

    // set the message if loc block is being used by more than one event.
    $result['count_loc_used'] = CRM_Event_BAO_Event::countEventsUsingLocBlockId($_REQUEST['lbid']);

    CRM_Utils_JSON::output($result);
  }
}
