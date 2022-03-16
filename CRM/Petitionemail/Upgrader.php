<?php

use Civi\Api4\CustomField;
use Civi\Api4\UFField;

/**
 * Collection of upgrade steps.
 */
class CRM_Petitionemail_Upgrader extends CRM_Petitionemail_Upgrader_Base {

  function upgrade_2000 () {
    // Disable no longer used fields. These should get yanked by the
    // managed code, but just in case we disable them here.
    $fields = [
      'Recipient_Name',
      'Recipient_Email',
      'CC',
      'CC_Email',
      'Message_Field'
    ];

    foreach($fields as $field) {
      $id = \Civi\Api4\CustomField::get()
        ->addSelect('id')
        ->addWhere('custom_group_id:name', '=', 'Letter_To')
        ->addWhere('name', '=', $field)
        ->execute()->first()['id'];
      if ($id) {
        \Civi\Api4\CustomField::update()
          ->addWhere('id', '=', $id)
          ->addValue('is_active', FALSE)
          ->execute();
      }
    }
    return TRUE;
  }
}
