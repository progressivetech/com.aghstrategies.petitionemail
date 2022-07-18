<?php
/**
 * @file
 * Recipient AJAX callback.
 *
 * Copyright (C) 2016, AGH Strategies, LLC <info@aghstrategies.com>
 * Licensed under the GNU Affero Public License 3.0 (see LICENSE.txt)
 */

require_once 'CRM/Core/Page.php';

/**
 * AJAX callback for legislator listing.
 */
class CRM_Petitionemail_Page_AJAX_Recipients extends CRM_Core_Page {
  /**
   * Provide the officials for the given address.
   *
   * Echos JSON object of the names, photo URLs, and emails.
   */
  public function run() {
    $addressValues = array(
      'Postal_Code_Field' => CRM_Utils_Request::retrieve('zip', 'Int'),
      'State_Province_Field' => CRM_Utils_Request::retrieve('state', 'Int'),
      'City_Field' => CRM_Utils_Request::retrieve('city', 'String'),
      'Street_Address_Field' => CRM_Utils_Request::retrieve('address', 'String'),
    );
    $surveyId = CRM_Utils_Request::retrieve('surveyId', 'Int');
    foreach ($addressValues as $val) {
      if (empty($val)) {
        return;
      }
    }

    $className = CRM_Petitionemail_Interface::findInterface($surveyId);
    $class = new $className($surveyId);
    $recipients = $class->findRecipients($addressValues);
    CRM_Utils_JSON::output($recipients);
  }

}
