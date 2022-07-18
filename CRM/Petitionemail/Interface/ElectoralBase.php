<?php
/**
 * @file
 * Base interface for connecting petition email to electoral extension 
 *
 */

/**
 * An interface to send petition messages to any elected official that can
 * be looked up via the electoral extension. 
 *
 * @extends CRM_Petitionemail_Interface
 */
class CRM_Petitionemail_Interface_ElectoralBase extends CRM_Petitionemail_Interface {

  /**
   * The class to use for lookups
   *
   * Should be overridden on the inherited class.
   */
  protected $electoralLookupClass = NULL;

  /**
   * Fields needed to form address for lookup.
   *
   * @type array
   */
  private $addressFields = array(
    'Street_Address_Field',
    'City_Field',
    'State_Province_Field',
    'Postal_Code_Field',
  );

  /**
   * Instantiate the delivery interface.
   *
   * @param int $surveyId
   *   The ID of the petition.
   */
  public function __construct($surveyId) {
    $this->neededFields = $this->addressFields;
    parent::__construct($surveyId);
  }

  /**
   * Get Sender Identification block.
   *
   * Override parent to add postal address fields. 
   */
  protected function getSenderIdentificationBlock($form) {
    $block = parent::getSenderIdentificationBlock($form);

    // We could pull the address from the address table, but just to
    // be sure we use the address they want, we pull it from the form.
    $street_address = NULL;
    $city = NULL;
    $state_province = NULL;
    $postal_code = NULL;
    foreach ($this->getAddressFieldsMap() as $fieldName => $formKey) {
      $value = $this->getSubmittedValue($form, $formKey);
      if ($fieldName == 'Street_Address_Field') {
        $street_address = $value;
      }
      if ($fieldName == 'City_Field') {
        $city = $value;
      }
      if ($fieldName == 'Postal_Code_Field') {
        $postal_code = $value;
      }
      if ($fieldName == 'State_Province_Field') {
        $state_province_id = $value;
        $state_province = \Civi\Api4\StateProvince::get()
          ->setCheckPermissions(FALSE)
          ->addSelect('name')
          ->addWhere('id', '=', $state_province_id)
          ->execute()->first()['name'];
      }
    }

    if ($street_address) {
      $block .= "\n" . $street_address;
    }
    if ($city || $state_province || $postal_code) {
      $block .= "\n";
      if ($city && $state_province) {
        $block .= "${city}, ${state_province}";
      }
      elseif ($city) {
        $block .= $city;
      }
      elseif ($state_province) {
        $block .= $state_province;
      }
      if ($postal_code) {
        $block .= " " . $postal_code;
      }
    }
    return $block;
  }

  /**
   * Take the signature form and send an email to the recipient.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function processSignature($form) {
    // Get the address information of the signer.
    $addressValues = [];
    foreach ($this->getAddressFieldsMap() as $fieldName => $formKey) {
      $addressValues[$fieldName] = $this->getSubmittedValue($form, $formKey);
    }

    // Get a list of officials associated with this address.
    $recipients = $this->findRecipients($addressValues);
    // Get the ones the signer chose to send to.
    $selectedRecipients = $this->getSubmittedValue($form, 'selected_officials');
    $selectedRecipients = explode(',', $selectedRecipients);

    // Create an array of contact ids to include.
    $extraContactIds = [];
    foreach ($recipients as $recipient) {
      // Make sure nobody is slipping in any recipients we should not be emailing.
      if (!in_array($recipient['ocd_id'], $selectedRecipients)) {
        continue;
      }

      // The "given name" often includes the middle name or initial.
      $given_names = explode(' ', trim($recipient['given_name']));
      // Take the first name as the first name.
      $first_name = array_shift($given_names);
      // Make the rest of the names the middle name.
      $middle_name = implode(' ', $given_names);
      $last_name = $recipient['family_name'];
      $email = $recipient['email'];
      $title = NULL;
      if (!empty($recipient['title'])) {
        $title = $recipient['title'];
      }

      $extraContactIds[] = $this->addOrRetrieveContact($email, $first_name, $last_name, $middle_name, $title);
    }
    $this->createPendingActivity($form, $extraContactIds);
    if ($this->sendEmail($form, $extraContactIds))  {
      // If all emails sent successfully complete the activity
      $this->activity->completeActivity();
    }
  }

  /**
   * Prepare the signature form to show officials to select.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function buildSigForm($form) {
    $jsVars = $this->getAddressFieldsMap();
    $jsVars['message'] = 'signer_message';

    $form->addElement('text', 'selected_officials', ts('Selected Officials', array('domain' => 'com.aghstrategies.petitionemail')));
    CRM_Core_Region::instance('form-body')->add(array(
      'template' => 'CRM/Petitionemail/Form/SelectedOfficials.tpl',
    ));

    CRM_Core_Resources::singleton()->addScriptFile('com.aghstrategies.petitionemail', 'js/signatureForm.js')
      ->addVars('petitionemail', $jsVars);

    $form->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Send', array('domain' => 'com.aghstrategies.petitionemail')),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Map address fields to their name attribute on the form.
   *
   * Find the profile fields holding the address fields so we can
   * find them in the $form->_submitValues array.
   *
   * @return array 
   */
  public function getAddressFieldsMap() {
    $return = array();
    foreach ($this->addressFields as $fieldName) {
      $ufField = $this->getPetitionValue($fieldName);
      try {
        $field = civicrm_api3('UFField', 'getsingle', array(
          'return' => array(
            'field_name',
            'location_type_id',
          ),
          'id' => $ufField,
        ));
        $locationType = 'Primary';
        if (!empty($field['location_type_id'])) {
          $locationType = $field['location_type_id'];
        }
        $return[$fieldName] = $field['field_name'] . '-' . $locationType;
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.statelegemail')));
      }
    }
    return $return;
  }

  /**
   * Find the recipients based upon postal data.
   *
   * @param array $addressValues
   *   Address parts in an array with the keys:
   *   - State_Province_Field,
   *   - City_Field,
   *   - Street_Address_Field, and
   *   - Postal_Code_Field.
   *
   * @return array
   *   The matching recipients in an array with the keys:
   *   - email,
   *   - photourl, and
   *   - name.
   */
  public function findRecipients($addressValues) {
    // Fix postal code to be precisely five digits, handling zeros.
    if (is_int($addressValues['Postal_Code_Field'])) {
      $postalCode = $addressValues['Postal_Code_Field'];
    }
    else {
      $postalCodeParts = explode('-', $addressValues['Postal_Code_Field']);
      $postalCode = intval(array_shift($postalCodeParts));
    }
    $postalCode = str_pad("{$addressValues['Postal_Code_Field']}", 5, "0", STR_PAD_LEFT);

    $limit = 0;
    $update = FALSE;
    $provider = new $this->electoralLookupClass($limit, $update);
    $adjustedAddress = [
      'street_address' => $addressvalues['Street_Address_Field'],
      'city' => $addressValues['City_Field'],
      'state_province_id' => $addressValues['State_Province_Field'],
      'postal_code' => $postalCode,
    ];
    $provider->setAddress($adjustedAddress);
    $response = $provider->lookup();

    foreach ($response['official'] as $official) {
      $return[] = [
        'email' => $official->getEmailAddress(),
        'photourl' => $official->getImageUrl(),
        'name' => $official->getName(),
        'family_name' => $official->getFirstName(),
        'given_name' => $official->getLastName(),
        'ocd_id' => $official->getOcdId(),
        'greeting' => '',
        'title' => $official->getTitle(),
      ];
    }
    
    return $return;
  }




}
