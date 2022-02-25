<?php

use Civi\Api4\CustomField;
use Civi\Api4\UFField;

/**
 * Collection of upgrade steps.
 */
class CRM_Petitionemail_Upgrader extends CRM_Petitionemail_Upgrader_Base {

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1000() {
    $this->ctx->log->info('Deleting "Message_Field" custom field');
    $customField = CustomField::get(FALSE)
      ->addWhere('custom_group_id:name', '=', 'Letter_To')
      ->addWhere('name', '=', 'Message_Field')
      ->execute()
      ->first();
    if (!empty($customField)) {
      $customFieldName = "custom_{$customField['id']}";
      // Delete field from Profiles
      UFField::delete(FALSE)
        ->addWhere('field_name', '=', $customFieldName)
        ->execute();
      // Now delete the custom field
      CustomField::delete(FALSE)
        ->addWhere('custom_group_id:name', '=', 'Letter_To')
        ->addWhere('name', '=', 'Message_Field')
        ->execute();
    }
    return TRUE;
  }

  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001');
    petitionemail_prefill_entities();
    return TRUE;
  }
}
