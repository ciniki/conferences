<?php
//
// Description
// ===========
// This function will generate the PDF for the review of the presentations to review
// with the submitter name removed.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_conferences_templates_reviewerPresentationsPDF(&$ciniki, $business_id, $reviewer_id, $conference_id) {
    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'maps');
    $rc = ciniki_conferences_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the presentations to be reviewed
    //
    $strsql = "SELECT ciniki_conferences_presentations.id, "
        . "ciniki_conferences_presentations.conference_id, "
        . "ciniki_conferences_presentations.customer_id, "
        . "ciniki_conferences_presentations.presentation_number, "
        . "ciniki_conferences_presentations.presentation_type, "
        . "ciniki_conferences_presentations.presentation_type AS presentation_type_text, "
        . "ciniki_conferences_presentations.status, "
        . "ciniki_conferences_presentations.status AS status_text, "
        . "ciniki_conferences_presentations.field, "
        . "ciniki_conferences_presentations.title, "
        . "ciniki_conferences_presentations.permalink, "
        . "ciniki_conferences_presentations.description "
        . "FROM ciniki_conferences_presentation_reviews, ciniki_conferences_presentations "
        . "WHERE ciniki_conferences_presentation_reviews.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_conferences_presentation_reviews.customer_id = '" . ciniki_core_dbQuote($ciniki, $reviewer_id) . "' "
        . "AND ciniki_conferences_presentation_reviews.presentation_id = ciniki_conferences_presentations.id "
        . "AND ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $conference_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'presentations', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'customer_id', 'presentation_number', 'presentation_type', 'presentation_type_text',
                'status', 'status_text', 'field', 'title', 'permalink', 'description'),
            'maps'=>array(
                'presentation_type_text'=>$maps['presentation']['presentation_type'],
                'status_text'=>$maps['presentation']['status'],
                ),
             ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3189', 'msg'=>'Presentations not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['presentations']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3247', 'msg'=>'Unable to find presentations'));
    }
    $presentations = $rc['presentations'];
    foreach($presentations as $pid => $presentation) {
        $presentations[$pid]['display_title'] = sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title'];
    }

    //
    // Load the business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $business_details = $rc['details'];
    } else {
        $business_details = array();
    }

    //
    // Load the conference details
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_conferences "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $conference_id) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['conference']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3248', 'msg'=>'Unable to find conference'));
    }
    $conference = $rc['conference'];

    //
    // Load TCPDF library
    //
    $rsp = array('stat'=>'ok');
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 18;
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 25;        // The height of the image and address
        public $business_details = array();
        public $courses_settings = array();
        public $conference_name = '';

        public function Header() {
            $this->SetFont('helvetica', 'I', 14);
            $this->Cell(0, 10, $this->conference_name, 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    $pdf->business_details = $business_details;
    $pdf->conference_name = $conference['name'];

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($business_details['name']);
    $pdf->SetTitle($conference['name']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);


    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    foreach($presentations as $presentation) {
        // add a page
        $pdf->AddPage();
        $pdf->SetFont('', 'B', 12);
        $pdf->MultiCell(180, 1, $presentation['display_title'], 0, 'L');
        $pdf->SetFont('', '', 11);
        $pdf->MultiCell(180, 1, $presentation['field'], 0, 'L');
        $pdf->SetFont('', '', 10);
        $pdf->Ln();
        $pdf->MultiCell(180, 8, $presentation['description'], 0, 'L');
    }

    return array('stat'=>'ok', 'presentations'=>$presentations, 'conference'=>$conference, 'pdf'=>$pdf);
}
?>
