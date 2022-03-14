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
    $statusId = $params['status_id'] ?? 'Cancelled';
    $details = $params['html'] ?? $params['text'] ?? '';

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
        ->addValue('status_id:name', $statusId)
        ->addValue('source_contact_id', $sourceContactID)
        ->execute()
        ->first();

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

}
