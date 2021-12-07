<?php

require_once 'petitionemail.civix.php';
use CRM_Petitionemail_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function petitionemail_civicrm_config(&$config) {
  _petitionemail_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function petitionemail_civicrm_xmlMenu(&$files) {
  _petitionemail_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function petitionemail_civicrm_install() {
  _petitionemail_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function petitionemail_civicrm_postInstall() {
  _petitionemail_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function petitionemail_civicrm_uninstall() {
  _petitionemail_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function petitionemail_civicrm_enable() {
  _petitionemail_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function petitionemail_civicrm_disable() {
  _petitionemail_civix_civicrm_disable();
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
function petitionemail_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _petitionemail_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function petitionemail_civicrm_managed(&$entities) {
  _petitionemail_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function petitionemail_civicrm_angularModules(&$angularModules) {
  _petitionemail_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function petitionemail_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _petitionemail_civix_civicrm_alterSettingsFolders($metaDataFolders);
}


/**
 * Implements hook_civicrm_buildForm().
 */
function petitionemail_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Campaign_Form_Petition_Signature':
      $surveyID = $form->getVar('_surveyId');
      if (!empty($surveyID)) {
        // Find the interface for this petition.
        $class = CRM_Petitionemail_Interface::findInterface($surveyID);
        if ($class === FALSE) {
          return;
        }
        $interface = new $class($surveyID);

        // Make sure all the necessary fields are present.
        if ($interface->isIncomplete) {
          return;
        }

        $interface->buildSigForm($form);
      }
      break;

    case 'CRM_Campaign_Form_Petition':
      $surveyID = $form->getVar('_surveyId');
      if (!empty($surveyID)) {
        // Find the interface for this petition.
        $class = CRM_Petitionemail_Interface::findInterface($surveyID);
        if ($class === FALSE) {
          return;
        }
        $interface = new $class($surveyID);

        // Make sure all the necessary fields are present.
        if ($interface->isIncomplete) {
          return;
        }

        if (method_exists($interface, 'buildFormPetitionConfig')) {
          $interface->buildFormPetitionConfig($form);
        }
      }
      break;
  }
}

/**
 * Implements hook_civicrm_postProcess().
 */
function petitionemail_civicrm_postProcess($formName, &$form) {
  switch ($formName) {
    case 'CRM_Campaign_Form_Petition_Signature':
      $class = CRM_Petitionemail_Interface::findInterface($form->petition['id']);
      if ($class === FALSE) {
        return;
      }
      $interface = new $class($form->petition['id']);
      $interface->processSignature($form);
      break;
  }
}

function petitionemail_civicrm_customPre(string $op, int $groupID, int $entityID, array &$params) {
  $customGroup = \Civi\Api4\CustomGroup::get(FALSE)
    ->addWhere('id', '=', $groupID)
    ->execute()
    ->first();
  if ($customGroup['name'] !== 'Letter_To') {
    return;
  }

  // We hide the actual To,CC,BCC fields and replace them with EntityReference fields.
  // Here we save the values of the EntityReference fields (Email IDs) to the actual custom fields.
  $customFields = \Civi\Api4\CustomField::get(FALSE)
    ->addWhere('custom_group_id:name', '=', 'Letter_To')
    ->execute()
    ->indexBy('id');

  $toEmailID = CRM_Utils_Request::retrieveValue('to_email_id', 'CommaSeparatedIntegers', NULL, FALSE, 'POST');
  $ccEmailID = CRM_Utils_Request::retrieveValue('cc_email_id', 'CommaSeparatedIntegers', NULL, FALSE, 'POST');
  $bccEmailID = CRM_Utils_Request::retrieveValue('bcc_email_id', 'CommaSeparatedIntegers', NULL, FALSE, 'POST');
  foreach ($params as &$customFieldParams) {
    if (isset($customFields[$customFieldParams['custom_field_id']])) {
      $customField = $customFields[$customFieldParams['custom_field_id']];
      switch ($customField['name']) {
        case 'To':
          if (!empty($toEmailID)) {
            $customFieldParams['value'] = $toEmailID;
          }
          break;

        case 'CC':
          if (!empty($ccEmailID)) {
            $customFieldParams['value'] = $ccEmailID;
          }
          break;

        case 'BCC':
          if (!empty($bccEmailID)) {
            $customFieldParams['value'] = $bccEmailID;
          }
          break;
      }
    }
  }
}

