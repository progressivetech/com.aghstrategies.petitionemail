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
  protected $petitionFields = [];

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
  protected $petitionFieldValues = [];

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
  public $isComplete = FALSE;

  /**
   * The activity object created for each given signature
   */
  protected $activity;

  public function __construct($surveyId) {
    $this->surveyId = $surveyId;
    $this->setPetitionFields();
    $this->setPetitionValues();

    foreach ($this->neededFields as $neededField) {
      if (empty($this->getPetitionValue($neededField))) {
        \Civi::log()->debug(__CLASS__ . ' missing neededField: ' . $neededField);
        // TODO: provide something more meaningful.
        return;
      }
    }
    // If all needed fields are found, the system is no longer incomplete.
    $this->isComplete = TRUE;
  }

  /**
   * Find the custom fields.
   *
   * @return array
   */
  public function setPetitionFields() {
    if (empty($this->fields)) {
      $customFields = \Civi\Api4\CustomField::get(FALSE)
        ->addWhere('custom_group_id:name', '=', 'Letter_To')
        ->execute();

      foreach ($customFields as $customField) {
        $this->petitionFields[$customField['name']] = "Letter_To.{$customField['name']}";
      }
    }
    return $this->petitionFields;
  }

  /**
   * Get the survey data.
   *
   * @return array
   *   The survey info.
   */
  public function setPetitionValues() {
    $this->petitionFieldValues = \Civi\Api4\Survey::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $this->surveyId)
      ->execute()
      ->first();
    return $this->petitionFieldValues;
  }

  /**
   * Get the value of a given petition field. 
   *
   * Retrieve the value for a given petition field.
   *
   **/
  protected function getPetitionValue($field) {
    if (empty($this->petitionFields[$field])) {
      \Civi::log()->debug("Failed getting value for non-existent field ${field}.");
      return NULL;
    };
    $fieldKey = $this->petitionFields[$field];
    return $this->petitionFieldValues[$fieldKey];
  }

  /**
   * Get the value of a submitted petition field. 
   *
   * Given a form and a field, return the value of the field
   * for the given form.
   *
   **/
  protected function getSubmittedValue($form, $field) {
    if (empty($form->_submitValues[$field])) {
      // If empty, use the default value.
      if ($field == 'signer_message') {
        return $this->getPetitionValue('Default_Message');
      }
      elseif ($field == 'signer_subject') {
        return $this->getPetitionValue('Default_Subject');
      }
      return ''; 
    }
    return $form->_submitValues[$field];
  }

  /**
   * Add body and subject to the petition form.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function addMessageAndSubjectToSigForm($form) {
    $form->add('text', 'signer_subject', E::ts('Subject'));
    $form->add('textarea', 'signer_message', E::ts('Message'),
      ['cols' => 60, 'rows' => 4]
    );
    CRM_Core_Region::instance('page-body')->add(
      ['template' => 'CRM/Petitionemail/Interface/Defaultmessage.tpl']
    );
    $defaults = [
      'signer_subject' => $this->getPetitionValue('Subject'),
      'signer_message' => $this->getPetitionValue('Default_Message')
    ];
    $form->setDefaults($defaults);
  }

  /**
   * Prepare the petition signature form.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The form.
   */
  public function buildSigForm($form) {}

  /**
   * Send the emails
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *
   */
  public function processSignature($form) {}

  /**
   * Create activity
   *
   * This creates an initial, incomplete activity that can be completed
   * with a call to $this->activity->completeActivity() once all emails are successfully
   * sent.
   *
   * @param $extraContactIds - add any extra contactIDs that should be
   * "with"ed on the activity.
   *
   * Generate an activity linking the signer to anyone who got the message.
   */
  protected function createPendingActivity($form, $extraContactIds = []) {
    $message = $this->getSenderIdentificationBlock($form) . "\n\n" .
      $this->getSubmittedValue($form, 'signer_message');
    $subject = $this->getSubmittedValue($form, 'signer_subject');

    // Append the Petition name so email can easily be matched to Petition
    $activitySubject = $subject;

    $targets = array_merge($this->getPetitionValue('To'), $extraContactIds);
    // Create an email activity
    $activityParams = [
      'subject' => $activitySubject,
      'text' => $message,
      'source_contact_id' => $form->_contactId,
      'target_contact_id' => $targets,
    ];
    $this->activity = new CRM_Petitionemail_Activity();
    $this->activity->createActivity($activityParams);
  } 

  /**
   * Retrieve or add contact
   *
   * If a matching contact exists, return the contact id. Otherwise
   * add the contact and return the contact id.
   */
  protected function addOrRetrieveContact($email, $first_name, $last_name, $middle_name = NULL, $title = NULL) {
    $record = \Civi\Api4\Email::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('contact_id')
      ->addWhere('contact_id.first_name', '=', $first_name)
      ->addWhere('contact_id.last_name', '=', $last_name)
      ->addWhere('contact_id.is_deleted', '=', FALSE)
      ->addWhere('email', '=', $email)
      ->execute()->first();

    if (!empty($record['contact_id'])) {
      return $record['contact_id'];
    }
    
    $contact = \Civi\Api4\Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', $first_name)
      ->addValue('last_name', $last_name)
      ->addValue('middle_name', $middle_name)
      ->addValue('contact_type', 'Individual')
      ->addValue('do_not_email', TRUE)
      ->addChain('create_email', \Civi\Api4\Email::create()->setValues(['contact_id' => '$id', 'email' => $email]));

    // Check if title exists in prefix list.
    if ($title) {
      $prefixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
      if (in_array($title, $prefixes)) {
        $contact->addValue('prefix_id:name', $title);
      }
    }
    return $contact->execute()->first()['id'];
  }

  /**
   * Get Sender Identification block.
   *
   * Get the block of text to be prepended to the message
   * that contains the senders contact information.
   */
  protected function getSenderIdentificationBlock($form) {
    $contactId = $form->_contactId;
    // Other classes may want to override to add additional info.
    // The base class only adds the Name and Email.
    $contact = \Civi\Api4\Email::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('contact_id.display_name')
      ->addSelect('email')
      ->addSelect('is_primary', '=', TRUE)
      ->addWhere('contact_id', '=', $contactId)
      ->execute()->first();

    return $contact['contact_id.display_name'] . "\n" .
      $contact['email'];
  }
  
  /**
   * Send email
   *
   * Send email to everyone specified in the To and Bcc fields.
   */
  protected function sendEmail($form, $extraContactIds = []) {
    $message = $this->getSubmittedValue($form, 'signer_message');
    $subject = $this->getSubmittedValue($form, 'signer_subject');
    $targets = array_merge($this->getPetitionValue('To'), $extraContactIds);

    // If message is left empty and no default message, don't send anything.
    if (empty($message)) {
      return;
    }

    // Setup email message:
    $mailParams = [
      'from' => $this->getSenderLine($form->_contactId),
      'subject' => $subject,
    ];

    $toEmails = $this->getContactDetails($targets);
    $bcc = $this->getPetitionValue('BCC');
    if ($bcc) {
      $bccEmails = $this->getContactDetails($bcc);
      if ($bccEmails) {
        $bccHeader = [];
        foreach($bccEmails as $bcc) {
          $bccHeader[] = CRM_Utils_Mail::formatRFC822Email($bcc['name'], $bcc['email']);
        }
        $mailParams['headers']['bcc'] = implode(',', $bccHeader);
      }
    }

    // Set the sender email as the reply to address.
    $mailParams['headers']['reply-to'] = \Civi\Api4\Email::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('email')
      ->addSelect('is_primary', '=', TRUE)
      ->addWhere('contact_id', '=', $form->_contactId)
      ->execute()->first()['email'];

    // If we have multiple "To" addresses we send the mail multiple times
    foreach ($toEmails as $toDetail) {
      $mailParams['toName'] = $toDetail['name'];
      $mailParams['toEmail'] = $toDetail['email'];
      $mailParams['text'] = 
        $this->getSenderIdentificationBlock($form) . "\n\n" .
        $toDetail['greeting'] . "\n\n" . 
        $message;
      if (!CRM_Utils_Mail::send($mailParams)) {
        $errorMessage = E::ts('Error sending message to %1', [1 => $mailParams['toEmail']]);
        CRM_Core_Session::setStatus($errorMessage);
        \Civi::log()->error(E::SHORT_NAME . ': ' . $errorMessage);
        return FALSE;
      }
      else {
        CRM_Core_Session::setStatus(E::ts('Message sent successfully to %1', [1 => $mailParams['toName']]));
      }
    }
    return TRUE;
  }

  /**
   * Get contact details for the specified contact id
   *
   * Return array with name and email indexed by contactId.
   *
   * @param array $contactIds
   *
   * @return array
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getContactDetails(array $contactIds): array {
    if (empty($contactIds)) {
      return [];
    }

    $results = \Civi\Api4\Email::get(FALSE)
      ->addSelect('contact_id', 'email', 'contact_id.display_name', 'prefix_id:name', 'last_name')
      ->addWhere('contact_id', 'IN', $contactIds)
      ->addWhere('is_primary', '=', TRUE)
      ->execute();
    $details = [];
    foreach ($results as $contact) {
      $id = $contact['contact_id'];
      $greeting = 'Dear ' . $contact['contact_id.display_name'] . ',';
      if (!empty($contact['prefix_id:name']) && !empty($contact['last_name'])) {
        $greeting = 'Dear ' . $contact['prefix_id:name'] . ' ' . $contact['last_name'] . ',';
      }
      $details[$id] =  [
        'name' => $contact['contact_id.display_name'], 
        'email' => $contact['email'],
        'greeting' => $greeting,
      ];
    }
    return $details;
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
    $label = \Civi\Api4\OptionValue::get()
      ->setCheckPermissions(FALSE) 
      ->addSelect('label')
      ->addWhere('option_group_id:name', '=', from_email_address)
      ->addWhere('is_default', '=', TRUE)
      ->execute()->first()['label'];

    // The label returns the whole "name" <email> bit. We only want the email
    // because we'll use the name from the signer.
    if (preg_match('/<([^>]+)>/', $label, $matches)) {
      return $matches[1];
    }
    return NULL;
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
   * Note: if we send from the email address of the signer, it will most likely
   * get blocked by the received due to SPF/DKIM. So we use the name of the signer
   * and the email address specified by the petition.
   *
   * @return string
   *   The from address in "Name <email>" format.
   */
  public function getSenderLine($contactId) {
    // Get the sender.
    $displayName = \Civi\Api4\Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactId)
      ->execute()->first()['display_name'];
    $email = $this->getPetitionValue('Petition_From_Email');
    if (empty($email)) {
      $email = $this->getDefaultFromAddress();
    }
    return CRM_Utils_Mail::formatRFC822Email($displayName, $email);
  }

}
