<?php
/**
 * @file
 * Single email interface.
 */

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
    $this->neededFields[] = 'Recipient_Name';
    $this->neededFields[] = 'Recipient_Email';

    $fields = $this->findFields();
    $petitionemailval = $this->getFieldsData();

    foreach ($this->neededFields as $neededField) {
      if (empty($fields[$neededField]) || empty($petitionemailval[$fields[$neededField]])) {
        // TODO: provide something more meaningful.
        return;
      }
    }
    // If all needed fields are found, the system is no longer incomplete.
    $this->isIncomplete = FALSE;
  }

  /**
   * Take the signature form and send an email to the recipient.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function processSignature($form) {
    // Get the message.
    $messageField = $this->findMessageField();
    if ($messageField === FALSE) {
      return;
    }
    $message = empty($form->_submitValues[$messageField]) ? $this->petitionEmailVal[$this->fields['Default_Message']] : $form->_submitValues[$messageField];
    // If message is left empty and no default message, don't send anything.
    if (empty($message)) {
      return;
    }

    // Setup email message:
    $mailParams = [
      'groupName' => 'Activity Email Sender',
      'from' => $this->getSenderLine($form->_contactId),
      'toName' => $this->petitionEmailVal[$this->fields['Recipient_Name']],
      'toEmail' => $this->petitionEmailVal[$this->fields['Recipient_Email']],
      'subject' => $this->petitionEmailVal[$this->fields['Subject']],
      // 'cc' => $cc, TODO: offer option to CC.
      // 'bcc' => $bcc,
      'text' => $message,
      // 'html' => $html_message, TODO: offer HTML option.
    ];

    if (!CRM_Utils_Mail::send($mailParams)) {
      CRM_Core_Session::setStatus(ts('Error sending message to %1', ['domain' => 'com.aghstrategies.petitionemail', 1 => $mailParams['toName']]));
    }
    else {
      CRM_Core_Session::setStatus(ts('Message sent successfully to %1', ['domain' => 'com.aghstrategies.petitionemail', 1 => $mailParams['toName']]));
    }
    parent::processSignature($form);
  }

  /**
   * Prepare the signature form with the default message.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function buildSigForm($form) {
    $defaults = $form->getVar('_defaults');

    $messageField = $this->findMessageField();
    if ($messageField === FALSE) {
      return;
    }
    if (empty($this->petitionEmailVal[$this->fields['Default_Message']])) {
      return;
    }
    else {
      $defaultMessage = $this->petitionEmailVal[$this->fields['Default_Message']];
    }

    foreach ($form->_elements as $element) {
      if ($element->_attributes['name'] == $messageField) {
        $element->_value = $defaultMessage;
      }
    }
    $defaults[$messageField] = $form->_defaultValues[$messageField] = $defaultMessage;
    $form->setVar('_defaults', $defaults);
  }

}
