============================
Membership Card Creator
============================

This extension adds an ability to generate membership cards
- Cover letter is defined as message template. Supports contact and membership tokens. 
- It automatically prints the membership card for the secondary contact


Configuration
-------------
For now you will need to update constants defined in CRM/Membershipcard/BAO/Card.php
RELATIONSHIP_TYPE_FOR_INHERITANCE
NAME_BADGE_FOR_MEMBERSHIP_CARD
MEMBERSHIP_CARD_MESSAGE_TEMPLATE_ID


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
