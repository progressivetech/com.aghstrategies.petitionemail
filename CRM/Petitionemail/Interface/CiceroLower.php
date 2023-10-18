<?php
/**
 * @file
 * Cicero class for querying the Lower state officials 
 *
 */

/**
 * @extends CRM_Petitionemail_Interface_ElectoralBase
 */
class CRM_Petitionemail_Interface_CiceroLower extends CRM_Petitionemail_Interface_ElectoralBase {

  /**
   * The class to use for lookups
   *
   * Should be overridden on the inherited class.
   */
  protected $electoralLookupClass = '\Civi\Electoral\Api\Cicero';

  /**
   * Include official
   *
   * Should we include the given official or filter them
   * out?
   */
  protected function includeOfficial($official) {
    if ($official['chamber'] == 'lower' && $official['level'] == 'administrativeArea1') {
      return TRUE;
    }
    return FALSE;
  }
}
