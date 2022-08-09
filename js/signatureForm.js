/**
 * Copyright (C) 2016, AGH Strategies, LLC <info@aghstrategies.com>
 * Licensed under the GNU Affero Public License 3.0 (see LICENSE.txt)
 */

CRM.$(function($) {
 
  var getRecips = function() {
    // Setup variables to hold display of available officials.
    var officialsLabel = $('<div/>', {
      class: 'label',
      html: '<label>Recipients</label>',
    });
    var officialsList = $('<div/>', {
      class: 'content',
      html: '<div class="messages status no-popup">Please enter a complete address to lookup your elected officials.</div>',
    });
    var clearDiv = $('<div/>', {
      class: 'clear',
    });

    // We only run if we have a complete address.
    var addressComplete = false;
    var zipComplete = false;
    var zipval = $(zip).val();
    if (zipval.length == 5 && $.isNumeric(zipval)) {
      zipComplete = true;
    }
    else if (
      zipval.length == 10 &&
      $.isNumeric(zipval.substring(0,5)) &&
      zipval.substring(5,6) == '-' &&
      $.isNumeric(zipval.substring(6))
    ) {
      zipComplete = true;
    }

    if (zipComplete && 
      $(stateProvince).val().length &&
      $(city).val().length &&
      $(address).val().length
    ) {
      // We have a complete address.
      var url = new URL(location.href);
      var surveyId = parseInt(url.searchParams.get("sid"));

      $.getJSON(CRM.url('civicrm/petitionemail/ajax/recipients'),
        {
          zip: $(zip).val().substring(0,5),
          state: $(stateProvince).val(),
          city: $(city).val(),
          address: $(address).val(),
          surveyId: surveyId,
        },
        function(data) {
          var officialsCount = 0;
          officialsList.html('Your message will be delivered to the following legislators: ');
          $.each(data, function(index, value) {
            var officialCheckbox = $('<input/>', {
              type: 'checkbox',
              class: 'crm-form-checkbox',
              value: value.id,
              name: 'selected-officials[]',
              id: 'selected-officials-' + value.id
            });
            officialCheckbox.prop('checked', true);
            var officialRow = $('<div/>', {
              class: 'statelegemail-row',
              html: ' ',
            }).prepend(officialCheckbox).append($('<label/>', {
              for: 'select-officials-' + value.id,
              html: value.name,
            }));
            officialsList.append(officialRow);
            officialsCount++;

          });
          if (officialsCount > 0) {
            $('#_qf_Signature_next-bottom').html('Send');
            $('#' + CRM.vars.petitionemail.message).prop('disabled', false);
            $('#signer_subject').prop('disabled', false);
          }
          else {
            officialsList.html('<div class="messages status no-popup">Failed to locate elected officials that can be contacted based on your address. Your elected officials may not have public email addresses. But you can still sign the petition to register your opinion.</div>');
            $('#_qf_Signature_next-bottom').html('Sign');
            $('#' + CRM.vars.petitionemail.message).prop('disabled', true);
            $('#signer_subject').prop('disabled', true);
          }
        
        }
      ); 
    }
    recipients.html([officialsLabel, officialsList, clearDiv]);
  }

  var recipients = $('<div/>', {
    id: 'legislator-list',
    class: 'crm-section',
  });

  $('.crm-petition-contact-profile').after(recipients);
  var zip = document.getElementById(CRM.vars.petitionemail.Postal_Code_Field);
  var stateProvince = document.getElementById(CRM.vars.petitionemail.State_Province_Field);
  var city = document.getElementById(CRM.vars.petitionemail.City_Field);
  var address = document.getElementById(CRM.vars.petitionemail.Street_Address_Field);

  getRecips();
  $(zip).keyup(getRecips);
  $(city).keyup(getRecips);
  $(address).keyup(getRecips);
  $(stateProvince).change(getRecips);

  // $('.send_cc-section').appendTo($('.crm-petition-activity-profile'));

});
