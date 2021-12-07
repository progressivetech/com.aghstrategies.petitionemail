<?php
/**
 * @file
 * Provide basics for petition delivery interfaces.
 */

use CRM_Petitionemail_ExtensionUtil as E;

/**
 * The base class for petition delivery interfaces.
 */
class CRM_Petitionemail_Interface {

  /**
   * Value of the record_type_id for activity source contacts.
   *
   * @type int
   */
  protected $sourceRecordType = NULL;

  /**
   * Fields in extension's custom data set.
   *
   * @type array
   */
  protected $fields = [];

  /**
   * The default "from" address for the site.
   *
   * @type string
   */
  protected $defaultFromAddress = NULL;

  /**
   * The values for the given survey.
   *
   * @type array
   */
  protected $petitionEmailVal = [];

  /**
   * The fields that are required to run a signature of this type.
   *
   * @var array
   */
  protected $neededFields = [];

  /**
   * The ID of the petition using this interface.
   *
   * @type int
   */
  public $surveyId = NULL;

  /**
   * A flag that the system doesn't have the fields for this interface to work.
   *
   * @type boolean
   */
  public $isIncomplete = TRUE;

  public function __construct($surveyId) {
    $this->surveyId = $surveyId;
    $this->findFields();
    $this->getFieldsData();
  }

  /**
   * Find the custom fields.
   *
   * @return array
   */
  public function findFields() {
    if (empty($this->fields)) {
      $customFields = \Civi\Api4\CustomField::get(FALSE)
        ->addWhere('custom_group_id:name', '=', 'Letter_To')
        ->execute();

      foreach ($customFields as $customField) {
        $this->fields[$customField['name']] = "Letter_To.{$customField['name']}";
      }
    }
    return $this->fields;
  }

  /**
   * Get the survey data.
   *
   * @return array
   *   The survey info.
   */
  public function getFieldsData() {
    $this->petitionEmailVal = \Civi\Api4\Survey::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $this->surveyId)
      ->execute()
      ->first();
    return $this->petitionEmailVal;
  }

  /**
   * Prepare the petition signature form.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The form.
   */
  public function buildSigForm($form) {}

  /**
   * @param CRM_Campaign_Form_Petition $form
   *
   */
  public function buildFormPetitionConfig($form) {}

  /**
   * Send the emails
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *
   */
  public function processSignature($form) {}

  /**
   * Get the value for the record_type_id for an activity source.
   *
   * @return int
   *   The source activityContact record ID.
   */
  public function getSourceRecordType() {
    if (empty($this->sourceRecordType)) {
      $cache = CRM_Utils_Cache::singleton();
      $this->sourceRecordType = $cache->get('petitionemail_sourceRecordType');
    }
    if (empty($this->sourceRecordType)) {
      try {
        $sourceTypeParams = [
          'name' => "activity_contacts",
          'options' => ['limit' => 1],
          'api.OptionValue.getsingle' => [
            'option_group_id' => '$value.id',
            'name' => "Activity Source",
            'options' => ['limit' => 1],
          ],
        ];
        $sourceTypeInfo = civicrm_api3('OptionGroup', 'getsingle', $sourceTypeParams);

        if (empty($sourceTypeInfo['api.OptionValue.getsingle']['value'])) {
          $this->sourceRecordType = 2;
        }
        else {
          $this->sourceRecordType = $sourceTypeInfo['api.OptionValue.getsingle']['value'];
          $cache->set('petitionemail_sourceRecordType', $this->sourceRecordType);
        }
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(E::ts('API Error: %1', [1 => $error]));
      }
    }

    return $this->sourceRecordType;
  }

  /**
   * Find the site's default "from" address.
   *
   * @return string
   *   The default "from" name and address.
   */
  public function getDefaultFromAddress() {
    if (empty($this->defaultFromAddress)) {
      $cache = CRM_Utils_Cache::singleton();
      $this->defaultFromAddress = $cache->get('petitionemail_defaultFromAddress');
    }
    if (empty($this->defaultFromAddress)) {
      try {
        $defaultMailParams = [
          'name' => "from_email_address",
          'options' => ['limit' => 1],
          'api.OptionValue.getsingle' => [
            'is_default' => 1,
            'options' => ['limit' => 1],
          ],
        ];
        $defaultMail = civicrm_api3('OptionGroup', 'getsingle', $defaultMailParams);
        if (empty($defaultMail['api.OptionValue.getsingle']['label'])
          || $defaultMail['api.OptionValue.getsingle']['label'] == $defaultMail['api.OptionValue.getsingle']['name']) {
          // No site email.
          // TODO: leave some kind of message with explanation.
          return NULL;
        }
        $this->defaultFromAddress = $defaultMail['api.OptionValue.getsingle']['label'];
        $cache->set('petitionemail_defaultFromAddress', $this->defaultFromAddress);
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(E::ts('API Error: %1', [1 => $error]));
      }
    }
    return $this->defaultFromAddress;
  }

  /**
   * Find the recipient interface for a petition.
   *
   * @param string $surveyId
   *   The ID of the petition.
   *
   * @return string
   *   The class of the interface, or false if not found.
   */
  public static function findInterface($surveyId) {
    try {
      $fieldId = civicrm_api3('CustomField', 'getvalue', [
        'return' => "id",
        'name' => "Recipient_System",
        'custom_group_id' => "Letter_To",
      ]);
      $result = civicrm_api3('Survey', 'getvalue', [
        'return' => "custom_$fieldId",
        'id' => $surveyId,
      ]);
      if (!empty($result)) {
        $class = "CRM_Petitionemail_Interface_$result";
        if (class_exists($class)) {
          return $class;
        }
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(E::ts('API Error: %1', [1 => $error]));
    }

    return FALSE;
  }

  /**
   * Find the field containing the petition message.
   *
   * @deprecated
   *
   * @return string
   *   The field name (e.g. "custom_4") or FALSE if none found.
   */
  public function findMessageField() {
    $messageUfField = CRM_Utils_Array::value($this->fields['Message_Field'], $this->petitionEmailVal);
    // We know $messageUfField is filled because this isn't marked incomplete.
    // Now find the field name from the UFField id.
    try {
      return civicrm_api3('UFField', 'getvalue', [
        'return' => "field_name",
        'id' => $messageUfField,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(E::ts('API Error: %1', [1 => $error]));
      return FALSE;
    }
  }

  /**
   * Set the from line for emails.
   *
   * @param int $contactId
   *   The contact ID of the sender (petition signer).
   *
   * @return string
   *   The from address in "Name <email>" format.
   */
  public function getSenderLine($contactId) {
    // Get the sender.
    try {
      $contact = civicrm_api3('Contact', 'getsingle', [
        'return' => [
          'display_name',
          'email',
        ],
        'id' => $contactId,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(E::ts('API Error: %1', [1 => $error]));
    }

    if (empty($contact['email'])) {
      return $this->getDefaultFromAddress();
    }
    elseif (empty($contact['display_name'])) {
      return $contact['email'];
    }
    else {
      return "\"{$contact['display_name']}\" <{$contact['email']}>";
    }
  }

}
