CRM.$(function($) {
  var msgFieldSelector = 'input[data-crm-custom="Letter_To:Message_Field"]';
  var subFieldSelector = 'input[data-crm-custom="Letter_To:Subject_Field"]';
  var recipientSystemFieldSelector = 'select[data-crm-custom="Letter_To:Recipient_System"]';

  // This field is populated on load.
  var recipientSystemField = null;
  var singleFields = [ 
    'To', 
  ]; 

  var initEntityFieldDropDown = function(f, dataType) {
    f.attr({
      placeholder: '- Select Field -',
      allowClear: 'true',
    });

    // Create a variable of all possible fields.
    var fields = [];
    if (dataType == 'Memo') {
      fields.push('activity_details');
    }
    else if (dataType == 'String') {
      fields.push ('activity_subject');
    }

    // Get a list of custom field groups that extend activities. 
    CRM.api3('CustomGroup', 'get', {
      sequential: 1,
      extends: 'Activity',
      'api.CustomField.get': {
        data_type: dataType,
        return: 'name'
      }
    }).done(function(result) {
      console.log(result);
      result['values'].forEach(function(val) {
        val['api.CustomField.get']['values'].forEach(function(field) {
          fields.push('custom_' + field.id);
        });
      });
      console.log("Fields are", fields);
      f.crmEntityRef({
        entity: 'UFField',
        placeholder: '- Select Field -',
        api: {
          params: {
            uf_group_id: CRM.$('#profile_id').val(),
            field_name: {"IN": fields}
          }
        },
        select: {minimumInputLength: 0},
      });
    });
  };

  var displaySingleFields = function() {
    var field;
    for(var key in singleFields) {
      field = singleFields[key];
      CRM.$('input[data-crm-custom="Letter_To:' + field + '"]').parent().parent().show();
    }
  };

  var hideSingleFields = function () {
    var field;
    for(var key in singleFields) {
      field = singleFields[key];
      CRM.$('input[data-crm-custom="Letter_To:' + field + '"]').parent().parent().hide();
    }
  };

  var toggleSingleFields = function () {
    if (recipientSystemField.val() == 'Single') {
      displaySingleFields();
    }
    else {
      hideSingleFields();
    }
  };
  var createEntityRef = function(field, profileId, dateType) {
    
  };

  CRM.$('body').on('crmLoad', function() {
    recipientSystemField = CRM.$(recipientSystemFieldSelector);
    messageField = CRM.$(msgFieldSelector);
    subjectField = CRM.$(subFieldSelector);
    initEntityFieldDropDown(messageField, 'Memo');
    initEntityFieldDropDown(subjectField, 'String');
    toggleSingleFields();
    recipientSystemField.change( function() {
      toggleSingleFields();
      
    });
    CRM.$('#profile_id').change( function() {
      var messageField = CRM.$(msgFieldSelector);
      messageField.val('');
      createEntityRef(messageField, CRM.$('#profile_id').val());
    });
  });

  

  
});
