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
// tnid:         The ID of the tenant the conference is attached to.
// conference_id:          The ID of the conference to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_conferenceBiosDownload($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.conferenceScheduleDownload');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

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

    $strsql = "SELECT ciniki_conferences.id, "
        . "ciniki_conferences.name, "
        . "ciniki_conferences.permalink, "
        . "ciniki_conferences.status, "
        . "ciniki_conferences.status AS status_text, "
        . "ciniki_conferences.flags, "
        . "DATE_FORMAT(ciniki_conferences.start_date, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS start_date, "
        . "DATE_FORMAT(ciniki_conferences.end_date, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS end_date, "
        . "ciniki_conferences.synopsis, "
        . "ciniki_conferences.description, "
        . "ciniki_conferences.imap_mailbox, "
        . "ciniki_conferences.imap_username, "
        . "ciniki_conferences.imap_password, "
        . "ciniki_conferences.imap_subject "
        . "FROM ciniki_conferences "
        . "WHERE ciniki_conferences.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_conferences.id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.14', 'msg'=>'Conference not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['conference']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.15', 'msg'=>'Unable to find Conference'));
    }
    $conference = $rc['conference'];
    if( isset($maps['conference']['status'][$conference['status_text']]) ) {
        $conference['status_text'] = $maps['conference']['status'][$conference['status_text']];
    }

    $strsql = "SELECT ciniki_conferences_sessions.id, "
        . "CONCAT_WS('-', ciniki_conferences_sessions.id, ciniki_conferences_presentations.id) AS rowid, "
        . "ciniki_conferences_sessions.conference_id, "
        . "ciniki_conferences_sessions.room_id, "
        . "ciniki_conferences_rooms.name AS room, "
        . "ciniki_conferences_rooms.sequence, "
        . "ciniki_conferences_sessions.name, "
        . "ciniki_conferences_sessions.session_start AS start_time, "
        . "ciniki_conferences_sessions.session_start AS start_date, "
        . "ciniki_conferences_sessions.session_end AS end_time, "
        . "IFNULL(ciniki_conferences_presentations.id, 0) AS presentation_id, "
        . "IFNULL(ciniki_customers.id, 0) AS customer_id, "
        . "IFNULL(ciniki_conferences_presentations.presentation_number, '') AS presentation_number, "
        . "IFNULL(ciniki_conferences_presentations.title, '') AS presentation_title, "
        . "IFNULL(ciniki_conferences_presentations.description, '') AS presentation_description, "
        . "IFNULL(ciniki_customers.display_name, '') AS display_name, "
        . "IFNULL(ciniki_customers.full_bio, '') AS full_bio, "
        . "IFNULL(ciniki_conferences_presentations.status, 0) AS status, "
        . "IFNULL(ciniki_conferences_presentations.status, '') AS status_text, "
        . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration, "
        . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration_text "
        . "FROM ciniki_conferences_sessions "
        . "INNER JOIN ciniki_conferences_rooms ON ("
            . "ciniki_conferences_sessions.room_id = ciniki_conferences_rooms.id "
            . "AND ciniki_conferences_rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_conferences_presentations ON ("
            . "ciniki_conferences_sessions.id = ciniki_conferences_presentations.session_id "
            . "AND ciniki_conferences_presentations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_conferences_attendees ON ("
            . "(ciniki_conferences_presentations.customer1_id = ciniki_conferences_attendees.customer_id "
                . " OR ciniki_conferences_presentations.customer2_id = ciniki_conferences_attendees.customer_id "
                . " OR ciniki_conferences_presentations.customer3_id = ciniki_conferences_attendees.customer_id "
                . " OR ciniki_conferences_presentations.customer4_id = ciniki_conferences_attendees.customer_id "
                . " OR ciniki_conferences_presentations.customer5_id = ciniki_conferences_attendees.customer_id "
                . ") "
            . "AND ciniki_conferences_presentations.conference_id = ciniki_conferences_attendees.conference_id "
            . "AND ciniki_conferences_attendees.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "(ciniki_conferences_presentations.customer1_id = ciniki_customers.id "
                . "OR ciniki_conferences_presentations.customer2_id = ciniki_customers.id "
                . "OR ciniki_conferences_presentations.customer3_id = ciniki_customers.id "
                . "OR ciniki_conferences_presentations.customer4_id = ciniki_customers.id "
                . "OR ciniki_conferences_presentations.customer5_id = ciniki_customers.id "
                . ") "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_conferences_sessions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_conferences_sessions.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "ORDER BY ciniki_conferences_sessions.session_start, "
            . "ciniki_conferences_rooms.name, "
            . "ciniki_conferences_rooms.sequence, "
            . "ciniki_conferences_presentations.title, "
            . "customer_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'times', 'fname'=>'start_time', 'fields'=>array('start_time', 'start_date', 'end_time'),
            'utctotz'=>array(
                'start_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                'start_date'=>array('format'=>$date_format, 'timezone'=>$intl_timezone),
                'end_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                ),
            ),
        array('container'=>'rooms', 'fname'=>'room_id', 'fields'=>array('id'=>'room_id', 'name'=>'room', 'session_name'=>'name', 'presentation_id')),
        array('container'=>'presentations', 'fname'=>'customer_id', 
            'fields'=>array('id', 'conference_id', 'room_id', 'room', 'sequence', 'name', 'start_time', 'start_date', 'end_time',
                'presentation_id', 'customer_id', 'presentation_number', 'presentation_title', 'presentation_description', 
                'display_name', 'full_bio', 'status', 'status_text', 'registration', 'registration_text'),
            'utctotz'=>array(
                'start_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                'start_date'=>array('format'=>$date_format, 'timezone'=>$intl_timezone),
                'end_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                ),
            'maps'=>array(
                'status_text'=>$maps['presentation']['status'],
                'registration_text'=>$maps['attendee']['status'],
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['times']) ) {
        $timeslots = $rc['times'];
    } else {
        $timeslots = array();
    }


    //
    // Generate the word file
    //
//    require_once($ciniki['config']['core']['lib_dir'] . '/PHPWord/src/PhpWord/Autoloader.php');
//    \PhpOffice\PhpWord\Autoloader::register();
//    require($ciniki['config']['core']['lib_dir'] . '/PHPWord/src/PhpWord/PhpWord.php');
    require_once($ciniki['config']['core']['lib_dir'] . '/PHPWord/bootstrap.php');

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>18), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>14), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $style_table = array('cellMargin'=>80, 'borderColor'=>'aaaaaa', 'borderSize'=>6);
    $style_header = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'bgColor'=>'dddddd', 'valign'=>'center');
    $style_cell = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'valign'=>'center', 'bgcolor'=>'ffffff');
    $style_header_font = array('bold'=>true, 'spaceAfter'=>20);
    $style_cell_font = array();
    $style_header_pleft = array('align'=>'left');
    $style_header_pright = array('align'=>'right');
    $style_cell_pleft = array('align'=>'left');
    $style_cell_pright = array('align'=>'right');

    $section = $PHPWord->addSection();
    $header = $section->addHeader();
    $table = $header->addTable();
    $table->addRow();
    $cell = $table->addCell(9600);
    $cell->addText($conference['name'], array('size'=>'16'), array('align'=>'center'));

   
    //print "<pre>" . print_r($timeslots, true) . "</pre>";
    //exit;

    //
    // Output the bios of each presenter
    //
    $session_number = 1;
    foreach($timeslots as $timeslot) {
        if( isset($timeslot['rooms']) && count($timeslot['rooms']) > 0 ) {
            foreach($timeslot['rooms'] as $room) {
                if( !isset($room['presentations']) || $room['presentation_id'] == 0 ) {
                    continue;
                }
                if( isset($room['presentations']) && $room['presentation_id'] != 0 ) {
                    foreach($room['presentations'] as $pid => $presentation) {
                        $section->addTitle(htmlspecialchars($presentation['display_name']), 1);
                        $lines = explode("\n", $presentation['full_bio']);
                        foreach($lines as $line) {
                            $section->addText(htmlspecialchars($line), array());
                        }
                        $section->addText('');
                    } 
                }
                $session_number++;
            }
        }
    }


    //
    // Output the word file
    //
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="' . preg_replace("/[^A-Za-z0-9]/", '', $conference['name']) . '-bios.docx"');
    header('Cache-Control: max-age=0');

    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($PHPWord, 'Word2007');
    $objWriter->save('php://output');
    return array('stat'=>'exit');
}
?>
