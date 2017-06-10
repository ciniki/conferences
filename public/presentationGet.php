<?php
//
// Description
// ===========
// This method will return all the information about an presentation.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the presentation is attached to.
// presentation_id:          The ID of the presentation to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_presentationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'presentation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Presentation'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

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
    // Return default for new Presentation
    //
    if( $args['presentation_id'] == 0 ) {
        $presentation = array('id'=>0,
            'conference_id'=>'',
            'customer1_id'=>'',
            'customer2_id'=>'',
            'customer3_id'=>'',
            'customer4_id'=>'',
            'customer5_id'=>'',
            'session_id'=>0,
            'presentation_number'=>'',
            'presentation_type'=>'',
            'status'=>'10',
            'status_text'=>'Submitted',
            'registration'=>'0',
            'submission_date'=>'',
            'field'=>'',
            'title'=>'',
            'permalink'=>'',
            'description'=>'',
        );
        $sessions = array();
    }

    //
    // Get the details for an existing Presentation
    //
    else {
        $strsql = "SELECT ciniki_conferences_presentations.id, "
            . "ciniki_conferences_presentations.conference_id, "
            . "ciniki_conferences_presentations.customer1_id, "
            . "ciniki_conferences_presentations.customer2_id, "
            . "ciniki_conferences_presentations.customer3_id, "
            . "ciniki_conferences_presentations.customer4_id, "
            . "ciniki_conferences_presentations.customer5_id, "
            . "ciniki_conferences_presentations.session_id, "
            . "ciniki_conferences_presentations.presenters, "
            . "ciniki_conferences_presentations.presentation_number, "
            . "ciniki_conferences_presentations.presentation_type, "
            . "ciniki_conferences_presentations.presentation_type AS presentation_type_text, "
            . "ciniki_conferences_presentations.status, "
            . "ciniki_conferences_presentations.status AS status_text, "
            . "IFNULL(a1.status, 0) AS registration1, "
            . "IFNULL(a1.status, 0) AS registration1_text, "
            . "IFNULL(a2.status, 0) AS registration2, "
            . "IFNULL(a2.status, 0) AS registration2_text, "
            . "IFNULL(a3.status, 0) AS registration3, "
            . "IFNULL(a3.status, 0) AS registration3_text, "
            . "IFNULL(a4.status, 0) AS registration4, "
            . "IFNULL(a4.status, 0) AS registration4_text, "
            . "IFNULL(a5.status, 0) AS registration5, "
            . "IFNULL(a5.status, 0) AS registration5_text, "
            . "ciniki_conferences_presentations.submission_date, "
            . "ciniki_conferences_presentations.field, "
            . "ciniki_conferences_presentations.title, "
            . "ciniki_conferences_presentations.permalink, "
            . "ciniki_conferences_presentations.description "
            . "FROM ciniki_conferences_presentations "
            . "LEFT JOIN ciniki_conferences_attendees AS a1 ON ("
                . "ciniki_conferences_presentations.customer1_id = a1.customer_id "
                . "AND ciniki_conferences_presentations.conference_id = a1.conference_id "
                . "AND a1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_conferences_attendees AS a2 ON ("
                . "ciniki_conferences_presentations.customer2_id = a2.customer_id "
                . "AND ciniki_conferences_presentations.conference_id = a2.conference_id "
                . "AND a2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_conferences_attendees AS a3 ON ("
                . "ciniki_conferences_presentations.customer3_id = a3.customer_id "
                . "AND ciniki_conferences_presentations.conference_id = a3.conference_id "
                . "AND a3.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_conferences_attendees AS a4 ON ("
                . "ciniki_conferences_presentations.customer4_id = a4.customer_id "
                . "AND ciniki_conferences_presentations.conference_id = a4.conference_id "
                . "AND a4.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_conferences_attendees AS a5 ON ("
                . "ciniki_conferences_presentations.customer5_id = a5.customer_id "
                . "AND ciniki_conferences_presentations.conference_id = a5.conference_id "
                . "AND a5.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences_presentations.id = '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'presentations', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'customer1_id', 'customer2_id', 'customer3_id', 'customer4_id', 'customer5_id',
                    'session_id', 'presenters', 'presentation_number', 'presentation_type', 'presentation_type_text',
                    'status', 'status_text', 
                    'registration1', 'registration1_text', 'registration2', 'registration2_text', 'registration3', 'registration3_text', 
                    'registration4', 'registration4_text', 'registration5', 'registration5_text', 
                    'submission_date', 'field', 'title', 'permalink', 'description'),
                'maps'=>array(
                    'presentation_type_text'=>$maps['presentation']['presentation_type'],
                    'status_text'=>$maps['presentation']['status'],
                    'registration1_text'=>$maps['attendee']['status'],
                    'registration2_text'=>$maps['attendee']['status'],
                    'registration3_text'=>$maps['attendee']['status'],
                    'registration4_text'=>$maps['attendee']['status'],
                    'registration5_text'=>$maps['attendee']['status'],
                    ),
                'utctotz'=>array('submission_date'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone)),
                 ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.25', 'msg'=>'Presentation not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['presentations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.26', 'msg'=>'Unable to find Presentation'));
        }
        $presentation = $rc['presentations'][0];
        $presentation['display_title'] = sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title'];

        //
        // Get the customer details
        //
        for($i = 1; $i <= 5; $i++) {
            if( $presentation['customer' . $i . '_id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
                $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], 
                    array('customer_id'=>$presentation['customer' . $i . '_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no', 'full_bio'=>'yes'));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $presentation['customer' . $i . '_first'] = $rc['customer']['first'];
                $presentation['customer' . $i . '_details'] = $rc['details'];
                if( isset($rc['customer']['full_bio']) ) {
                    $presentation['customer' . $i . '_bio'] = $rc['customer']['full_bio'];
                } else {
                    $presentation['customer' . $i . '_bio'] = '';
                }
            }
        }

        //
        // Lookup reviews
        //
        $strsql = "SELECT ciniki_conferences_presentation_reviews.id, "
            . "ciniki_conferences_presentation_reviews.conference_id, "
            . "ciniki_conferences_presentation_reviews.customer_id, "
            . "ciniki_customers.display_name, "
            . "ciniki_conferences_presentation_reviews.vote, "
            . "ciniki_conferences_presentation_reviews.vote AS vote_text "
            . "FROM ciniki_conferences_presentation_reviews "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_conferences_presentation_reviews.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_conferences_presentation_reviews.presentation_id = '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
            . "AND ciniki_conferences_presentation_reviews.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'reviews', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'customer_id', 'display_name', 'vote', 'vote_text'),
                'maps'=>array('vote_text'=>$maps['presentationreview']['vote']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.27', 'msg'=>'Unable to get list of reviews', 'err'=>$rc['err']));
        }
        if( isset($rc['reviews']) ) {
            $presentation['reviews'] = $rc['reviews'];
        } else {
            $presentation['reviews'] = array();
        }

        //
        // Get the list of sessions
        //
        $strsql = "SELECT ciniki_conferences_sessions.id, "
            . "ciniki_conferences_sessions.conference_id, "
            . "ciniki_conferences_sessions.room_id, "
            . "ciniki_conferences_sessions.name, "
            . "ciniki_conferences_rooms.name AS room, "
            . "ciniki_conferences_rooms.sequence, "
            . "ciniki_conferences_sessions.session_start AS start_time, "
            . "ciniki_conferences_sessions.session_start AS start_date, "
            . "ciniki_conferences_sessions.session_end AS end_time "
            . "FROM ciniki_conferences_sessions "
            . "LEFT JOIN ciniki_conferences_rooms ON ("
                . "ciniki_conferences_sessions.room_id = ciniki_conferences_rooms.id "
                . "AND ciniki_conferences_rooms.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_conferences_sessions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences_sessions.conference_id = '" . ciniki_core_dbQuote($ciniki, $presentation['conference_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'sessions', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'room', 'room_id', 'name', 'sequence', 
                    'start_time', 'start_date', 'end_time'),
                'utctotz'=>array(
                    'start_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                    'start_date'=>array('format'=>$date_format, 'timezone'=>$intl_timezone),
                    'end_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sessions']) ) {
            $sessions = $rc['sessions'];
            foreach($sessions as $sid => $session) {
                $sessions[$sid]['display_name'] = $session['name'];
                if( $session['room'] != '' ) {
                    $sessions[$sid]['display_name'] .= ($sessions[$sid]['display_name'] != '' ? ' - ' : '') . $session['room'];
                }
                $sessions[$sid]['display_name'] .= ($sessions[$sid]['display_name'] != '' ? ' - ' : '') . $session['start_time'] . ' - ' . $session['end_time'] . ', ' . $session['start_date'];
            }
        } else {
            $sessions = array();
        }
    }

    return array('stat'=>'ok', 'presentation'=>$presentation, 'sessions'=>$sessions);
}
?>
