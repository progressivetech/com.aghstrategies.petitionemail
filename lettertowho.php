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
 * Implements hook_civicrm_buildForm().
 */
function lettertowho_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Campaign_Form_Petition_Signature':
      $survey_id = $form->getVar('_surveyId');
      if (!empty($survey_id)) {
        // Find the interface for this petition.
        $class = CRM_Lettertowho_Interface::findInterface($survey_id);
        if ($class === FALSE) {
          return;
        }
        $interface = new $class($survey_id);

        // Make sure all the necessary fields are present.
        if ($interface->isIncomplete) {
          return;
        }

        $interface->buildSigForm($form);
      }
      break;

    case 'CRM_Campaign_Form_Petition':
      // TODO: add js for picking message field.
      CRM_Core_Resources::singleton()->addScriptFile('com.aghstrategies.lettertowho', 'js/messageField.js');
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
    // First, check that the activity is a petition signature.
    $petitionActivityType = CRM_Lettertowho_Utils::getPetitionActivityType();

    if ($objectRef->activity_type_id != $petitionActivityType) {
      return;
    }

    // Find the interface for this petition.
    $class = CRM_Lettertowho_Interface::findInterface($objectRef->source_record_id);
    if ($class === FALSE) {
      return;
    }
    $interface = new $class($objectRef->source_record_id);
    $interface->processSignature($objectRef->id);
  }
}
