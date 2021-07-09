CRM.$(function($) {
  var selectors = ['#customData .custom-group-Support_Message input[data-crm-custom="Support_Message:Support_Message_Field"]',
                   '#customData .custom-group-Support_Message input[data-crm-custom="Support_Message:Support_Subject_Field"]'];
  $(selectors).each(function(index,fieldSelector) {
    var $field = $(fieldSelector);
    var initField = function(m) {
      $field.attr({
        placeholder: '- Select Field -',
        allowClear: 'true',
      });
      createEntityRef(m, $('#profile_id').val());
    };
    initField($field);

    // 4.6 doesn't trigger crmLoad while 4.7 loads the custom data after this runs
    $('body').on('crmLoad', function() {
      $field = $(fieldSelector);
      initField($field);
    });

    $('#profile_id').change( function() {
      $field.crmEntityRef('destroy');
      $field.val('');
      createEntityRef($field, $('#profile_id').val());
    });
  });

  function createEntityRef($field, profileId) {
    $field.crmEntityRef({
      entity: 'UFField',
      placeholder: '- Select Field -',
      api: {
        params: {
          uf_group_id: profileId,
        }
      },
      select: {minimumInputLength: 0},
    });
  }
});
