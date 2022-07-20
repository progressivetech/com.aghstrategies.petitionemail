<?php
/**
 * @file
 * Open States class for querying the Upper state officials 
 *
 */

/**
 * @extends CRM_Petitionemail_Interface_ElectoralBase
 */
class CRM_Petitionemail_Interface_OpenstatesUpper extends CRM_Petitionemail_Interface_ElectoralBase {

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
    if ($official->getChamber() == 'upper' && $official->getLevel() == 'administrativeArea1') {
      return TRUE;
    }
    return FALSE;
  }
}
