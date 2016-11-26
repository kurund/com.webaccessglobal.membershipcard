<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Class CRM_Membershipcard_BAO_Card.
 *
 * parent class for building name membership cards
 */
class CRM_Membershipcard_BAO_Card {

  /**
   * @var bool
   */
  public $debug = FALSE;

  /**
   * @var int
   */
  public $border = 0;

  /**
   * @var string relationship that determines the secondary contact
   */
  const RELATIONSHIP_TYPE_FOR_INHERITANCE = 'Provides Membership To';

  /**
   * @var string name badge used for membership card
   */
  const NAME_BADGE_FOR_MEMBERSHIP_CARD = 'Membership Card';

  /**
   * @var int message template used in membership card
   */
  const MEMBERSHIP_CARD_MESSAGE_TEMPLATE_ID = 80;

  /**
   *  This function is called to create membership card pdf.
   *
   * @param array $memberships
   *   Associated array with membership info.
   * @param array $layoutInfo
   *   Associated array which contains meta data about format/layout.
   */
  public function createLabels(&$memberships, &$layoutInfo) {
    $this->pdf = new CRM_Utils_PDF_Label($layoutInfo['format'], 'mm');
    $this->pdf->Open();
    $this->pdf->setPrintHeader(FALSE);
    $this->pdf->setPrintFooter(FALSE);
    $this->pdf->SetGenerator($this, "labelCreator");

    // this is very useful for debugging, by default set to FALSE
    if ($this->debug) {
      $this->border = "LTRB";
    }

    foreach ($memberships as $membership) {
      $formattedRow = self::formatLabel($membership, $layoutInfo);
      $this->pdf->AddPage();
      $this->pdf->AddPdfLabel($formattedRow);
    }

    $this->pdf->Output('MembershipCards.pdf', 'D');
    CRM_Utils_System::civiExit(1);
  }

  /**
   * Function to create structure and add meta data according to layout.
   *
   * @param array $row
   *   Row element that needs to be formatted.
   * @param array $layout
   *   Layout meta data.
   *
   * @return array
   *   row with meta data
   */
  public static function formatLabel(&$row, &$layout) {
    $formattedRow = array('labelFormat' => $layout['format']);
    $formattedRow['values'] = $row;
    return $formattedRow;
  }

  /**
   * Function to create / place labels on the PDF document
   *
   * @param $formattedRow
   */
  public function labelCreator($formattedRow) {
    $this->pdf->SetFont('helvetica', '', '8');
    $this->pdf->writeHTMLCell(115, 140, 20, 50, $formattedRow['values']['message']);

    $this->pdf->SetFont('helvetica', '', '12');
    $this->pdf->writeHTMLCell(80, 7, 21, 243, $formattedRow['values']['primary_first_name'] . ' ' . $formattedRow['values']['primary_last_name']);
    $this->pdf->writeHTMLCell(27, 7, 21, 253, $formattedRow['values']['primary_membership_id']);
    $this->pdf->writeHTMLCell(30, 7, 65, 253, $formattedRow['values']['membership_end_date']);

    $this->pdf->writeHTMLCell(80, 7, 121, 243, $formattedRow['values']['other_first_name'] . ' ' . $formattedRow['values']['other_last_name']);
    $this->pdf->writeHTMLCell(27, 7, 121, 253, $formattedRow['values']['other_membership_id']);
    $this->pdf->writeHTMLCell(30, 7, 164, 253, $formattedRow['values']['other_membership_end_date']);
  }

  /**
   * Build cards parameters before actually creating badges.
   *
   * @param array $params
   *   Associated array of submitted values.
   * @param array $membershipIDs
   */
  public static function buildCards(&$params, &$membershipIDs) {
    // get name membership card layout info
    $formatProperties = CRM_Core_OptionGroup::getValue('name_badge', self::NAME_BADGE_FOR_MEMBERSHIP_CARD, 'name');
    $layoutInfo['format'] = json_decode($formatProperties, TRUE);

    // get the membership data and sent it to the card creator
    $rows = array();

    $params = array('id' => self::MEMBERSHIP_CARD_MESSAGE_TEMPLATE_ID);
    $defaults = array();
    CRM_Core_BAO_MessageTemplate::retrieve($params, $defaults);

    $message = $defaults['msg_html'];

    $messageToken = CRM_Utils_Token::getTokens($message);
    $memberships = CRM_Utils_Token::getMembershipTokenDetails($membershipIDs);
    $html = array();

    foreach ($membershipIDs as $membershipID) {
      $membership = $memberships[$membershipID];

      // get primary contact information
      $contactId = $membership['contact_id'];
      $params = array('contact_id' => $contactId);
      list($contacts) = CRM_Utils_Token::getTokenDetails($params);

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($message, $contacts[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('membership', $membership, $tokenHtml, $messageToken);
      $tokenHtml = CRM_Utils_Token::parseThroughSmarty($tokenHtml, $contacts[$contactId]);

      $html[] = $tokenHtml;

      $primaryContact = array(
        'primary_first_name' => $contacts[$contactId]['first_name'],
        'primary_last_name' => $contacts[$contactId]['last_name'],
        'primary_membership_id' => $membershipID,
        'membership_end_date' => CRM_Utils_Date::customformat($membership['end_date'], '%m/%d/%Y'),
        'message' => $tokenHtml,
      );

      // get the information on other member of the family
      $otherMemberInfo = array();
      if (!empty($membership['relationship_name'])
        && $membership['relationship_name'] == self::RELATIONSHIP_TYPE_FOR_INHERITANCE) {
        // get the contact via relationship
        $result = civicrm_api3('Relationship', 'get', array(
          'sequential' => 1,
          'return' => array("contact_id_b"),
          'relationship_type_id' => 11,
          'contact_id_a' => $contactId,
        ));

        $otherContactId = $result['values'][0]['contact_id_b'];

        // get contact and membership information
        $result = civicrm_api3('Contact', 'get', array(
          'sequential' => 1,
          'return' => array("first_name", "last_name"),
          'id' => $otherContactId,
          'api.Membership.get' => array('owner_membership_id' => $membershipID),
        ));

        $otherMemberInfo = array(
          'other_first_name' => $result['values'][0]['first_name'],
          'other_last_name' => $result['values'][0]['last_name'],
          'other_membership_id' => $result['values'][0]['api.Membership.get']['values'][0]['id'],
          'other_membership_end_date' => CRM_Utils_Date::customformat($membership['end_date'], '%m/%d/%Y'),
        );
      }

      // add primary and other contact information
      $rows[] = array_merge($primaryContact, $otherMemberInfo);
    }

    $membershipCardClass = new CRM_Membershipcard_BAO_Card();
    $membershipCardClass->createLabels($rows, $layoutInfo);
  }
}
