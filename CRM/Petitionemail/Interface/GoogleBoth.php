<?php
/**
 * @file
 * Google class for querying the Upper House state officials 
 *
 */

/**
 * @extends CRM_Petitionemail_Interface_ElectoralBase
 */
class CRM_Petitionemail_Interface_GoogleBoth extends CRM_Petitionemail_Interface_ElectoralBase {

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
    $allowedChambers = [ 'upper', 'lower' ];
    $chambers = $official['chamber'] ?? NULL;
    if (in_array($chamber, $allowedChambers) && $official['level'] == 'administrativeArea1') {
      return TRUE;
    }
    return FALSE;
  }
}
