{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="petitionemail_custom" style="visibility: hidden">
  <table>
    <tr class="custom_field-row" id="petitionemail_to_email_id" style="visibility: hidden;">
      <td class="label">{$form.to_email_id.label}</td>
      <td class="html-adjust">{$form.to_email_id.html}</td>
    </tr>
    <tr class="custom_field-row" id="petitionemail_cc_email_id" style="visibility: hidden;">
      <td class="label">{$form.cc_email_id.label}</td>
      <td class="html-adjust">{$form.cc_email_id.html}</td>
    </tr>
    <tr class="custom_field-row" id="petitionemail_bcc_email_id" style="visibility: hidden;">
      <td class="label">{$form.bcc_email_id.label}</td>
      <td class="html-adjust">{$form.bcc_email_id.html}</td>
    </tr>
  </table>
</div>

{literal}
  <script type="text/javascript">
    CRM.$(function($) {

      // Configure custom data for Petition config
      $(document).ajaxComplete(function (event, xhr, settings) {
        if ((settings.url.indexOf('custom') > -1) && (settings.url.indexOf('type=Survey') > -1)) {
          CRM.$('tr#petitionemail_to_email_id').insertAfter(CRM.$('div.custom-group-Letter_To div.crm-accordion-body table.form-layout-compressed tbody tr:nth-child(2)'));
          CRM.$('tr#petitionemail_cc_email_id').insertAfter(CRM.$('div.custom-group-Letter_To div.crm-accordion-body table.form-layout-compressed tbody tr:nth-child(6)'));
          CRM.$('tr#petitionemail_bcc_email_id').insertAfter(CRM.$('div.custom-group-Letter_To div.crm-accordion-body table.form-layout-compressed tbody tr:nth-child(10)'));
          CRM.$('tr#petitionemail_to_email_id').css('visibility', 'visible');
          CRM.$('tr#petitionemail_cc_email_id').css('visibility', 'visible');
          CRM.$('tr#petitionemail_bcc_email_id').css('visibility', 'visible');
          CRM.$('input[data-crm-custom="Letter_To:To"]').parent().parent().hide();
          CRM.$('input[data-crm-custom="Letter_To:CC"]').parent().parent().hide();
          CRM.$('input[data-crm-custom="Letter_To:BCC"]').parent().parent().hide();
          CRM.$('tr#petitionemail_to_email_id').before('<tr><td></td><td class="description"><br/>' + ts('You can select emails from the database or enter manually as a comma-separated list') + '</td></tr>');
          CRM.$('tr#petitionemail_to_email_id').before('<tr><td></td><td class="description">' + ts('To emails:') + '</td></tr>');
          CRM.$('tr#petitionemail_cc_email_id').before('<tr><td></td><td class="description"><br/>' + ts('CC emails:') + '</td></tr>');
          CRM.$('tr#petitionemail_bcc_email_id').before('<tr><td></td><td class="description"><br/>' + ts('BCC emails:') + '</td></tr>');
          CRM.$('input[data-crm-custom="Letter_To:BCC_Email"]').parent().append('<br/><br/>');
        }
      });
    });
  </script>
{/literal}
