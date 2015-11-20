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

/**
 * Implements hook_civicrm_buildForm().
 */
function lettertowho_civicrm_buildForm($formName, &$form) {
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
        $defaults[$fields['Message_Field']] = $form->_defaultValues[$fields['Message_Field']] = CRM_Utils_Array::value($fields['Default_Message'], $petitionemailval);
        $form->setVar('_defaults', $defaults);
      }
      break;

    case 'CRM_Campaign_Form_Petition':
      // TODO: add js for picking message field.
      // TODO: make sure it shows survey custom fields.
  }
}

/**
 * Implements hook_civicrm_post().
 *
 * TODO: Make sure custom fields are saved at this point: it may be that this
 * needs to be attached to the postProcess.
 */
function lettertowho_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' && $objectName == 'Activity') {
    try {
      $petitionTypeParams = array(
        'name' => "activity_type",
        'api.OptionValue.getsingle' => array(
          'option_group_id' => '$value.id',
          'name' => "Petition",
          'options' => array('limit' => 1),
        ),
        'options' => array('limit' => 1),
      );
      $petitionTypeInfo = civicrm_api3('OptionGroup', 'getsingle', $petitionTypeParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
    }

    if (empty($petitionTypeInfo['api.OptionValue.getsingle']['value'])
      || $objectRef->activity_type_id != $petitionTypeInfo['api.OptionValue.getsingle']['value']) {
      return;
    }

    $survey_id = $objectRef->source_record_id;
    $activity_id = $objectRef->id;
    list($fields, $petitionemailval) = lettertowho_getFieldsData($survey_id);

    $neededFields = array(
      'Sends_Email',
      'Subject',
      'Recipient_Name',
      'Recipient_Email',
      'Default_Message',
      'Message_Field',
    );

    foreach ($neededFields as $neededField) {
      if (empty($neededField) || empty($petitionemailval[$fields[$neededField]])) {
        return;
      }
    }

    $messageField = 'custom_' . intval($petitionemailval[$fields['Message_Field']]);

    // Get custom message value.
    try {
      $sourceTypeParams = array(
        'name' => "activity_contacts",
        'options' => array('limit' => 1),
        'api.OptionValue.getsingle' => array(
          'option_group_id' => '$value.id',
          'name' => "Activity Source",
          'options' => array('limit' => 1),
        ),
      );
      $sourceTypeInfo = civicrm_api3('OptionGroup', 'getsingle', $sourceTypeParams);

      $sourceRecordType = empty($sourceTypeInfo['api.OptionValue.getsingle']['value']) ? 2 : $sourceTypeInfo['api.OptionValue.getsingle']['value'];

      $signatureParams = array(
        'id' => $activity_id,
        'api.ActivityContact.getsingle' => array(
          'record_type_id' => $sourceRecordType,
          'options' => array('limit' => 1),
          'api.Contact.getsingle' => array(
            'return' => array(
              'display_name',
              'email',
            ),
          ),
        ),
        'return' => array(
          $messageField,
        ),
      );
      $signature = civicrm_api3('Activity', 'getsingle', $signatureParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
    }

    $petitionMessage = empty($signature[$messageField]) ? CRM_Utils_Array::value($fields['Default_Message'], $petitionemailval) : $signature[$messageField];

    if (empty($signature['api.ActivityContact.getsingle']['api.Contact.getsingle']['email'])) {
      // TODO: Get default from address.
      $defaultMailParams = array(
        'name' => "from_email_address",
        'options' => array('limit' => 1),
        'api.OptionValue.getsingle' => array(
          'is_default' => 1,
          'options' => array('limit' => 1),
        ),
      );
      $defaultMail = civicrm_api3('OptionGroup', 'getsingle', $defaultMailParams);
      if (empty($defaultMail['api.OptionValue.getsingle']['label'])
        || $defaultMail['api.OptionValue.getsingle']['label'] == $defaultMail['api.OptionValue.getsingle']['name']) {
        // No site email.
        // TODO: leave some kind of message with explanation.
        return;
      }
      $from = $defaultMail['api.OptionValue.getsingle']['label'];
    }
    elseif (empty($signature['api.ActivityContact.getsingle']['api.Contact.getsingle']['display_name'])) {
      $from = $signature['api.ActivityContact.getsingle']['api.Contact.getsingle']['email'];
    }
    else {
      $from = "\"{$signature['api.ActivityContact.getsingle']['api.Contact.getsingle']['display_name']}\" <{$signature['api.ActivityContact.getsingle']['api.Contact.getsingle']['email']}>";
    }

    // Setup email message:
    $mailParams = array(
      'groupName' => 'Activity Email Sender',
      'from' => $from,
      'toName' => $petitionemailval[$fields['Recipient_Name']],
      'toEmail' => $petitionemailval[$fields['Recipient_Email']],
      'subject' => $petitionemailval[$fields['Subject']],
      // 'cc' => $cc, TODO: offer option to CC.
      // 'bcc' => $bcc,
      'text' => $petitionMessage,
      // 'html' => $html_message, TODO: offer HTML option.
    );

    if (!CRM_Utils_Mail::send($mailParams)) {
      CRM_Core_Session::setStatus(ts('Error sending message to %1', array('domain' => 'com.aghstrategies.lettertowho', 1 => $mailParams['toName'])));
    }
    else {
      CRM_Core_Session::setStatus(ts('Message sent successfully to %1', array('domain' => 'com.aghstrategies.lettertowho', 1 => $mailParams['toName'])));
    }
  }
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
