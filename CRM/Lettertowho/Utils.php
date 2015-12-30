<?php
/**
 * @file
 * Utilities for petition emails.
 */

class CRM_Lettertowho_Utils {
  /**
   * Find the activity type ID for petitions.
   *
   * @return int
   *   The activity type ID.
   */
  public static function getPetitionActivityType() {
    $cache = CRM_Utils_Cache::singleton();
    $petitionActivityType = $cache->get('lettertowho_petitionActivityType');
    if (empty($petitionActivityType)) {
      try {
        $petitionTypeParams = array(
          'name' => "activity_type",
          'api.OptionValue.getsingle' => array(
            'option_group_id' => '$value.id',
            'name' => "Petition",
            'options' => array('limit' => 1),
          ),
          'options' => array('limit' => 1),
        );
        $petitionTypeInfo = civicrm_api3('OptionGroup', 'getsingle', $petitionTypeParams);
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.lettertowho')));
      }
      if (empty($petitionTypeInfo['api.OptionValue.getsingle']['value'])) {
        return;
      }
      else {
        $petitionActivityType = $petitionTypeInfo['api.OptionValue.getsingle']['value'];
        $cache->set('lettertowho_petitionActivityType', $petitionActivityType);
      }
    }
    return $petitionActivityType;
  }
}
