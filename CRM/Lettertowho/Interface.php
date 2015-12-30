<?php
/**
 * @file
 * Provide basics for petition delivery interfaces.
 */

/**
 * The base class for petition delivery interfaces.
 */
class CRM_Lettertowho_Interface {
  /**
   * What kind of interface this is.
   *
   * @type string
   */
  public $interfaceType = NULL;

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
  protected $fields = array();

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
  protected $petitionEmailVal = array();

  /**
   * The fields that are required to run a signature of this type.
   *
   * $type array
   */
  protected $neededFields = array(
    'Default_Message',
    'Message_Field',
  );

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
   * @return string
   *   The field name for API purposes like "custom_123".
   */
  public function findFields() {
    if (empty($this->fields)) {
      try {
        $fieldParams = array(
          'custom_group_id' => "letter_to",
          'sequential' => 1,
        );
        $result = civicrm_api3('CustomField', 'get', $fieldParams);
        if (!empty($result['values'])) {
          $this->fields = array();
          foreach ($result['values'] as $f) {
            $this->fields[$f['name']] = "custom_{$f['id']}";
          }
        }
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
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
    try {
      $surveyParams = array(
        'id' => $this->surveyId,
        'return' => array_values($this->fields),
      );
      $this->petitionEmailVal = civicrm_api3('Survey', 'getsingle', $surveyParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
    }
    return $this->petitionEmailVal;
  }

  public function petitionForm() {
  }

  public function processSignature($activityId) {
    // Send the email(s).
  }

  /**
   * Get the value for the record_type_id for an activity source.
   *
   * @return int
   *   The source activityContact record ID.
   */
  public function getSourceRecordType() {
    if (empty($this->sourceRecordType)) {
      $cache = CRM_Utils_Cache::singleton();
      $this->sourceRecordType = $cache->get('lettertowho_sourceRecordType');
    }
    if (empty($this->sourceRecordType)) {
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

        if (empty($sourceTypeInfo['api.OptionValue.getsingle']['value'])) {
          $this->sourceRecordType = 2;
        }
        else {
          $this->sourceRecordType = $sourceTypeInfo['api.OptionValue.getsingle']['value'];
          $cache->set('lettertowho_sourceRecordType', $this->sourceRecordType);
        }
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
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
      $this->defaultFromAddress = $cache->get('lettertowho_defaultFromAddress');
    }
    if (empty($this->defaultFromAddress)) {
      try {
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
          return NULL;
        }
        $this->defaultFromAddress = $defaultMail['api.OptionValue.getsingle']['label'];
        $cache->set('lettertowho_defaultFromAddress', $this->defaultFromAddress);
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
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
      $fieldId = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'name' => "Recipient_System",
        'custom_group_id' => "Letter_To",
      ));
      $result = civicrm_api3('Survey', 'getvalue', array(
        'return' => "custom_$fieldId",
        'id' => $surveyId,
      ));
      if (!empty($result)) {
        $class = "CRM_Lettertowho_Interface_$result";
        if (class_exists($class)) {
          return $class;
        }
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
    }

    return FALSE;
  }

  /**
   * Prepare the petition signature form.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The form.
   */
  public function buildSigForm($form) {
    // Process the form.
  }

  /**
   * Find the field containing the petition message.
   *
   * @return string
   *   The field name (e.g. "custom_4") or FALSE if none found.
   */
  public function findMessageField() {
    $messageUfField = CRM_Utils_Array::value($this->fields['Message_Field'], $this->petitionEmailVal);
    // We know $messageUfField is filled because this isn't marked incomplete.
    // Now find the field name from the UFField id.
    try {
      return civicrm_api3('UFField', 'getvalue', array(
        'return' => "field_name",
        'id' => $messageUfField,
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
      return FALSE;
    }
  }

}
