============================
Membership Card Creator
============================

This extension adds an ability to generate membership cards
- Cover letter is defined as message template. Supports contact and membership tokens. 
- It automatically prints the membership card for the secondary contact
- Membership card is formatted to print cover letter and membership cards at the bottom
- Single membership card per page.


Configuration
-------------
1. Create a label format: <your site name>/civicrm/admin/labelFormats?reset=1
2. Create membership cover letter: <your site name>/civicrm/admin/messageTemplates?reset=1
3. Create a relationship type and update membership type to define inheritance. This is an optional step and can be skipped if you don't have membership inherited via relationship.

Once you done above update the values for the constants defined in CRM/Membershipcard/BAO/Card.php
- RELATIONSHIP_TYPE_FOR_INHERITANCE
- NAME_BADGE_FOR_MEMBERSHIP_CARD
- MEMBERSHIP_CARD_MESSAGE_TEMPLATE_ID


Instructions
------------
1. Find Memberships
2. Select "Primary Members": Yes and Search
3. Select the membership > Actions > "Generate membership cards"
4. Download PDF


TO FIX
------
- Move constants to configurable options
- automatically create default label format, message template
