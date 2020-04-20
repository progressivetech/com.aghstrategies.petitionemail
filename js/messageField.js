CRM.$(function($) {
  var msgFieldSelector = '#customData .custom-group-Letter_To input[data-crm-custom="Letter_To:Message_Field"]';
  var $messageField = $(msgFieldSelector);
  var initMessageField = function(m) {
    $messageField.attr({
      placeholder: '- Select Field -',
      allowClear: 'true',
    });
    createEntityRef(m, $('#profile_id').val());
  };
  initMessageField($messageField);

  // 4.6 doesn't trigger crmLoad while 4.7 loads the custom data after this runs
  $('body').on('crmLoad', function() {
    $messageField = $(msgFieldSelector);
    initMessageField($messageField);
  });

  $('#profile_id').change( function() {
    $messageField.crmEntityRef('destroy');
    $messageField.val('');
    createEntityRef($messageField, $('#profile_id').val());
  });

  function createEntityRef($field, profileId) {
    var noteFields = ['activity_details'];
    CRM.api3('CustomGroup', 'get', {
      sequential: 1,
      extends: 'Activity',
      'api.CustomField.get': {
        data_type: 'Memo',
        return: 'name'
      }
    }).done(function(result) {
      result['values'].forEach(function(val) {
        val['api.CustomField.get']['values'].forEach(function(field) {
          //noteFields.push(field.name);
          noteFields.push('custom_' + field.id);
        });
      });
      $field.crmEntityRef({
        entity: 'UFField',
        placeholder: '- Select Field -',
        api: {
          params: {
            uf_group_id: profileId,
            field_name: {"IN": noteFields}
          }
        },
        select: {minimumInputLength: 0},
      });
    });
  }
});
