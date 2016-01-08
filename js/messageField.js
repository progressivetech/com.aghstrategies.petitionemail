CRM.$(function($) {
  var $messageField = $('#customData .custom-group-Letter_To input[data-crm-custom="Letter_To:Message_Field"]');
  $messageField.attr({
    placeholder: '- Select Field -',
    allowClear: 'true',
  });
  createEntityRef($messageField, $('#profile_id').val());

  $('#profile_id').change( function() {
    $messageField.crmEntityRef('destroy');
    $messageField.val('');
    createEntityRef($messageField, $('#profile_id').val());
  });

  function createEntityRef($field, profileId) {
    noteFields = getNoteFields();

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
  }

  function getNoteFields() {
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
          noteFields.push(field.name);
        });
      });
      return noteFields;
    });
  }
});
