<?php
//
// Description
// ===========
// This method will return a word document with the schedule for the conference.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the conference is attached to.
// conference_id:          The ID of the conference to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_attendeeExport($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Attendee or Presenter'),
        'output'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Format'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.attendeeExport');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');

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
    // Get the conference name
    //
    $strsql = "SELECT ciniki_conferences.id, "
        . "ciniki_conferences.name "
        . "FROM ciniki_conferences "
        . "WHERE ciniki_conferences.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_conferences.id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.9', 'msg'=>'Conference not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['conference']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.10', 'msg'=>'Unable to find Conference'));
    }
    $conference = $rc['conference'];

    //
    // Get the list of attendees
    //
    $strsql = "SELECT ciniki_conferences_attendees.id, " 
        . "ciniki_conferences_attendees.customer_id, "
        . "ciniki_customers.display_name, "
        . "ciniki_customers.sort_name, "
        . "ciniki_customers.prefix, "
        . "ciniki_customers.first, "
        . "ciniki_customers.middle, "
        . "ciniki_customers.last, "
        . "ciniki_customers.suffix, "
        . "ciniki_customers.company, "
        . "ciniki_customers.department, "
        . "ciniki_customers.title, "
        . "ciniki_conferences_attendees.status, "
        . "ciniki_conferences_attendees.status AS status_text, "
        . "IFNULL(ciniki_customer_emails.email, '') AS emails, "
        . "IF(IFNULL(ciniki_conferences_presentations.id, 0) > 0, 'Presenter', 'Attendee') AS presenter "
        . "FROM ciniki_conferences_attendees "
        . "LEFT JOIN ciniki_conferences_presentations ON ("
            . "(ciniki_conferences_attendees.customer_id = ciniki_conferences_presentations.customer1_id "
                . "OR ciniki_conferences_attendees.customer_id = ciniki_conferences_presentations.customer2_id "
                . "OR ciniki_conferences_attendees.customer_id = ciniki_conferences_presentations.customer3_id "
                . "OR ciniki_conferences_attendees.customer_id = ciniki_conferences_presentations.customer4_id "
                . "OR ciniki_conferences_attendees.customer_id = ciniki_conferences_presentations.customer5_id "
                . ") "
            . "AND ciniki_conferences_attendees.conference_id = ciniki_conferences_presentations.conference_id "
            . "AND ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_conferences_attendees.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "ciniki_customers.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_conferences_attendees.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "";
    $strsql .= "GROUP BY ciniki_customers.display_name, ciniki_customers.id ";
    if( isset($args['type']) && $args['type'] == 'attendee' ) {
        $strsql .= "HAVING presenter = 'Attendee' ";
    } elseif( isset($args['type']) && $args['type'] == 'presenter' ) {
        $strsql .= "HAVING presenter = 'Presenter' ";
    }
    $strsql .= "ORDER BY ciniki_customers.sort_name ";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'attendees', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'sort_name', 'prefix', 'first', 'middle', 'last', 'suffix', 
                'company', 'department', 'title', 'status', 'status_text', 'emails', 'presenter'),
            'lists'=>array('emails'=>','),
            'maps'=>array('status_text'=>$maps['attendee']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['attendees']) ) {
        $attendees = $rc['attendees'];
    } else {
        $attendees = array();
    }

    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $col = 0;
    $row = 1;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Registration', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Type', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Name', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Institution', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Department', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Title', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Salutation', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'First', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Middle', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Last', false);
//    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Degrees', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);

    $objPHPExcelWorksheet->getStyle('A1:H1')->getFont()->setBold(true);

    $row++;
    foreach($attendees as $attendee) {
        if( $attendee['status'] != 30 ) {
            continue;
        }
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['status_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['presenter'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['display_name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['company'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['department'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['title'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['prefix'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['first'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['middle'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['last'], false);
//        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['suffix'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $attendee['emails'], false);
        $row++;
    }

    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
    $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);

    //
    // Output the word file
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . preg_replace("/[^A-Za-z0-9]/", '', $conference['name']) . '-attendees.xls"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
