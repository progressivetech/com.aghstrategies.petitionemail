/**
 * Copyright (C) 2016, AGH Strategies, LLC <info@aghstrategies.com>
 * Licensed under the GNU Affero Public License 3.0 (see LICENSE.txt)
 */

CRM.$(function($) {
  var addressFields = [
    'Street_Address',
    'City',
    'State_Province',
    'Postal_Code',
  ];
  var recipientSystemFieldSelector = 'select[data-crm-custom="Letter_To:Recipient_System"]';
  // The field var gets populated on load below.
  var recipientSystemField = null;

  // The list of recipient systems that require an address.
  var postalAddressRecipientSystems = [ 
    'OpenstatesUpper',
    'OpenstatesLower',
    'OpenstatesBoth',
    'OpenstatesNationalHouse',
    'GoogleUpper',
    'GoogleLower',
    'GoogleBoth',
    'GoogleNationalHouse',
    'GoogleCity',
    'CiceroUpper',
    'CiceroLower',
    'CiceroBoth',
    'CiceroNationalHouse',
    'CiceroCity',
  ];

  var createEntityRef = function(field, profileId, fieldName) {
    field.crmEntityRef({
      entity: 'UFField',
      placeholder: '- Select Field -',
      api: {
        params: {
          uf_group_id: profileId,
          field_name: fieldName,
        },
      },
      allowClear: true,
      select: {minimumInputLength: 0},
    });
  };

  var displayAddressFields = function() {
    var field;
    for(var key in addressFields) {
      field = addressFields[key];
      var el = CRM.$('input[data-crm-custom="Letter_To:' + field + '_Field"]');
      el.parent().parent().show();
      el.attr({
        placeholder: '- Select Field -',
        allowClear: 'true',
      });
      createEntityRef(el, $('#contact_profile_id').val(), field.toLowerCase());
    }
  };

  var hideAddressFields = function () {
    var field;
    for(var key in addressFields) {
      field = addressFields[key];
      var el = CRM.$('input[data-crm-custom="Letter_To:' + field + '_Field"]');
      el.parent().parent().hide();
      el.crmEntityRef('destroy');
      el.val('');
    }
  };


  var toggleAddressFields = function() {
    if (postalAddressRecipientSystems.includes(recipientSystemField.val())) {
      displayAddressFields();
    }
    else {
      hideAddressFields();
    }
  };
  $('body').on('crmLoad', function() {
    recipientSystemField = CRM.$(recipientSystemFieldSelector);
    toggleAddressFields();

    recipientSystemField.change( function() {
      toggleAddressFields();
    }); 

    CRM.$('#contact_profile_id').change(function() {
      if (postalAddressRecipientSystems.includes(recipientSystemField.val())) {
        hideAddressFields();
        displayAddressFields();
      }
    });
  });

  


});
