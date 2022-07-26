<?php
/**
 * @file
 * Google class for querying the Upper state senate officials 
 *
 */

/**
 * @extends CRM_Petitionemail_Interface_ElectoralBase
 */
class CRM_Petitionemail_Interface_GoogleUpper extends CRM_Petitionemail_Interface_ElectoralBase {

  /**
   * The class to use for lookups
   *
   * Should be overridden on the inherited class.
   */
  protected $electoralLookupClass = '\Civi\Electoral\Api\GoogleCivicInformation';

  /**
   * Include official
   *
   * Should we include the given official or filter them
   * out?
   */
  protected function includeOfficial($official) {
    \Civi::log()->debug("chamger: " . $official->getChamber() . ' and level: ' . $official->getLevel());
    if ($official->getChamber() == 'upper' && $official->getLevel() == 'administrativeArea1') {
      return TRUE;
    }
    return FALSE;
  }
}
