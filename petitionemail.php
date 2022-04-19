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

function petitionemail_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    ['civi.token.list', ['\Civi\Petitionemail\Tokens', 'register']]
  );
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    ['civi.token.eval', ['\Civi\Petitionemail\Tokens', 'evaluate']]
  );
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
      $surveyId = $form->getVar('_surveyId');
      if (!empty($surveyId)) {
        if (!empty($_COOKIE['signed_' . $surveyId])) {
          // This petition has been signed, we should bail.
          return;
        }
        // Find the interface for this petition.
        $class = CRM_Petitionemail_Interface::findInterface($surveyId);
        if ($class === FALSE) {
          return;
        }
        $interface = new $class($surveyId);

        // Make sure all the necessary fields are present.
        if (!$interface->isComplete) {
          return;
        }

        $interface->addMessageAndSubjectToSigForm($form);
        $interface->buildSigForm($form);
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

/**
 * Implements hook_civicrm_fieldOptions().
 *
 * Add all the groups listed in allowedgroups_for_eventdedupe to the duplicate_if_in_groups custom field
 * which is used to select a group that is used in hook_civicrm_findDuplicates to choose whether to duplicate
 * or merge contact on event registration.
 */
function petitionemail_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if ($entity == 'Survey') {

    // Check if this is the customField that specifies the Message Template ID
    $customFieldID = \Civi\Api4\CustomField::get(FALSE)
      ->addSelect('id')
      ->addWhere('custom_group_id:name', '=', 'Letter_To')
      ->addWhere('name', '=', 'MessageTemplate')
      ->execute()
      ->first()['id'] ?? NULL;
    if (empty($customFieldID) || ($field !== "custom_{$customFieldID}")) {
      // Not the MessageTemplate field (or field does not exist)
      return;
    }

    // Add a filtered select list to replace the standard select template field
    $messageTemplates = \Civi\Api4\MessageTemplate::get(FALSE)
      ->addWhere('workflow_name', 'IS NULL')
      ->addWhere('is_sms', '=', FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('msg_title', 'ASC');

    $templates = $messageTemplates->execute()->indexBy('id');
    $listOfTemplates = [];
    foreach ($templates as $templateID => $templateDetail) {
      $listOfTemplates[$templateID] = $templateDetail['msg_title'];
    }

    $options = $listOfTemplates;
  }
}
