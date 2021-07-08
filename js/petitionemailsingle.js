(function($){
  // Handle display of recipient email options.
  // Get the field controlling which option to use.
  CRM.api3('CustomField', 'getsingle', {
    "return": ["id"],
    "name": "Email_Recipient_System"
  }).done(function(recipientSystemField) {
    // Set based on initial value.
    update_single_recipient_display(recipientSystemField.id);
    // Update when the value changes.
    CRM.$("select[id*='custom_" + recipientSystemField.id + "']").change(function() {;
      update_single_recipient_display(recipientSystemField.id);
    });
  });
})(CRM.$);

function update_single_recipient_display(fieldId){
  // Update display based on selected value.
  CRM.api3('CustomField', 'get', {
      "sequential": 1,
      "return": ["id"],
      "name": {'IN': ['Recipient_Name', 'Recipient_Email']},
  }).done(function(recipientFields) {
    // Iterate over both the name and email fields.
    var selectedRecipientSystem = CRM.$("select[id*='custom_" + fieldId + "'] option:selected").val();
    if (selectedRecipientSystem == 'Single') {
      CRM.$(recipientFields.values).each(function(){
        CRM.$("tr[class*='custom_" + this.id + "']").show();
      });
    }
    else {
      CRM.$(recipientFields.values).each(function(){
        CRM.$("tr[class*='custom_" + this.id + "']").hide();
      });
    }
  });
}
