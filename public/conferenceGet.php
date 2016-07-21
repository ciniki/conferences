<?php
//
// Description
// ===========
// This method will return all the information about an conference.
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
function ciniki_conferences_conferenceGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'cfplogs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'CFP Log Entries'),
        'presentations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Presentations'),
        'presentation_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Presentations'),
        'registration_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
        'presentation_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Presentations'),
        'reviewers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Reviewers'),
        'attendees'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Attendees'),
        'attendee_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Presentations'),
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.conferenceGet');
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
    // Return default for new Conference
    //
    $email_list = '';
    if( $args['conference_id'] == 0 ) {
        $conference = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'flags'=>'0',
            'start_date'=>'',
            'end_date'=>'',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Conference
    //
    else {
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
            . "WHERE ciniki_conferences.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences.id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2840', 'msg'=>'Conference not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['conference']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2841', 'msg'=>'Unable to find Conference'));
        }
        $conference = $rc['conference'];
        if( isset($maps['conference']['status'][$conference['status_text']]) ) {
            $conference['status_text'] = $maps['conference']['status'][$conference['status_text']];
        }

        //
        // Check if CFP logs should be returned
        //
        if( isset($args['cfplogs']) && $args['cfplogs'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_cfplogs.id, "
                . "ciniki_conferences_cfplogs.name, "
                . "ciniki_conferences_cfplogs.url, "
                . "ciniki_conferences_cfplogs.sent_date, "
                . "ciniki_conferences_cfplogs.email "
                . "FROM ciniki_conferences_cfplogs "
                . "WHERE ciniki_conferences_cfplogs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_cfplogs.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'cfplogs', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'url', 'email', 'sent_date'),
                    'utctotz'=>array('sent_date'=>array('format'=>$date_format, 'timezone'=>'UTC')),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['cfplogs']) ) {
                $conference['cfplogs'] = $rc['cfplogs'];
            } else {
                $conference['cfplogs'] = array();
            }
        }

        //
        // Check if presentations should be returned
        //
        if( isset($args['presentations']) && $args['presentations'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_presentations.id, "
                . "ciniki_conferences_presentations.conference_id, "
                . "ciniki_conferences_presentations.customer_id, "
                . "ciniki_customers.display_name, "
                . "ciniki_conferences_presentations.presentation_number, "
                . "ciniki_conferences_presentations.presentation_type, "
                . "ciniki_conferences_presentations.presentation_type AS presentation_type_text, "
                . "ciniki_conferences_presentations.status, "
                . "ciniki_conferences_presentations.status AS status_text, "
                . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration, "
                . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration_text, "
                . "ciniki_conferences_presentations.submission_date, "
                . "ciniki_conferences_presentations.field, "
                . "ciniki_conferences_presentations.title, "
                . "ciniki_conferences_presentations.permalink, "
                . "IF(ciniki_conferences_presentation_reviews.vote > 0, 'yes', 'no') AS voted, "
                . "COUNT(ciniki_conferences_presentation_reviews.id) AS num_votes "
                . "FROM ciniki_conferences_presentations "
                . "LEFT JOIN ciniki_conferences_attendees ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_conferences_attendees.customer_id "
                    . "AND ciniki_conferences_presentations.conference_id = ciniki_conferences_attendees.conference_id "
                    . "AND ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_conferences_presentation_reviews ON ("
                    . "ciniki_conferences_presentations.id = ciniki_conferences_presentation_reviews.presentation_id "
                    . "AND ciniki_conferences_presentation_reviews.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            if( isset($args['presentation_status']) && $args['presentation_status'] > 0 ) {
                $strsql .= "AND ciniki_conferences_presentations.status = '" . ciniki_core_dbQuote($ciniki, $args['presentation_status']) . "' ";
            }
            if( isset($args['presentation_type']) && $args['presentation_type'] > 0 ) {
                $strsql .= "AND ciniki_conferences_presentations.presentation_type = '" . ciniki_core_dbQuote($ciniki, $args['presentation_type']) . "' ";
            }
            $strsql .= "GROUP BY ciniki_conferences_presentations.id, voted ";
            if( isset($args['registration_status']) && $args['registration_status'] != '' ) {
                $strsql .= "HAVING registration = '" . ciniki_core_dbQuote($ciniki, $args['registration_status']) . "' ";
            }
            $strsql .= "ORDER BY submission_date ";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'presentations', 'fname'=>'id', 
                    'fields'=>array('id', 'conference_id', 'customer_id', 'presentation_type', 'presentation_number',
                        'status', 'status_text', 'registration', 'registration_text', 'submission_date', 'field', 'title', 'display_name', 'permalink'),
                     'utctotz'=>array('submission_date'=>array('format'=>'M j', 'timezone'=>$intl_timezone)),
                     'maps'=>array(
                        'status_text'=>$maps['presentation']['status'],
                        'registration_text'=>$maps['attendee']['status'],
                        'presentation_type_text'=>$maps['presentation']['presentation_type'],
                     )),
                array('container'=>'voted', 'fname'=>'voted', 'fields'=>array('voted', 'num_votes')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $customer_ids = array();
            if( isset($rc['presentations']) ) {
                $conference['presentations'] = $rc['presentations'];
                foreach($conference['presentations'] as $pid => $presentation) {
                    $customer_ids[] = $presentation['customer_id'];
                    $conference['presentations'][$pid]['display_title'] = sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title'];
                    $conference['presentations'][$pid]['votes_received'] = 0;
                    $conference['presentations'][$pid]['total_reviews'] = 0;
                    if( isset($presentation['voted']) ) {
                        foreach($presentation['voted'] as $vote) {
                            $conference['presentations'][$pid]['total_reviews'] += $vote['num_votes'];
                            if( $vote['voted'] == 'yes' ) {
                                $conference['presentations'][$pid]['votes_received'] += $vote['num_votes'];
                            }
                        }
                        unset($conference['presentations'][$pid]['voted']);
                    }

                }
            } else {
                $conference['presentations'] = array();
            }

            //
            // Get the email list
            //
            if( isset($customer_ids) && count($customer_ids) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
                $strsql = "SELECT ciniki_customers.display_name, "
                    . "ciniki_customer_emails.email "
                    . "FROM ciniki_customers "
                    . "LEFT JOIN ciniki_customer_emails ON ("
                        . "ciniki_customers.id = ciniki_customer_emails.customer_id "
                        . "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                        . ") "
                    . "WHERE ciniki_customers.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids). ") "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "";
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                    array('container'=>'emails', 'fname'=>'email', 'fields'=>array('display_name', 'email')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['emails']) ) {
                    foreach($rc['emails'] as $email) {
                        $email_list .= ($email_list != '' ? ",\n" : '') . '"' . $email['display_name'] . '" &lt;' . $email['email'] . '&gt;';
                    }
                }
            }
        }

        //
        // Get the reviewers for the conference
        //
        if( isset($args['reviewers']) && $args['reviewers'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_presentation_reviews.id, " 
                . "ciniki_conferences_presentation_reviews.customer_id, "
                . "ciniki_customers.display_name, "
                . "IF(ciniki_conferences_presentation_reviews.vote > 0, 'yes', 'no') AS voted, "
                . "COUNT(ciniki_conferences_presentation_reviews.id) AS num_votes "
                . "FROM ciniki_conferences_presentation_reviews "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_conferences_presentation_reviews.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_presentation_reviews.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentation_reviews.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "GROUP BY ciniki_conferences_presentation_reviews.customer_id, voted "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'reviewers', 'fname'=>'customer_id', 'fields'=>array('id', 'customer_id', 'display_name')),
                array('container'=>'voted', 'fname'=>'voted', 'fields'=>array('voted', 'num_votes')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['reviewers']) ) {
                $conference['reviewers'] = $rc['reviewers'];

                foreach($conference['reviewers'] as $rid => $review) {
                    $conference['reviewers'][$rid]['votes_received'] = 0;
                    $conference['reviewers'][$rid]['total_reviews'] = 0;
                    if( isset($review['voted']) ) {
                        foreach($review['voted'] as $vote) {
                            $conference['reviewers'][$rid]['total_reviews'] += $vote['num_votes'];
                            if( $vote['voted'] == 'yes' ) {
                                $conference['reviewers'][$rid]['votes_received'] += $vote['num_votes'];
                            }
                        }
                        unset($conference['reviewers'][$rid]['voted']);
                    }
                }
            } else {
                $conference['reviewers'] = array();
            }
        }

        //
        // Get the attendees for the conference
        //
        if( isset($args['attendees']) && $args['attendees'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_attendees.id, " 
                . "ciniki_conferences_attendees.customer_id, "
                . "ciniki_customers.display_name, "
                . "ciniki_customers.sort_name, "
                . "ciniki_customers.company, "
                . "ciniki_conferences_attendees.status, "
                . "ciniki_conferences_attendees.status AS status_text, "
                . "IFNULL(ciniki_customer_emails.email, '') AS emails, "
                . "IF(IFNULL(ciniki_conferences_presentations.id, 0) > 0, 'Yes', 'No') AS presenter "
                . "FROM ciniki_conferences_attendees "
                . "LEFT JOIN ciniki_conferences_presentations ON ("
                    . "ciniki_conferences_attendees.customer_id = ciniki_conferences_presentations.customer_id "
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
            if( isset($args['attendee_status']) && $args['attendee_status'] != '' ) {
                $strsql .= "AND ciniki_conferences_attendees.status = '" . ciniki_core_dbQuote($ciniki, $args['attendee_status']) . "' ";
            }
            $strsql .= "GROUP BY ciniki_customers.display_name, ciniki_customers.id ";
            $strsql .= "ORDER BY ciniki_customers.sort_name ";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'attendees', 'fname'=>'id', 
                    'fields'=>array('id', 'customer_id', 'display_name', 'sort_name', 'company', 'status', 'status_text', 'emails', 'presenter'),
                    'lists'=>array('emails'=>','),
                    'maps'=>array('status_text'=>$maps['attendee']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['attendees']) ) {
                $conference['attendees'] = $rc['attendees'];
                foreach($conference['attendees'] as $attendee) {
                    $email_list .= ($email_list != '' ? ", \n" : '') . '"' . $attendee['display_name'] . '" &lt;' . $attendee['emails'] . '&gt;';
                }
            } else {
                $conference['attendees'] = array();
                $email_list = '';
            }
        }

        //
        // Get the presentations ordered by sessions, and then unassigned presentations
        //
        if( isset($args['sessionpresentations']) && $args['sessionpresentations'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_sessions.id, "
                . "ciniki_conferences_sessions.conference_id, "
                . "ciniki_conferences_sessions.room_id, "
                . "ciniki_conferences_rooms.name, "
                . "ciniki_conferences_rooms.sequence, "
                . "ciniki_conferences_sessions.session_start AS start_time, "
                . "ciniki_conferences_sessions.session_start AS start_date, "
                . "ciniki_conferences_sessions.session_end AS end_time, "
                . "IFNULL(ciniki_conferences_presentations.id, 0) AS presentation_id, "
                . "IFNULL(ciniki_conferences_presentations.customer_id, 0) AS customer_id, "
                . "IFNULL(ciniki_conferences_presentations.title, '') AS presentation_title, "
                . "IFNULL(ciniki_customers.display_name, '') AS display_name "
                . "IFNULL(ciniki_conferences_presentations.status, 0) AS presentation_status, "
                . "IFNULL(ciniki_conferences_presentations.status, '') AS presentation_status_text, "
                . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration, "
                . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration_text, "
                . "FROM ciniki_conferences_sessions "
                . "INNER JOIN ciniki_conferences_rooms ON ("
                    . "ciniki_conferences_sessions.room_id = ciniki_conferences_rooms.id "
                    . "AND ciniki_conferences_rooms.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_conferences_presentations ON ("
                    . "ciniki_conferences_sessions.id = ciniki_conferences_presentations.session_id "
                    . "AND ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_conferences_attendees ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_conferences_attendees.customer_id "
                    . "AND ciniki_conferences_presentations.conference_id = ciniki_conferences_attendees.conference_id "
                    . "AND ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_sessions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_sessions.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "ORDER BY ciniki_conferences_sessions.session_start, "
                    . "ciniki_conferences_rooms.sequence, "
                    . "ciniki_conferences_rooms.name, "
                    . "ciniki_conferences_presentations.title "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'sessions', 'fname'=>'id', 
                    'fields'=>array('id', 'conference_id', 'room_id', 'name', 'sequence', 'session_start', 'session_end',
                        'presentation_id', 'customer_id', 'presentation_title', 'display_name', 'presentation_status', 'registration', 'registration_text'),
                    'utctotz'=>array(
                        'start_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                        'start_date'=>array('format'=>$date_format, 'timezone'=>$intl_timezone),
                        'end_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                        'presentation_status_text'=>$maps['presentation']['status'],
                        'registration_text'=>$maps['attendee']['status'],
                        )),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['sessions']) ) {
                $conference['assignedpresentations'] = $rc['sessions'];
            } else {
                $conference['assignedpresentations'] = array();
            }

            //
            // Get the list of unassigned presentations
            //
            $strsql = "SELECT ciniki_conferences_presentations.id, "
                . "ciniki_conferences_presentations.conference_id, "
                . "ciniki_conferences_presentations.customer_id, "
                . "ciniki_customers.display_name, "
                . "ciniki_conferences_presentations.presentation_number, "
                . "ciniki_conferences_presentations.presentation_type, "
                . "ciniki_conferences_presentations.presentation_type AS presentation_type_text, "
                . "ciniki_conferences_presentations.status, "
                . "ciniki_conferences_presentations.status AS status_text, "
                . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration, "
                . "IFNULL(ciniki_conferences_attendees.status, 0) AS registration_text, "
                . "ciniki_conferences_presentations.submission_date, "
                . "ciniki_conferences_presentations.field, "
                . "ciniki_conferences_presentations.title, "
                . "ciniki_conferences_presentations.permalink "
                . "FROM ciniki_conferences_presentations "
                . "LEFT JOIN ciniki_conferences_attendees ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_conferences_attendees.customer_id "
                    . "AND ciniki_conferences_presentations.conference_id = ciniki_conferences_attendees.conference_id "
                    . "AND ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentations.session_id = 0 "
                . "AND ciniki_conferences_presentations.status = 30 "
                . "ORDER BY submission_date "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'presentations', 'fname'=>'id', 
                    'fields'=>array('id', 'conference_id', 'customer_id', 'presentation_type', 'presentation_number',
                        'status', 'status_text', 'registration', 'registration_text', 'submission_date', 'field', 'title', 'display_name', 'permalink'),
                     'utctotz'=>array('submission_date'=>array('format'=>'M j', 'timezone'=>$intl_timezone)),
                     'maps'=>array(
                        'status_text'=>$maps['presentation']['status'],
                        'registration_text'=>$maps['attendee']['status'],
                        'presentation_type_text'=>$maps['presentation']['presentation_type'],
                     )),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['unassignedpresentations']) ) {
                $conference['unassignedpresentations'] = $rc['presentations'];
            } else {
                $conference['unassignedpresentations'] = array();
            }
        }

        //
        // Get the list of sessions
        //
        if( isset($args['sessions']) && $args['sessions'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_sessions.id, "
                . "ciniki_conferences_sessions.conference_id, "
                . "ciniki_conferences_sessions.room_id, "
                . "ciniki_conferences_rooms.name, "
                . "ciniki_conferences_rooms.sequence, "
                . "ciniki_conferences_sessions.session_start AS start_time, "
                . "ciniki_conferences_sessions.session_start AS start_date, "
                . "ciniki_conferences_sessions.session_end AS end_time, "
                . "FROM ciniki_conferences_sessions "
                . "INNER JOIN ciniki_conferences_rooms ON ("
                    . "ciniki_conferences_sessions.room_id = ciniki_conferences_rooms.id "
                    . "AND ciniki_conferences_rooms.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND ciniki_conferences_rooms.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_sessions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_sessions.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "ORDER BY ciniki_conferences_sessions.session_start, ciniki_conferences_rooms.sequence, ciniki_conferences_rooms.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'sessions', 'fname'=>'id', 
                    'fields'=>array('id', 'conference_id', 'room_id', 'name', 'sequence', 'session_start', 'session_end'),
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
                $conference['sessions'] = $rc['sessions'];
            } else {
                $conference['sessions'] = array();
            }
        }

        //
        // Get the list of rooms
        //
        if( isset($args['rooms']) && $args['rooms'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_rooms.id, "
                . "ciniki_conferences_rooms.conference_id, "
                . "ciniki_conferences_rooms.name, "
                . "ciniki_conferences_rooms.sequence "
                . "FROM ciniki_conferences_rooms "
                . "WHERE ciniki_conferences_rooms.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_rooms.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "ORDER BY sequence, name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'rooms', 'fname'=>'id', 
                    'fields'=>array('id', 'conference_id', 'name', 'sequence')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['rooms']) ) {
                $conference['rooms'] = $rc['rooms'];
            } else {
                $conference['rooms'] = array();
            }
        }

        //
        // Get the stats for the conference
        //
        if( isset($args['stats']) && $args['stats'] == 'yes' ) {
            $strsql = "SELECT status, COUNT(status) AS num_presentations "
                . "FROM ciniki_conferences_presentations "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "GROUP BY status "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'stats', 'fname'=>'status', 'fields'=>array('status', 'num_presentations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $conference['presentation_stats'] = array();
            foreach($maps['presentation']['status'] as $status => $status_text) {
                $conference['presentation_stats'][$status] = array(
                    'name'=>$status_text,
                    'count'=>(isset($rc['stats'][$status]['num_presentations'])?$rc['stats'][$status]['num_presentations']:0),
                    );
            }

            //
            // Get the attendee stats
            //
            $strsql = "SELECT status, COUNT(status) AS num_attendees "
                . "FROM ciniki_conferences_attendees "
                . "WHERE ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_attendees.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "GROUP BY status "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'stats', 'fname'=>'status', 'fields'=>array('status', 'num_attendees')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $conference['attendee_stats'] = array();
            foreach($maps['attendee']['status'] as $status => $status_text) {
                $conference['attendee_stats'][$status] = array(
                    'name'=>$status_text,
                    'count'=>(isset($rc['stats'][$status]['num_attendees'])?$rc['stats'][$status]['num_attendees']:0),
                    );
            }

            //
            // Get the registration stats
            //
            $strsql = "SELECT IFNULL(ciniki_conferences_attendees.status, 0) AS status, COUNT(*) AS num_attendees "
                . "FROM ciniki_conferences_presentations "
                . "LEFT JOIN ciniki_conferences_attendees ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_conferences_attendees.customer_id "
                    . "AND ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND ciniki_conferences_attendees.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "";
            if( isset($args['presentation_status']) && $args['presentation_status'] > 0 ) {
                $strsql .= "AND ciniki_conferences_presentations.status = '" . ciniki_core_dbQuote($ciniki, $args['presentation_status']) . "' ";
            }
            if( isset($args['presentation_type']) && $args['presentation_type'] > 0 ) {
                $strsql .= "AND ciniki_conferences_presentations.presentation_type = '" . ciniki_core_dbQuote($ciniki, $args['presentation_type']) . "' ";
            }
            $strsql .= "GROUP BY status "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'stats', 'fname'=>'status', 'fields'=>array('status', 'num_attendees')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            foreach($maps['attendee']['status'] as $type => $type_text) {
                $conference['registration_statuses'][$type] = array(
                    'name'=>$type_text,
                    'count'=>(isset($rc['stats'][$type]['num_attendees'])?$rc['stats'][$type]['num_attendees']:0),
                    );
            }

            //
            // Get the types for the conference
            //
            $strsql = "SELECT presentation_type, COUNT(*) AS num_presentations "
                . "FROM ciniki_conferences_presentations "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "GROUP BY presentation_type "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'stats', 'fname'=>'presentation_type', 'fields'=>array('presentation_type', 'num_presentations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $conference['presentation_types'] = array();
            foreach($maps['presentation']['presentation_type'] as $type => $type_text) {
                $conference['presentation_types'][$type] = array(
                    'name'=>$type_text,
                    'count'=>(isset($rc['stats'][$type]['num_presentations'])?$rc['stats'][$type]['num_presentations']:0),
                    );
            }
        }
    }

    return array('stat'=>'ok', 'conference'=>$conference, 'emails'=>$email_list);
}
?>
