<?php
/**
 * @file
 * Open states class for querying house of reps
 *
 */

/**
 * @extends CRM_Petitionemail_Interface_ElectoralBase
 */
class CRM_Petitionemail_Interface_OpenstatesNationalHouse extends CRM_Petitionemail_Interface_ElectoralBase {

  /**
   * The class to use for lookups
   *
   * Should be overridden on the inherited class.
   */
  protected $electoralLookupClass = '\Civi\Electoral\Api\Openstates';

  /**
   * Include official
   *
   * Should we include the given official or filter them
   * out?
   */
  protected function includeOfficial($official) {
    if ($official->getChamber() == 'lower' && $official->getLevel() == 'country') {
      return TRUE;
    }
    return FALSE;
  }
}
