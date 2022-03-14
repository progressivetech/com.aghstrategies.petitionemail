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
    $this->neededFields[] = 'To';
    parent::__construct($surveyId);
  }

  /**
   * Prepare the signature form with the default message.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function buildSigForm($form) {
    return; 
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
    $this->createPendingActivity($form);
    if ($this->sendEmail($form))  {
      // If all emails sent successfully complete the activity
      $this->activity->completeActivity();
    }
  }



}
