<?php
  
namespace Civi\Api4\Action\PetitionEmailUtilities;
use CRM_PetitionEmail_ExtensionUtil as E;

/**
 *
 * Fix Targets
 *
 * Previous versions of this extenion required the target to be
 * specified via to text fields - recipeint_name and recipient_email.
 *
 * An upgrade changed that to using contact reference fields.
 *
 * This action updates from the old to the new version automatically.
 *
 * Use with caution! It adds contacts to your database. 
 *
 */
class FixTargets extends \Civi\Api4\Generic\AbstractAction {
 
  public function _run(\Civi\Api4\Generic\Result $result) {
    // Get the activity_type_id for petitions.
    $petitionActivityTypeId = \Civi\Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('name', '=', 'Petition')
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->execute()->first()['value'];
    
    $petitions = \Civi\Api4\Survey::get()
      ->addSelect('id', 'Letter_To.Recipient_Name', 'Letter_To.Recipient_Email')
      ->addWhere('activity_type_id:name', '=', 'Petition' )
      ->addWhere('Letter_To.Recipient_System', '=', 'Single')
      ->addWhere('Letter_To.Recipient_Email', 'IS NOT EMPTY')
      // ->addWhere('is_active', '=', TRUE)
      ->execute();
    foreach($petitions as $petition) {
      $id = $petition['id'];
      $name = trim($petition['Letter_To.Recipient_Name']);
      $email = trim($petition['Letter_To.Recipient_Email']);

      // We match on just the email. If there's already a match we don't have
      // to create a new contact.
      $contactId = \Civi\Api4\Email::get()
        ->addSelect('contact_id')
        ->addWhere('email', '=', $email)
        ->addWhere('contact_id.is_deleted', '=', FALSE)
        ->execute()->first()['contact_id'];

      if (!$contactId) {
        $contact = $this->parseName($name);
        $contact['email'] = $email;
        $contactId = $this->addContact($contact);
      }
      $this->updatePetition($id, $contactId);
      $count++;
    }
    $result[] = [ E::ts('%1 petitions were updated', [ 1 => $count ]) ];
  }

  private function updatePetition($id, $contactId) {
    \Civi\Api4\Survey::update()
      ->addWhere('id', '=', $id)
      ->addValue('Letter_To.To', $contactId)
      ->execute();
  }

  private function addContact($contact) {
    return \Civi\Api4\Contact::create()
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', $contact['first_name'])
      ->addValue('last_name', $contact['last_name'])
      ->addValue('prefix_id:name', $contact['prefix'])
      ->addvalue('title', $contact['title'])
      ->addValue('do_not_email', TRUE)
      ->addChain('email_chain', \Civi\Api4\Email::create()->addValue('contact_id', '$id')->addValue('email', $contact['email']))
      ->execute()->first()['id'];
  }
  private function parseName($name) {
    // Parse the name. This is hard.
    $title = NULL;
    $prefix = NULL;
    $firstName = NULL;
    $lastname = NULL;

    // Debug... keep copy of original name.
    $nameOrig = $name;
    // For the name, first check for a comma. If we find one, we assume the
    // name is in the format: name, title
    $nameCommaParts = explode(',', $name);
    $title = NULL;
    if (count($nameCommaParts) > 1) {
      $title = array_pop($nameCommaParts);
      // Put the rest back together.
      $name = implode(' ', $nameCommaParts);
    }

    $nameParts = explode(' ', $name);
    $lastName = array_pop($nameParts);

    // What is left of the name? Let's first see if the first part
    // is an existing prefix.
    $prefix = NULL;
    $prefixResults = \Civi\Api4\OptionValue::get()
      ->addWhere('option_group_id:name', '=', 'individual_prefix')
      ->addWhere('name', '=', trim($nameParts[0]))
      ->execute();
    if ($prefixResults->count() > 0) {
      // We have a match for prefix.
      $prefix = array_shift($nameParts);
    }
    $firstName = implode(' ', $nameParts);
    return [
      'first_name' => $firstName,
      'last_name' => $lastName,
      'prefix' => $prefix,
      'title' => $title,
    ];
  }
}
