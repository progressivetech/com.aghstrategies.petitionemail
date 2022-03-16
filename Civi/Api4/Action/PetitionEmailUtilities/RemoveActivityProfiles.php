<?php
  
namespace Civi\Api4\Action\PetitionEmailUtilities;
use CRM_PetitionEmail_ExtensionUtil as E;

/**
 *
 * Remove activity profiles.
 *
 * This helper will remove the activity profile from all petitions.
 *
 * Many installations have a custom message field provided by the Activity
 * profile. When the custom message field became automatically provided by this
 * extension, it left many petitions with two custom message fields.
 *
 * This helper resolves the problem by removing the activity profile from all
 * petitions. Note: if *additional* activity fields were provided by the
 * activity profile, then you should not use this action and instead manually
 * remove just the custom message field.
 *
 */
class RemoveActivityProfiles extends \Civi\Api4\Generic\AbstractAction {
 
  public function _run(\Civi\Api4\Generic\Result $result) {
    // Get the activity_type_id for petitions.
    $petitionActivityTypeId = \Civi\Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('name', '=', 'Petition')
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->execute()->first()['value'];
    // Get the Letter_To table name. 
    $petitionValueTable = \Civi\Api4\CustomGroup::get()
      ->addSelect('table_name')
      ->addWhere('name', '=', 'Letter_To')
      ->execute()->first()['table_name'];

    $sql = "
      SELECT DISTINCT(s.id)
      FROM 
        civicrm_survey s JOIN
        civicrm_uf_join j ON j.entity_table = 'civicrm_survey' AND j.entity_id = s.id JOIN
        $petitionValueTable v ON s.id = v.entity_id
      WHERE s.activity_type_id = %0 AND weight = 1
    ";
        
    $params = [ 0 => [ $petitionActivityTypeId, 'Integer' ] ];
    $dao = \CRM_Core_DAO::executeQuery($sql, $params); 
    $count = 0;
    while ($dao->fetch()) {
      // In the core Campaign code, the profiles for each petition are stored
      // in the civicrm_uf_join table. The activity petition is given a weight
      // of 1 and the contact petition a weight of 2.
      \Civi\Api4\UFJoin::delete()
        ->addWhere('entity_table', '=', 'civicrm_survey')
        ->addWhere('entity_id', '=', $dao->id )
        ->addWhere('weight', '=', 1)
        ->execute();
      $count++;
    }
    $result[] = [ E::ts('%1 petitions had their activity profile removed', [ 1 => $count ]) ];
  }
}
