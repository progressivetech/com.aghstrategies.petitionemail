<?php

use Civi\Api4\CustomField;
use Civi\Api4\UFField;
use CRM_Petitionemail_ExtensionUtil as E;

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

  public function upgrade_2001() {
    $this->addMessageTemplate();
    return TRUE;
  }

  private function addMessageTemplate() {
    // Create msg template
    if (empty(\Civi\Api4\MessageTemplate::get(FALSE)
      ->addWhere('msg_title', '=', 'Sample Petition Email')
      ->execute()
      ->count())
    ) {
      \Civi\Api4\MessageTemplate::create(FALSE)
        ->addValue('msg_title', 'Sample Petition Email')
        ->addValue('msg_subject', '{petitionemail.subject}')
        ->addValue('msg_html', "{petitionemail.senderIdentificationBlock}

{contact.email_greeting_display}

{petitionemail.message}")
        ->execute();
    }
  }

}
