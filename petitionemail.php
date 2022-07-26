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
    case 'CRM_Campaign_Form_Petition':
      CRM_Core_Resources::singleton()->addScriptFile('com.aghstrategies.petitionemail', 'js/addressField.js');
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
 *
 * Implements hook_civicrm_fieldOptions().
 *
 * We alter field options in two cases:
 *
 * 1. We dynamically display available templates to be used for sending the
 * target email. 
 *
 * 2. We dynamically display the available recipient systems depending on
 * which extensions are installed.
 *
 */
function petitionemail_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if ($entity == 'Survey') {

    // Get the field ids we are looking for.
    $messageTemplateFieldId = \Civi\Api4\CustomField::get(FALSE)
      ->addSelect('id')
      ->addWhere('custom_group_id:name', '=', 'Letter_To')
      ->addWhere('name', '=', 'MessageTemplate')
      ->execute()
      ->first()['id'] ?? NULL;

    $recipientSystemFieldId = \Civi\Api4\CustomField::get(FALSE)
      ->addSelect('id')
      ->addWhere('custom_group_id:name', '=', 'Letter_To')
      ->addWhere('name', '=', 'Recipient_System')
      ->execute()
      ->first()['id'] ?? NULL;

    // Check if this is the customField that specifies the Message Template ID
    if ($field == "custom_{$messageTemplateFieldId}") {
      // Add a filtered select list to replace the standard select template field
      $messageTemplates = \Civi\Api4\MessageTemplate::get(FALSE)
        ->addWhere('workflow_name', 'IS NULL')
        ->addWhere('is_sms', '=', FALSE)
        ->addWhere('is_active', '=', TRUE)
        ->addWhere('msg_html', 'LIKE', '%{petitionemail.message}%')
        ->addOrderBy('msg_title', 'ASC');

      $templates = $messageTemplates->execute()->indexBy('id');
      $listOfTemplates = [];
      foreach ($templates as $templateID => $templateDetail) {
        $listOfTemplates[$templateID] = $templateDetail['msg_title'];
      }

      $options = $listOfTemplates;
    }

    // Check if this is the email recipient system field.
    if ($field == "custom_{$recipientSystemFieldId}") {
      // We only manipulate the options if the electoral extension is
      // installed and is the right version.
      
      $electoralExt = \Civi\Api4\Extension::get()
        ->addWhere('key', '=', 'com.jlacey.electoral')
        ->addWhere('version', '>=', 3)
        ->addWhere('status', '=', 'installed')
        ->execute();
      if ($electoralExt->count() > 0) {
        // It is installed, let's see which backends are enabled.
        $enabledProviders = \Civi::settings()->get('electoralApiProviders');
        foreach ($enabledProviders as $k => $provider) {
          $name = \Civi\Api4\OptionValue::get(FALSE)
            ->addSelect('name')
            ->addWhere('option_group_id:name', '=', 'electoral_api_data_providers')
            ->addWhere('value', '=', $provider)
            ->execute()
            ->column('name')[0];

          if ($name == '\Civi\Electoral\Api\Openstates') {
            $options['OpenstatesUpper'] = 'Open States: Upper (State)';
            $options['OpenstatesLower'] = 'Open States: Lower (Sttae)';
            $options['OpenstatesBoth'] = 'Open States: Upper and Lower (State)';
          }
          elseif ($name == '\Civi\Electoral\Api\GoogleCivicInformation') {
            $options['GoogleUpper'] = 'Google Civic: Upper (State)';
            $options['GoogleLower'] = 'Google Civic: Lower (Sttae)';
            $options['GoogleBoth'] = 'Google Civic: Upper and Lower (State)';
            $options['GoogleCity'] = 'Google Civic: City Council';

          } 
          elseif ($name == '\Civi\Electoral\Api\Cicero') {
            $options['CiceroUpper'] = 'Cicero: Upper (State)';
            $options['CiceroLower'] = 'Cicero: Lower (Sttae)';
            $options['CiceroBoth'] = 'Cicero: Upper and Lower (State)';
            $options['CiceroCity'] = 'Cicero: City Council';
          }
        }
      }
    }
  }
}
