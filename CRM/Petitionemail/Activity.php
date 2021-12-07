<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\Activity;
use Civi\Api4\ActivityContact;
use Civi\Api4\CaseActivity;
use \Civi\Api4\Email;

/**
 * Class CRM_Petitionemail_Activity
 */
class CRM_Petitionemail_Activity {

  /**
   * @var array
   */
  private $contactEmails = [];

  /**
   * @var int
   */
  private $activityID = NULL;

  /**
   * Create an email activity
   * Note: Attachments are not supported
   *
   * @param array $params
   *
   * @return int|NULL
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function createActivity(array $params) {
    // Create it as Cancelled - we update it to Completed if it successfully sends
    $params['status_id'] = $params['status_id'] ?? 'Cancelled';

    if (!empty($params['contactId']) && empty($params['target_contact_id'])) {
      $params['target_contact_id'] = $params['contactId'];
    }

    $additionalDetails = '';
    if (!empty($params['toNameEmails'])) {
      foreach ($params['toNameEmails'] as $nameEmail) {
        $to[] = "'{$nameEmail['toName']}' <{$nameEmail['toEmail']}>";
      }
      $additionalDetails .= "\nTo : " . implode(',', $to);
    }

    $additionalDetails .= empty($params['cc']) ? '' : "\ncc : " . $params['cc'];
    $additionalDetails .= empty($params['bcc']) ? '' : "\nbcc : " . $params['bcc'];

    // Save both text and HTML parts in details (if present)
    if (!empty($params['html']) && !empty($params['text'])) {
      $details = "-ALTERNATIVE ITEM 0-\n{$params['html']}{$additionalDetails}\n-ALTERNATIVE ITEM 1-\n{$params['text']}{$additionalDetails}\n-ALTERNATIVE END-\n";
    }
    else {
      $details = $params['html'] ?? $params['text'] ?? '';
      $details .= $additionalDetails;
    }

    // We must have a source contact. Try the logged in contact, or if not use the domain contact ID.
    if (empty('source_contact_id')) {
      unset($params['source_contact_id']);
    }
    $sourceContactID = $params['source_contact_id'] ??
      CRM_Core_Session::getLoggedInContactID()
      ?? \Civi\Api4\Domain::get(FALSE)
        ->setCurrentDomain(TRUE)
        ->execute()
        ->first()['contact_id'];

    try {
      $activity = Activity::create(FALSE)
        ->addValue('activity_type_id:name', 'Email')
        ->addValue('subject', $params['subject'] ?? '')
        ->addValue('details', $details)
        ->addValue('status_id:name', $params['status_id'])
        ->addValue('source_contact_id', $sourceContactID)
        ->execute()
        ->first();

      if (!empty($params['case_id'])) {
        CaseActivity::create(FALSE)
          ->addValue('case_id', $params['case_id'])
          ->addValue('activity_id', $activity['id'])
          ->execute();
      }

      if (!empty($params['target_contact_id'])) {
        foreach ($params['target_contact_id'] as $targetContactID) {
          ActivityContact::create(FALSE)
            ->addValue('activity_id', $activity['id'])
            ->addValue('contact_id', $targetContactID)
            ->addValue('record_type_id:name', 'Activity Targets')
            ->execute();
        }
      }
    }
    catch (Exception $e) {
      \Civi::log()->error('Failed to create Email activity. ' . $e->getMessage());
      return NULL;
    }
    $this->activityID = $activity['id'];
    return $this->activityID;
  }

  /**
   * Sugar to update the activity to Completed.
   */
  public function completeActivity() {
    Activity::update(FALSE)
      ->addValue('status_id:name', 'Completed')
      ->addWhere('id', '=', $this->activityID)
      ->execute();
  }

  /**
   * Get the string for the email IDs.
   *
   * @param array $emailIDs
   *   Array of email IDs.
   *
   * @return string
   *   e.g. "Smith, Bob<bob.smith@example.com>".
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getEmailString(array $emailIDs): string {
    if (empty($emailIDs)) {
      return '';
    }
    $emails = Email::get()
      ->addWhere('id', 'IN', $emailIDs)
      ->setCheckPermissions(FALSE)
      ->setSelect(['contact_id', 'email', 'contact.sort_name', 'contact.display_name'])->execute();
    $emailStrings = [];
    foreach ($emails as $email) {
      $this->contactEmails[$email['id']] = $email;
      $emailStrings[] = '"' . $email['contact.sort_name'] . '" <' . $email['email'] . '>';
    }
    return implode(',', $emailStrings);
  }

  /**
   * Get the url string.
   *
   * This is called after the contacts have been retrieved so we don't need to re-retrieve.
   *
   * @param array $emailIDs
   *
   * @return string
   *   e.g. <a href='{$contactURL}'>Bob Smith</a>'
   */
  protected function getEmailUrlString(array $emailIDs): string {
    $urlString = '';
    foreach ($emailIDs as $email) {
      $contactURL = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'force' => 1, 'cid' => $this->contactEmails[$email]['contact_id']], TRUE);
      $urlString .= "<a href='{$contactURL}'>" . $this->contactEmails[$email]['contact.display_name'] . '</a>';
    }
    return $urlString;
  }

}
