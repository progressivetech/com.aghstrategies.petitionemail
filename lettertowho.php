<?php

require_once 'lettertowho.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function lettertowho_civicrm_config(&$config) {
  _lettertowho_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function lettertowho_civicrm_xmlMenu(&$files) {
  _lettertowho_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function lettertowho_civicrm_install() {
  _lettertowho_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function lettertowho_civicrm_uninstall() {
  _lettertowho_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function lettertowho_civicrm_enable() {
  _lettertowho_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function lettertowho_civicrm_disable() {
  _lettertowho_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function lettertowho_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _lettertowho_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function lettertowho_civicrm_managed(&$entities) {
  _lettertowho_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function lettertowho_civicrm_caseTypes(&$caseTypes) {
  _lettertowho_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function lettertowho_civicrm_angularModules(&$angularModules) {
_lettertowho_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function lettertowho_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _lettertowho_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Find the custom fields.
 *
 * @param string $field
 *   The field you're looking for.
 *
 * @return string
 *   The field name for API purposes like "custom_123".
 */
function lettertowho_findField($field = NULL) {
  try {
    $fieldParams = array(
      'custom_group_id' => "letter_to",
      'sequential' => 1,
    );
    if ($field) {
      $fieldParams['name'] = $field;
    }
    $result = civicrm_api3('CustomField', 'get', $fieldParams);
    if (!empty($result['values'][0]) && $field) {
      return "custom_{$result['values'][0]['id']}";
    }
    elseif (!empty($result['values'])) {
      $fields = array();
      foreach ($result['values'] as $f) {
        $fields[$f['name']] = "custom_{$f['id']}";
      }
      return $fields;
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
  }
}

function civicrm_petition_email_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Campaign_Form_Petition_Signature':
      $survey_id = $form->getVar('_surveyId');
      if (!empty($survey_id)) {
        list($fields, $petitionemailval) = lettertowho_getFieldsData($survey_id);

        // If somehow the survey custom fields weren't found:
        if (empty($fields['Message_Field']) || empty($fields['Default_Message'])) {
          return;
        }

        $defaults = $form->getVar('_defaults');
        foreach ($form->_elements as $element) {
          if ($element->_attributes['name'] == $fields['Message_Field']) {
            $element->_value = CRM_Utils_Array::value($fields['Default_Message'], $petitionemailval);
          }
        }
        $defaults[$messagefield] = $form->_defaultValues[$messagefield] = CRM_Utils_Array::value($fields['Default_Message'], $petitionemailval);
        $form->setVar('_defaults', $defaults);
      }
      break;

    case 'CRM_Campaign_Form_Petition':
      // TODO: add js for picking message field.
  }
}

/**
 * Implements hook_civicrm_post().
 */
function lettertowho_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  static $profile_fields = NULL;
  if ($objectName == 'Profile' && is_array($objectRef)) {
    // This seems like broad criteria to be hanging on to a static array, however,
    // not sure how else to capture the input to be used in case this is a petition
    // being signed that has a target. If you are anonymous, you have a source field in the
    // array, but that is not there if you are logged in. Sigh.
    $profile_fields = $objectRef;
  }

  // TODO: change from table to API
  if ($op == 'create' && $objectName == 'Activity') {
    require_once 'api/api.php';
    $petitiontype = civicrm_petition_email_get_petition_type();
    if ($objectRef->activity_type_id == $petitiontype) {
      $survey_id = $objectRef->source_record_id;
      $activity_id = $objectRef->id;
      global $language;
      // TODO: SQL to API, here's old SQL, not sure if i got the API call correct -NM
      // $sql = 'SELECT petition_id, recipient_email, recipient_name, default_message, message_field, subject FROM {civicrm_petition_email} WHERE petition_id = :survey_id';
      $sql = civicrm_api('Survey', 'get', array(
            'version' => '3',
            'sequential' => 1,
            "custom_subject" => "",
            "custom_default_message" => "",
            "custom_recipient_name" => '',
            "custom_recipient_email" => "",
            "petition_id" => "",
            "survey_id" => "",
          ));
      $params = array(':survey_id' => $survey_id);
      // TODO: Not sure how to proceed here, I'm a little confused by what this is supposed to be doing in the DB and not sure how to re-create it with API call -NM
      $result = db_query($sql, $params);
      $petition = $result->fetchAssoc();
      if (empty($petition) || !array_key_exists('petition_id', $petition) || empty($petition['petition_id'])) {
        // Must not be a petition with a target.
        return;
      }

      // Set up variables for the email message
      // Figure out whether to use the user-supplied message or the default message
      $petition_message = NULL;
      // If the petition has specified a message field, and we've encountered the profile post action....
      if (!empty($petition['message_field']) && !is_null($profile_fields)) {
        if (is_numeric($petition['message_field'])) {
          $message_field = 'custom_' . $petition['message_field'];
        }
        else {
          $message_field = $petition['message_field'];
        }
        // If the field is in the profile
        if (array_key_exists($message_field, $profile_fields)) {
          // If it's not empty...
          if (!empty($profile_fields[$message_field])) {
            $petition_message = $profile_fields[$message_field];
          }
        }
      }
      // No user supplied message, use the default
      if (is_null($petition_message)) {
        $petition_message = $petition['custom_default_message'];
      }
      $to = '"' . $petition['custom_recipient_name'] . '" <' . $petition['custom_recipient_email'] . '>';

      // Figure out the user id that created this activity so we can set the from address
      $from = NULL;

      // Get the record_type_id for Source Activity records.
      $activity_contacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $source_id   = CRM_Utils_Array::key('Activity Source', $activity_contacts);

      // Get the source contact for this activity
      $params = array('activity_id' => $objectRef->id, 'record_type_id' => $source_id);
      try{
        $value = civicrm_api3("Contact", "getsingle", $params);
        $from = $value['display_name'] . ' <' . $value['email'] . '>';
      }
      catch (CiviCRM_API3_Exception $e) {
        // If there's an error, use default values for from address
        $domain = civicrm_api("Domain", "get", array('version' => '3', 'sequential' => '1'));
        if ($domain['is_error'] != 0 || !is_array($domain['values'])) {
          // Can't send email without a from address.
          return;
        }
        $value = array_pop($domain['values']);
        $from = '"' . $value['from_name'] . '"' . ' <' . $value['from_email'] . '>';
      }

      // Setup email message
      // TODO: change from drupal mail to Civi mail -NM
      $params = array(
        'subject' => $petition['subject'],
        'message' => $petition_message,
      );
      $success = drupal_mail('civicrm_petition_email', 'signature', $to, $language, $params, $from);
      if ($success['result']) {
        CRM_Core_Session::setStatus(ts('Message sent successfully to') . " $to");
      }
      else {
        CRM_Core_Session::setStatus(ts('Error sending message to') . " $to");
      }
    }
  }
}

function civicrm_petition_email_mail($key, &$message, $params) {
  $message['subject'] = $params['subject'];
  $message['body'][] = $params['message'];
}

function civicrm_petition_email_get_petition_type() {
  $petitiontype = variable_get('civicrm_petition_email_petitiontype', FALSE);

  // TODO: probably not needed, can use survey get -NM
  if (!$petitiontype) {// Go figure out and set the activity type id
    $acttypegroup = civicrm_api("OptionGroup", "getsingle", array('version' => '3', 'sequential' => '1', 'name' => 'activity_type'));
    if ($acttypegroup['id'] && !$acttypegroup['is_error']) {
      $acttype = civicrm_api("OptionValue", "getsingle", array('version' => '3', 'sequential' => '1', 'option_group_id' => $acttypegroup['id'], 'name' => 'Petition'));
      if ($acttype['id'] && !$acttype['is_error']) {
        $petitiontype = $acttype['value'];
        variable_set('civicrm_petition_email_petitiontype', $acttype['value']);
      }
    }
  }

  return $petitiontype;
}

/**
 * Get the fields and survey data.
 *
 * @param int $survey_id
 *   The ID of the petition.
 *
 * @return array
 *   The fields array and the survey info.
 */
function lettertowho_getFieldsData($survey_id) {
  // Get the field IDs for the standard fields:
  $fields = lettertowho_findField();

  // If somehow the survey custom fields weren't found:
  if (empty($fields['Message_Field']) || empty($fields['Default_Message'])) {
    return array($fields);
  }

  try {
    $surveyParams = array(
      'id' => $survey_id,
      'return' => array_values($fields),
    );
    $petitionemailval = civicrm_api3('Survey', 'getsingle', $surveyParams);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
    return array($fields);
  }
  return array(
    $fields,
    $petitionemailval,
  );
}
