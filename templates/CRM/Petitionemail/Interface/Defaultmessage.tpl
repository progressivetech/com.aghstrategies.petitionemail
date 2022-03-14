{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-section editrow_signer_message-section form-item" id="petitionemail_signer_message">
  <div class="label">{$form.signer_subject.label}</div>
  <div class="content">{$form.signer_subject.html}</div>
  <div class="clear"></div>
  <div class="label">{$form.signer_message.label}</div>
  <div class="content">{$form.signer_message.html}</div>
  <div class="clear"></div>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $(document).ready(function() {
      CRM.$('div#petitionemail_signer_subject').appendTo('div.crm-petition-activity-profile');
      CRM.$('div#petitionemail_signer_message').appendTo('div.crm-petition-activity-profile');
    });
  });
</script>
{/literal}
