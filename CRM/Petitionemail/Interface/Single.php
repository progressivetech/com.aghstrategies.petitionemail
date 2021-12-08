<?php
/**
 * @file
 * Single email interface.
 */

use Civi\Api4\Email;
use CRM_Petitionemail_ExtensionUtil as E;

/**
 * An interface to send a single email.
 *
 * @extends CRM_Petitionemail_Interface
 */
class CRM_Petitionemail_Interface_Single extends CRM_Petitionemail_Interface {

  /**
   * Instantiate the delivery interface.
   *
   * @param int $surveyId
   *   The ID of the petition.
   */
  public function __construct($surveyId) {
    parent::__construct($surveyId);

    $this->neededFields[] = 'Subject';
    $this->neededFields[] = 'Recipient_Email';
    $this->neededFields[] = 'Recipient_Name';

    $fields = $this->findFields();
    $petitionemailval = $this->getFieldsData();

    foreach ($this->neededFields as $neededField) {
      if (empty($fields[$neededField]) || empty($petitionemailval[$fields[$neededField]])) {
        \Civi::log()->debug(__CLASS__ . ' missing neededField: ' . $neededField);
        return;
      }
    }
    // If all needed fields are found, the system is no longer incomplete.
    $this->isIncomplete = FALSE;
  }

  public static function buildFormPetitionConfig($form) {
    $form->addEntityRef('to_email_id', E::ts('To'), [
      'entity' => 'Email',
      'multiple' => TRUE,
      ''
    ]);
    $form->addEntityRef('cc_email_id', E::ts('CC'), [
      'entity' => 'Email',
      'multiple' => TRUE,
    ]);
    $form->addEntityRef('bcc_email_id', E::ts('BCC'), [
      'entity' => 'Email',
      'multiple' => TRUE,
    ]);
    CRM_Core_Region::instance('page-body')->add(
      ['template' => 'CRM/Petitionemail/Petitionconfig.tpl']
    );

    $surveyID = $form->getVar('_surveyId');
    if (!empty($surveyID)) {
      $survey = \Civi\Api4\Survey::get(FALSE)
        ->addSelect('custom.*')
        ->addWhere('id', '=', $surveyID)
        ->execute()
        ->first();
      if (!empty($survey)) {
        $form->setDefaults([
          'to_email_id' => $survey['Letter_To.To'],
          'cc_email_id' => $survey['Letter_To.CC'],
          'bcc_email_id' => $survey['Letter_To.BCC'],
        ]);
      }

    }
  }

