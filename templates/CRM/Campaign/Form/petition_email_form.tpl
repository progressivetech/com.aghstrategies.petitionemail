<!-- TODO: Replace tpl_Vars with smarty -->

<tr class="crm-campaign-survey-form-block-email_petition">
  <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['email_petition']['label'] . $help_code . '</td>
  <td>'.$rendererval->_tpl->_tpl_vars['form']['email_petition']['html'].'
    <div class="description">'.ts('Should signatures generate an email to the petition\'s  target?') .'</div>
  </td>
</tr>
<tr class="crm-campaign-survey-form-block-recipient_name">
<td class="label">'. $rendererval->_tpl->_tpl_vars['form']['recipient_name']['label'] .  '</td>
  <td>'.$rendererval->_tpl->_tpl_vars['form']['recipient_name']['html'].'
    <div class="description">'.ts('Enter the target\'s name (as he or she should see it) here.').'</div>
  </td>
</tr>
<tr class="crm-campaign-survey-form-block-recipient">
  <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['recipient']['label'] .'</td>
  <td>'.$rendererval->_tpl->_tpl_vars['form']['recipient']['html'].'
    <div class="description">'.ts('Enter the target\'s email address here.').'</div>
  </td>
</tr>
<tr class="crm-campaign-survey-form-block-user_message">
  <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['user_message']['label'] .'</td>
  <td>'.$rendererval->_tpl->_tpl_vars['form']['user_message']['html'].'
    <div class="description">'.ts('Select a field that will have the signer\'s custom message.  Make sure it is included in the Activity Profile you selected.').'</div>
  </td>
</tr>
<tr class="crm-campaign-survey-form-block-default_message">
  <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['default_message']['label'] .'</td>
  <td>'.$rendererval->_tpl->_tpl_vars['form']['default_message']['html'].'
    <div class="description">'.ts('Enter the default message to be included in the email.').'</div>
  </td>
</tr>
<tr class="crm-campaign-survey-form-block-subjectline">
  <td class="label">'. $rendererval->_tpl->_tpl_vars['form']['subjectline']['label'] .'</td>
  <td>'.$rendererval->_tpl->_tpl_vars['form']['subjectline']['html'].'
    <div class="description">'.ts('Enter the subject line to be included in the email.').'</div>
  </td>
</tr>
