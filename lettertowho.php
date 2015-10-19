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
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function lettertowho_civicrm_preProcess($formName, &$form) {

}
 */

function getCustomGroup() {
  if (empty(static::$customGroup)) {
    $params = array(
      'extends' => 'Survey',
      'is_active' => 1,
      'name' => static::letter_to,
      'return' => array('id', 'table_name'),
    );
    static::$customGroup = civicrm_api3('CustomGroup', 'getsingle', $params);
    unset(static::$customGroup['extends']);
    unset(static::$customGroup['is_active']);
    unset(static::$customGroup['name']);
  }
  return static::$customGroup;
}

function getCustomFields() {
  if (empty(static::$customFields)) {
    $custom_group = static::getCustomGroup();
    $params = array(
      'custom_group_id' => $custom_group['id'],
      'is_active' => 1,
      'return' => array('id', 'column_name', 'name', 'data_type'),
    );
    $fields = civicrm_api3('CustomField', 'get', $params);
    if (CRM_Utils_Array::value('count', $fields) < 1) {
      CRM_Core_Error::fatal('Custom fields appear to be missing (custom field group' . static::letter_to . ').');
    }
    foreach ($fields['values'] as $field) {
      static::$customFields[strtolower($field['name'])] = array(
        'id' => $field['id'],
        'column_name' => $field['column_name'],
        'custom_n' => 'custom_' . $field['id'],
        'data_type' => $field['data_type'],
      );
    }
  }
  return static::$customFields;
}

function civicrm_petition_email_civicrm_config(&$config) {
  // Include our templates directory.
  $template_dir = dirname(__FILE__) .
    DIRECTORY_SEPARATOR . 'templates';

  $template =& CRM_Core_Smarty::singleton();
  array_unshift($template->template_dir, $template_dir);
}
function civicrm_petition_email_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Campaign_Form_Petition_Signature') {
    $survey_id = $form->getVar('_surveyId');
    if ($survey_id) {
      // TODO: change from database query to API call
      // $petitionemailval = db_query('SELECT petition_id, recipient_email, recipient_name, default_message, message_field, subject FROM {civicrm_petition_email} WHERE petition_id = :survey_id', array(':survey_id' => $survey_id));
      $petitionemailval = civicrm_api('Survey', 'get', array(
        'version' => '3',
        'sequential' => 1,
      ));
      foreach ($petitionemailval as $petitioninfo) {
        $defaults = $form->getVar('_defaults');
        $messagefield = 'custom_' . $petitioninfo->message_field;
        foreach ($form->_elements as $element) {
          if ($element->_attributes['name'] == $messagefield) {
            $element->_value = $petitioninfo->default_message;
          }
        }
        $defaults[$messagefield] = $form->_defaultValues[$messagefield] = $petitioninfo->default_message;
        $form->setVar('_defaults', $defaults);
        break;
      }
    }
  }

  if ($formName != 'CRM_Campaign_Form_Petition') {
    return;
  }
  $survey_id = $form->getVar('_surveyId');
  if ($survey_id) {
    // TODO: Change from database query to API call
    // $petitionemailval = db_query('SELECT petition_id, recipient_email, recipient_name, default_message, message_field, subject FROM {civicrm_petition_email} WHERE petition_id = :survey_id', array(':survey_id' => $survey_id));
    $petitionemailval = civicrm_api('Survey', 'get', array(
      'version' => '3',
      'sequential' => 1,
    ));
    foreach ($petitionemailval as $petitioninfo) {
      $form->_defaultValues['email_petition'] = 1;
      $form->_defaultValues['recipient_name'] = $petitioninfo->recipient_name;
      $form->_defaultValues['recipient'] = $petitioninfo->recipient_email;
      $form->_defaultValues['default_message'] = $petitioninfo->default_message;
      $form->_defaultValues['user_message'] = $petitioninfo->message_field;
      $form->_defaultValues['subjectline'] = $petitioninfo->subject;
      break;
    }
  }
  $form->add('checkbox', 'email_petition', ts('Send an email to a target'));
  $form->add('text', 'recipient_name', ts('Recipient\'s Name'));
  $form->add('text', 'recipient', ts('Recipient\'s Email'));
  $validcustomgroups = array();

  // Get the Profiles in use by this petition so we can find out
  // if there are any potential fields for an extra message to the
  // petition target.
  $params = array('version' => '3', 'module' => 'CiviCampaign', 'entity_table' => 'civicrm_survey', 'entity_id' => $survey_id);
  $join_results = civicrm_api('UFJoin', 'get', $params);
  $custom_fields = array();
  if ($join_results['is_error'] == 0) {
    foreach ($join_results['values'] as $join_value) {
      $uf_group_id = $join_value['uf_group_id'];

      // Now get all fields in this profile
      $params = array('version' => 3, 'uf_group_id' => $uf_group_id);
      $field_results = civicrm_api('UFField', 'get', $params);
      if ($field_results['is_error'] == 0) {
        foreach ($field_results['values'] as $field_value) {
          $field_name = $field_value['field_name'];
          // TODO: since on install this field will be created, have it look for that field specifically
          if (!preg_match('/^custom_[0-9]+/', $field_name)) {
            // We only know how to lookup field types for custom
            // fields. Skip core fields.
            continue;
          }

          $id = substr(strrchr($field_name, '_'), 1);
          // Finally, see if this is a text or textarea field.
          $params = array('version' => 3, 'id' => $id);
          $custom_results = civicrm_api('CustomField', 'get', $params);
          if ($custom_results['is_error'] == 0) {
            $field_value = array_pop($custom_results['values']);
            $html_type = $field_value['html_type'];
            $label = $field_value['label'];
            $id = $field_value['id'];
            if ($html_type == 'Text' || $html_type == 'TextArea') {
              $custom_fields[$id] = $label;
            }
          }
        }
      }
    }
  }
  $custom_message_field_options = array();
  if (count($custom_fields) == 0) {
    $custom_message_field_options = array('' => t('- No Text or TextArea fields defined in your profiles -'));
  }
  else {
    $custom_message_field_options = array('' => t('- Select -'));
    $custom_message_field_options = $custom_message_field_options + $custom_fields;
  }
  $form->add('select', 'user_message', ts('Custom Message Field'), $custom_message_field_options);
  $form->add('textarea', 'default_message', ts('Default Message'));
  $form->add('text', 'subjectline', ts('Email Subject Line'));
}

