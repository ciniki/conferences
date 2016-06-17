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
function ciniki_conferences_presentationSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'),
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
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
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
    // Search the presentations
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
        . "ciniki_conferences_attendees.status AS registration, "
        . "ciniki_conferences_attendees.status AS registration_text, "
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
    $strsql .= "AND ("
        . "ciniki_customers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR ciniki_customers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR ciniki_conferences_presentations.title LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR ciniki_conferences_presentations.title LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    $strsql .= "GROUP BY ciniki_conferences_presentations.id, voted ";
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
    if( isset($rc['presentations']) ) {
        $presentations = $rc['presentations'];
        foreach($presentations as $pid => $presentation) {
            $presentations[$pid]['display_title'] = sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title'];
            $presentations[$pid]['votes_received'] = 0;
            $presentations[$pid]['total_reviews'] = 0;
            if( isset($presentation['voted']) ) {
                foreach($presentation['voted'] as $vote) {
                    $presentations[$pid]['total_reviews'] += $vote['num_votes'];
                    if( $vote['voted'] == 'yes' ) {
                        $presentations[$pid]['votes_received'] += $vote['num_votes'];
                    }
                }
                unset($presentations[$pid]['voted']);
            }

        }
    } else {
        $presentations = array();
    }

    return array('stat'=>'ok', 'presentations'=>$presentations);
}
?>