  /**
   * Prepare the signature form with the default message.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function buildSigForm($form) {

    $form->add('textarea', 'default_message', E::ts('Message'),
      ['cols' => 60, 'rows' => 4]
    );
    CRM_Core_Region::instance('page-body')->add(
      ['template' => 'CRM/Petitionemail/Interface/Defaultmessage.tpl']
    );

    $form->setDefaults(['default_message' => $this->petitionEmailVal[$this->fields['Default_Message']]]);
  }

  /**
   * Take the signature form and send an email to the recipient.
   * // @todo: Allow to specify/send via a MessageTemplate
   * // @todo: Include emails from text fields, allow multiple for To
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function processSignature($form) {
    $message = empty($form->_submitValues['default_message']) ? $this->petitionEmailVal[$this->fields['default_message']] : $form->_submitValues['default_message'];
    // If message is left empty and no default message, don't send anything.
    if (empty($message)) {
      return;
    }

    // We need to end up with an array of toName,toEmail to pass to CRM_Utils_Mail::send()
    // Additionally we want the formatted (eg. "bob" <bob@bob.com>) for the activity

    $toContactIDs = [];
    $toNameEmails = [];
    // Get the list of formatted To Emails
    $toNames = explode(',', $this->petitionEmailVal[$this->fields['Recipient_Name']]);
    $toEmails = explode(',', $this->petitionEmailVal[$this->fields['Recipient_Email']]);

    foreach ($toEmails as $toEmail) {
      // Split into name/email
      // Try to match the toEmail to a contact in the database
      $toContactID = Email::get(FALSE)
          ->addSelect('contact_id')
          ->addWhere('email', '=', $toEmail)
          ->addOrderBy('is_primary', 'DESC')
          ->execute()
          ->first()['contact_id'] ?? NULL;
      if ($toContactID) {
        $toContactIDs[] = $toContactID;
      }
      $toNameEmails[] = [
        'toName' => array_shift($toNames),
        'toEmail' => $toEmail,
      ];
    }

    // Setup email message:
    $mailParams = [
      'groupName' => 'Activity Email Sender',
      'from' => $this->getSenderLine($form->_contactId),
      'subject' => $this->petitionEmailVal[$this->fields['Subject']],
      'text' => $message,
      // 'html' => $html_message, TODO: offer HTML option.
    ];

    // Append the Petition name so email can easily be matched to Petition
    $activitySubject = $mailParams['subject'] . ' (' . $form->getTitle() . ')';

    // Create an email activity
    $activity = new CRM_Petitionemail_Activity();
    $activityParams = [
      'subject' => $activitySubject,
      'text' => $mailParams['text'],
      'source_contact_id' => $form->_contactId,
    ];

    $to = $this->getEmails(explode(',', $this->petitionEmailVal[$this->fields['To']]));
    if (!empty($to)) {
      foreach ($to as $toContactID => $toDetail) {
        $toContactIDs[] = $toContactID;
        $toNameEmails[] = [
          'toName' => $toDetail['contact_id.sort_name'],
          'toEmail' => $toDetail['email'],
        ];
      }
    }

    // Record "To" contacts as targets on the activity
    if (!empty($toContactIDs)) {
      $activityParams['target_contact_id'] = $toContactIDs;
    }
    $activityParams['toNameEmails'] = $toNameEmails;

    $cc = $this->getEmailsCsv(explode(',', $this->petitionEmailVal[$this->fields['CC']]));
    if (!empty($cc)) {
      $mailParams['cc'] = $cc;
      $activityParams['cc'] = $cc;
    }

    $bcc = $this->getEmailsCsv(explode(',', $this->petitionEmailVal[$this->fields['BCC']]));
    if (!empty($bcc)) {
      $mailParams['bcc'] = $bcc;
      $activityParams['bcc'] = $bcc;
    }

    // Create the "Email" activity. We only create one if there are multiple "To" addresses
    //   with all target contacts recorded on the activity.
    $activity->createActivity($activityParams);

    // If we have multiple "To" addresses we send the mail multiple times
    foreach ($toNameEmails as $toDetail) {
      $mailParams['toName'] = $toDetail['toName'];
      $mailParams['toEmail'] = $toDetail['toEmail'];
      if (!CRM_Utils_Mail::send($mailParams)) {
        $errorMessage = E::ts('Error sending message to %1', [1 => $mailParams['toName']]);
        CRM_Core_Session::setStatus($errorMessage);
        \Civi::log()->error(E::SHORT_NAME . ': ' . $errorMessage);
      }
      else {
        CRM_Core_Session::setStatus(E::ts('Message sent successfully to %1', [1 => $mailParams['toName']]));
      }
    }
    // If all emails sent successfully complete the activity
    if (!isset($errorMessage)) {
      $activity->completeActivity();
    }
  }

  /**
   * Get detail for specified email entityIDs ('contact_id', 'email', 'contact.sort_name', 'contact.display_name')
   *   indexed by emailID.
   *
   * @param array $emailIDs
   *
   * @return array
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function getEmails(array $emailIDs): array {
    if (empty($emailIDs)) {
      return [];
    }

    $emails = Email::get(FALSE)
      ->addSelect('contact_id', 'email', 'contact_id.sort_name', 'contact_id.display_name')
      ->addWhere('id', 'IN', $emailIDs)
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();
    return $emails;
  }

  /**
   * Given an address in format "Flowerpot, Bill" <billflowerpot@example.org> return ['name' => 'Flowerpot, Bill', 'email' => 'billflowerpot@example.org]
   * @see https://stackoverflow.com/questions/16685416/split-full-email-addresses-into-name-and-email
   *
   * @param string $emailString
   *
   * @return array
   */
  private function getNameAndEmail(string $emailString): array {
    $emailString .=" ";
    $sPattern = '/([^<]*)?(<)?(([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4}))?(>)?/';
    preg_match($sPattern, $emailString, $aMatch);
    //echo "string";
    //print_r($aMatch);
    $name = (isset($aMatch[1])) ? $aMatch[1] : '';
    $email = (isset($aMatch[3])) ? $aMatch[3] : '';
    return ['name' => trim($name), 'email' => trim($email)];
  }

  /**
   * @param array $emailIDs
   *
   * @return string
   */
  private function getEmailsCsv(array $emailIDs): string {
    if (empty($emailIDs)) {
      return '';
    }
    $emailStrings = [];
    $emailArray = $this->getEmails($emailIDs);
    foreach ($emailArray as $email) {
      $emailStrings[] = CRM_Utils_Mail::formatRFC2822Name($email['contact_id.sort_name']) . ' <' . $email['email'] . '>';
    }
    return implode(',', $emailStrings);
  }

}