function civicrm_petition_email_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  if ($tplName == 'CRM/Campaign/Form/Petition.tpl') {
    $rendererval = $object->getVar('_renderer');
    $action = $object->getVar('_action');
    if ($action == 8) {
      return;
    }

    //insert the field before is_active
    //
    // TODO: Move mark up to template and use smarty to grab fields
    $insertpoint = strpos($content, '<tr class="crm-campaign-survey-form-block-is_active">');

    $help_code = "<a class=\"helpicon\" onclick=\"CRM.help('Petition Email', {'id':'id-email-petition','file':'CRM\/Campaign\/Form\/Petition'}); return false;\" href=\"#\" title=\"Petition Email Help\"></a>";
    $content1 = substr($content, 0, $insertpoint);
    $content3 = substr($content, $insertpoint);
    // TODO: Moved content2 to petition_email_form.html - replace vars with smarty, put something here to link it back
    $content2 = '<tr class="crm-campaign-survey-form-block-email_petition">
      <td class="label">'.{ $rendererval->_tpl->_tpl_vars['form']['email_petition']['label']} . $help_code . '</td>
      <td>'.$rendererval->_tpl->_tpl_vars['form']['email_petition']['html'].'
        <div class="description">'.ts('Should signatures generate an email to the petition\'s  target?') .'</div>
      </td>
    </tr>
    <tr class="crm-campaign-survey-form-block-recipient_name">
    <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['recipient_name']['label'] .  '</td>
      <td>'.$rendererval->_tpl->_tpl_vars['form']['recipient_name']['html'].'
        <div class="description">'.ts('Enter the target\'s name (as he or she should see it) here.').'</div>
      </td>
    </tr>
    <tr class="crm-campaign-survey-form-block-recipient">
      <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['recipient']['label'] .'</td>
      <td>'.$rendererval->_tpl->_tpl_vars['form']['recipient']['html'].'
        <div class="description">'.ts('Enter the target\'s email address here.').'</div>
      </td>
    </tr>
    <tr class="crm-campaign-survey-form-block-user_message">
      <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['user_message']['label'] .'</td>
      <td>'.$rendererval->_tpl->_tpl_vars['form']['user_message']['html'].'
        <div class="description">'.ts('Select a field that will have the signer\'s custom message.  Make sure it is included in the Activity Profile you selected.').'</div>
      </td>
    </tr>
    <tr class="crm-campaign-survey-form-block-default_message">
      <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['default_message']['label'] .'</td>
      <td>'.$rendererval->_tpl->_tpl_vars['form']['default_message']['html'].'
        <div class="description">'.ts('Enter the default message to be included in the email.').'</div>
      </td>
    </tr>
    <tr class="crm-campaign-survey-form-block-subjectline">
      <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['subjectline']['label'] .'</td>
      <td>'.$rendererval->_tpl->_tpl_vars['form']['subjectline']['html'].'
        <div class="description">'.ts('Enter the subject line to be included in the email.').'</div>
      </td>
    </tr>';

    // jQuery to show/hide the email fields
    // TODO: Moved JS to petition_email_form.js, need to find a wa to link it back here
    $content4 = '';

    $content = $content1 . $content2 . $content3 . $content4;
  }
}

