# Petition Email

**NOTE**: This extension has been signficantly re-written from the original.

If you are upgrading, please see the upgrade notes below. Manual intervention
will be necessary.

## About

This extension allows the easy setup of automatic emails to decision-makers
upon signing a CiviCRM petition.

It uses the CiviCRM outbound email system, which makes it configurable, but for
obvious reasons, deliverability is not guaranteed and it only works for targets
that have an email address.

## Usage

When creating or editing a petition, you can expand a section at the bottom of
the petition form and choose from a list of "Email Recipient Systems".

## Static targets

This extension provides the "Static" system, allowing you to specify one or
more targets that will receive an email everytime the petition is signed,
regardless of the signer's address. The "Static" recipient system is useful
when targeting a governor, mayor or CEO of a company.

Each target must be a contact in your database (you can configure the target
with DO NOT EMAIL to avoid accidentally sending them your organizational email
messages while still enabling them to receive petition email messages). You can
also choose yourself as a BCC to receive an email everytime someone fills out
your petition.

## Dynamic targets

The [Electoral
extension](https://github.com/progressivetech/com.jlacey.electoral) provides a
means for looking up elected officials based on address using several different
commercial and non-profit APIS. If the electoral extension is installed,
petitionemail will allow you to use any of the configured APIs to dynamically
lookup an elected official based on the address of the petition signer.

## Default message and subject

Lastly, you can indicate a default email subject and message.

When someone fills out the petition, they'll see a text box populated with your
default message and default subject. If they don't touch it or if they delete
it entirely, the defaults will be sent. Otherwise, the message they type will
be sent.  

The email will be sent with the signer's name in the email messages from field,
but your organizational address (sending from the signer's email address will
guarantee that the message goes straight to the spam box).

The signer's name and email will appear in the body of the message and the
signer's email address will be set in the Reply-To header so if the target hits
reply, it will go to the signer.

## Upgrade

There are number of signficant changes from the original version of this
extension, some of which require manual intervention.

 * Both Subject and Message fields for signers are automatically provided and
   no longer need to be configured. This means, for most petitions, there is no
   longer a need for an activity profile to be specified **and if one is
   specified and it includes a custom message field, there will be two custom
   message fields on the peition.**

   To work around this problem, an Api is provided that automatically removes the
   activity profile from all petitions using this extension. You can run it with:

   `cv --user=admin api4 PetitionEmailUtilities.RemoveActivityProfiles`

   Or, via the web based Api4 page (/civicrm/api4).

   Or you can simply update your petitions by hand.

 * The Recipient name and email fields have been removed. **You must manually
   add these targets to your database and select them using the new contact
   reference fields.**

   You can automate this process via the API;

   `cv --user=admin api4 PetitionEmailUtilities.FixTargets`

   Or, via the web based Api4 page (/civicrm/api4).

   Or you can simply update your petitions by hand.

 * When ever a petition is signed and an email is sent, an activity
   representing the email is added with both the signer and the target so you
   have a complete record of which target was sent which email.

 * A sender identification block and a salutation is prepended to each email
   sent to correctly identify the sender and the recipient. Default email
   messages should no longer contain a salutation.

 * To avoid fraud detection, the from email address used to send all email is
   configurable and never uses the signer's from address. But the signer's from
   address is set in the Reply-To header and included in the body of the
   message.

 * A BCC option is available to get a copy of all email sent.

 * The code has been refactored to make it easier to maintain (using managed
   entities) and easier to add new recipient systems.

