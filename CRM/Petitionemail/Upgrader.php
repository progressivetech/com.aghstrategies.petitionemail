<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Petitionemail_Upgrader extends CRM_Petitionemail_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Convert from old custom names to new ones.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1904() {
    $this->ctx->log->info('Applying update 1200');

    // Search for the presence of the old custom group name.
    $res = \Civi\Api4\CustomGroup::get()
      ->addSelect('id')
      ->addWhere('name', '=', 'Letter_To')
      ->execute();
    $email_recipient_cg = $res->first();

    // This operation should be indempotent. Only run the rest of the code
    // if this old custom group exists.
    if ($email_recipient_cg) {
      $email_recipient_cg_id = $email_recipient_cg['id'];

      // Rename the custom group. Note: I'm not bothering to update the table
      // name.
      \Civi\Api4\CustomGroup::update()
        ->addWhere('id', '=', $email_recipient_cg_id)
        ->addValue('name', 'Email_Recipient')
        ->addValue('title', 'Email Recipient')
        ->execute();

      // Rename the custom fields. Note: I'm not bothering to update the column
      // names of these fields.
      \Civi\Api4\CustomField::update()
        ->addWhere('name', '=', 'Recipient_System')
        ->addWhere('custom_group_id', '=', $email_recipient_cg_id)
        ->addValue('name', 'Email_Recipient_System')
        ->addValue('title', 'Email Recipient')
        ->execute();
      \Civi\Api4\CustomField::update()
        ->addWhere('name', '=', 'Subject')
        ->addWhere('custom_group_id', '=', $email_recipient_cg_id)
        ->addValue('name', 'Support_Subject')
        ->addValue('title', 'Email Recipient')
        ->execute();
      \Civi\Api4\CustomField::update()
        ->addWhere('name', '=', 'Default_Message')
        ->addWhere('custom_group_id', '=', $email_recipient_cg_id)
        ->addValue('name', 'Support_Message')
        ->addValue('title', 'Message')
        ->addValue('html_type', 'RichTextEditor')
        ->execute();
      \Civi\Api4\CustomField::update()
        ->addWhere('name', '=', 'Message_Field')
        ->addWhere('custom_group_id', '=', $email_recipient_cg_id)
        ->addValue('name', 'Support_Message_Field')
        ->execute();

      // Change name of option group
      \Civi\Api4\OptionGroup::update()
        ->addWhere('name', '=', 'letter_to_recipient_system')
        ->addValue('name', 'email_recipient_system')
        ->execute();

      // Create the new custom groups.
      $result = \Civi\Api4\CustomGroup::create()
        ->addValue('name', 'Support_Message')
        ->addValue('title', 'Email Message to Recipient to Urge Support')
        ->addValue('extends', 'Survey')
        ->addValue('style', 'Inline')
        ->addValue('collapse_display', 1)
        ->addValue('weight', 4)
        ->addValue('is_active',  1)
        ->addValue('is_multiple', 0)
        ->addValue('collapse_adv_display', 0)
        ->addValue('is_reserved', 1)
        ->execute();
      $support_message_cg_id = $result->first()['id'];

      $result = \Civi\Api4\CustomGroup::create()
        ->addValue('name', 'Support_Email_to_Recipient')
        ->addValue('title', 'Support Email Message sent to Recipient')
        ->addValue('extends', 'Activity')
        ->addValue('extends_entity_column_value', 32)
        ->addValue('style', 'Inline')
        ->addValue('collapse_display', 1)
        ->addValue('weight', 5)
        ->addValue('is_active',  1)
        ->addValue('is_multiple', 0)
        ->addValue('collapse_adv_display', 0)
        ->addValue('is_reserved', 1)
        ->execute();
      $support_email_to_recipient_cg_id = $result->first()['id'];

      // We're moving these fields from the original custom group to the new one.
      $move_fields = [
        'Support_Subject' => $support_message_cg_id,
        'Support_Message' => $support_message_cg_id,
        'Support_Message_Field' => $support_message_cg_id,
      ];

      foreach ($move_fields as $name => $custom_group_id) {
        $field_id = \Civi\Api4\CustomField::get()
          ->addWhere('name', '=', $name)
          ->addWhere('custom_group_id', '=', $email_recipient_cg_id)
          ->execute()
          ->first()['id'];
          CRM_Core_BAO_CustomField::moveField($field_id, $custom_group_id);
      }

      // Now create the new custom fields.
      \Civi\Api4\CustomField::create()
        ->addValue('name', 'Support_Subject_Field')
        ->addValue('label', 'Subject Field')
        ->addvalue('data_type', 'Int')
        ->addvalue('html_type', 'Text')
        ->addValue('is_required', 0)
        ->addValue('is_searchable', 0)
        ->addValue('is_search_range', 0)
        ->addValue('weight', 3)
        ->addvalue('help_pos', 'The ID number of the activity custom field storing the petition subject.')
        ->addValue('is_active', 1)
        ->addValue('is_view', 0)
        ->addValue('text_length', 255)
        ->addvalue('note_columns', 60)
        ->addValue('column_name', 'support_subject_field')
        ->addValue('in_selector', 0)
        ->addValue('custom_group_id', $support_message_cg_id )
        ->execute();

      \Civi\Api4\CustomField::create()
        ->addValue('name', 'Support_Email_Subject')
        ->addValue('label', 'Support Subject')
        ->addvalue('data_type', 'String')
        ->addvalue('html_type', 'Text')
        ->addValue('is_required', 0)
        ->addValue('is_searchable', 1)
        ->addValue('is_search_range', 0)
        ->addValue('weight', 2)
        ->addValue('is_active', 1)
        ->addValue('is_view', 0)
        ->addValue('text_length', 128)
        ->addvalue('note_columns', 60)
        ->addValue('note_rows', 4)
        ->addValue('column_name', 'support_email_to_recipient_subject')
        ->addValue('in_selector', 0)
        ->addValue('custom_group_id', $support_email_to_recipient_cg_id )
        ->execute();

      \Civi\Api4\CustomField::create()
        ->addValue('name', 'Support_Email_Message')
        ->addValue('label', 'Support Message')
        ->addvalue('data_type', 'Memo')
        ->addvalue('html_type', 'RichTextEditor')
        ->addValue('is_required', 0)
        ->addValue('is_searchable', 0)
        ->addValue('is_search_range', 0)
        ->addValue('weight', 9)
        ->addValue('attributes', 'rows=4, cols=60')
        ->addValue('is_active', 1)
        ->addValue('is_view', 0)
        ->addvalue('note_columns', 60)
        ->addValue('note_rows', 10)
        ->addValue('column_name', 'support_email_to_recipient_message')
        ->addValue('in_selector', 0)
        ->addValue('custom_group_id', $support_email_to_recipient_cg_id )
        ->execute();
    }
    return TRUE;
  }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