/**
 * TODO: change from using table to making an API call
 */
function civicrm_petition_email_civicrm_postProcess($formName, &$form) {
  if ($formName != 'CRM_Campaign_Form_Petition') {
    return;
  }
  if ($form->_submitValues['email_petition'] == 1) {
    require_once 'api/api.php';
    $survey_id = $form->getVar('_surveyId');
    $lastmoddate = 0;
    if (!$survey_id) {// Ugly hack because the form doesn't return the id
      $surveys = civicrm_api("Survey", "get", array('version' => '3', 'sequential' => '1', 'title' => $form->_submitValues['title']));
      if (is_array($surveys['values'])) {
        foreach ($surveys['values'] as $survey) {
          if ($lastmoddate > strtotime($survey['last_modified_date'])) {
            continue;
          }
          $lastmoddate = strtotime($survey['last_modified_date']);
          $survey_id = $survey['id'];
        }
      }
    }
    if (!$survey_id) {
      CRM_Core_Session::setStatus(ts('Cannot find the petition for saving email delivery fields.'));
      return;
    }

    // TODO: Change from DB INSERT into API create
    $checkexisting = db_query("SELECT count(*) AS count FROM {civicrm_petition_email} WHERE petition_id = :survey_id", array(':survey_id' => $survey_id));
    $row = $checkexisting->fetchAssoc();
    if ($row['count'] == 0) {
      $insert = db_query(
        "INSERT INTO {civicrm_petition_email} (petition_id, recipient_email, recipient_name, default_message, message_field, subject) VALUES ( :survey_id, :recipient, :recipient_name, :default_message, :user_message, :subjectline )", array(
          ':survey_id' => $survey_id,
          ':recipient' => $form->_submitValues['recipient'],
          ':recipient_name' => $form->_submitValues['recipient_name'],
          ':default_message' => $form->_submitValues['default_message'],
          ':user_message' => intval($form->_submitValues['user_message']),
          ':subjectline' => $form->_submitValues['subjectline'],
        )
      );
    }
    else {
      $insert = db_query(
        "UPDATE {civicrm_petition_email} SET recipient_email = :recipient, recipient_name = :recipient_name, default_message = :default_message, message_field = :message_field, subject = :subject WHERE petition_id = :survey_id", array(
          ':recipient' => $form->_submitValues['recipient'],
          ':recipient_name' => $form->_submitValues['recipient_name'],
          ':default_message' => $form->_submitValues['default_message'],
          ':message_field' => intval($form->_submitValues['user_message']),
          ':subject' => $form->_submitValues['subjectline'],
          ':survey_id' => $survey_id,
        )
      );
    }
    if (!$insert) {
      CRM_Core_Session::setStatus(ts('Could not save petition delivery information.'));
    }
  }
}

function civicrm_petition_email_civicrm_post($op, $objectName, $objectId, &$objectRef) {
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
      $sql = 'SELECT petition_id, recipient_email, recipient_name, default_message, message_field, subject FROM {civicrm_petition_email} WHERE petition_id = :survey_id';
      $params = array(':survey_id' => $survey_id);
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
        $petition_message = $petition['default_message'];
      }
      $to = '"' . $petition['recipient_name'] . '" <' . $petition['recipient_email'] . '>';

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
      // TODO: change from drupal mail to Civi mail
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

  // TODO: probably not needed, can use survey get
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
