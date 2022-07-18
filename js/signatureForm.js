/**
 * Copyright (C) 2016, AGH Strategies, LLC <info@aghstrategies.com>
 * Licensed under the GNU Affero Public License 3.0 (see LICENSE.txt)
 */

CRM.$(function($) {
  var initialSelectedOfficials = $('#selected_officials').val().split(',');
  if (initialSelectedOfficials.length == 1 && initialSelectedOfficials[0] == '') {
    initialSelectedOfficials = [];
  }
  $('#selected_officials').hide();
  var legList = $('<div/>', {
    id: 'legislator-list',
    class: 'crm-section',
  });
  var greeting = $('<div/>', {
    id: 'legislator-greeting',
    class: 'crm-section',
  });
  var legCheck = function() {
    var selectedOfficials = [];
    maxLength = 0;
    greeting.children().hide();
    $('input[name="select-officials"]:checked').each(function() {
      var thisLegId = $(this).val();
      selectedOfficials.push(thisLegId);
      greeting.children('[statelegemail-leg-id="' + thisLegId + '"]').show();
    });
    $('#selected_officials').val(selectedOfficials.join(','));
  }
  var getRecips = function() {
    var firstRun = (initialSelectedOfficials.length > 0);
    var zipval = $(zip).val();
    if (!$(stateProvince).val().length
      || !$(city).val().length
      || !$(address).val().length) {
      legList.html('');
      return;
    }

    var url = new URL(location.href);
    var surveyId = parseInt(url.searchParams.get("sid"));

    if ((zipval.length == 5 && $.isNumeric(zipval))
      || (zipval.length == 10
        && $.isNumeric(zipval.substring(0,5))
        && zipval.substring(5,6) == '-'
        && $.isNumeric(zipval.substring(6)))) {
      $.getJSON(CRM.url('civicrm/petitionemail/ajax/recipients'),
        {
          zip: $(zip).val().substring(0,5),
          state: $(stateProvince).val(),
          city: $(city).val(),
          address: $(address).val(),
          surveyId: surveyId,
        },
        function(data) {
          var legLabel = $('<div/>', {
            class: 'label',
            html: '<label>Recipients</label>',
          });
          var legContent = $('<div/>', {
            class: 'content',
            html: 'Your letter will be delivered to the following legislators: ',
          });
          var legClear = $('<div/>', {
            class: 'clear',
          });
          var legCount = 0;
          greeting.html('');
          $.each(data, function(index, value) {
            var legCheckBox = $('<input/>', {
              type: 'checkbox',
              class: 'crm-form-checkbox',
              value: value.leg_id,
              name: 'select-officials',
              id: 'select-officials-' + value.leg_id,
              change: legCheck,
            });
            if (firstRun) {
              var boxIndex = initialSelectedOfficials.indexOf(value.leg_id);
              if (boxIndex >= 0) {
                legCheckBox.prop('checked', true);
                initialSelectedOfficials.splice(boxIndex, 1);
              } else {
                legCheckBox.prop('checked', false);
              }
            } else {
              legCheckBox.prop('checked', false);
            }
            var legRow = $('<div/>', {
              class: 'statelegemail-paper-row',
              html: ' ',
            }).prepend(legCheckBox).append($('<label/>', {
              for: 'select-officials-' + value.leg_id,
              html: value.name,
            }));
            legContent.append(legRow);
            var greetingRow = $('<div/>', {
              html: value.greeting,
              'statelegemail-leg-id': value.leg_id,
            });
            if (legCheckBox.prop('checked')) {
              greetingRow.show();
            }
            else {
              greetingRow.hide();
            }
            if (data.length == 1) {
              legCheckBox.prop('checked', true);
              legCheckBox.attr("disabled", true);
            }
            greeting.append(greetingRow);
            legCount++;
          });
          if (legCount) {
            legList.html([legLabel, legContent, legClear]);
          }
        }
      );
    } else {
      legList.html('');
      greeting.html('');
    }
  }

  var messageField = document.getElementById(CRM.vars.petitionemail.message);
  $('.crm-petition-contact-profile').after(legList);
  $(messageField).before(greeting);
  var zip = document.getElementById(CRM.vars.petitionemail.Postal_Code_Field);
  var stateProvince = document.getElementById(CRM.vars.petitionemail.State_Province_Field);
  var city = document.getElementById(CRM.vars.petitionemail.City_Field);
  var address = document.getElementById(CRM.vars.petitionemail.Street_Address_Field);

  getRecips();
  $(zip).keyup(getRecips);
  $(city).keyup(getRecips);
  $(address).keyup(getRecips);
  $(stateProvince).change(getRecips);

  $('.send_cc-section').appendTo($('.crm-petition-activity-profile'));

});
