<?php
//
// Description
// -----------
// This method will return the list of Conference Sessions for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Conference Session for.
//
// Returns
// -------
//
function ciniki_conferences_sessionList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.sessionList');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the list of sessions
    //
    $strsql = "SELECT ciniki_conferences_sessions.id, "
        . "ciniki_conferences_sessions.conference_id, "
        . "ciniki_conferences_sessions.room_id, "
        . "ciniki_conferences_rooms.name, "
        . "ciniki_conferences_rooms.sequence, "
        . "ciniki_conferences_sessions.session_start, "
        . "ciniki_conferences_sessions.session_end "
        . "FROM ciniki_conferences_sessions "
        . "WHERE ciniki_conferences_sessions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'sessions', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'room_id', 'name', 'sequence', 'session_start', 'session_end'),
            'utctotz'=>array('session_start'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['sessions']) ) {
        $sessions = $rc['sessions'];
    } else {
        $sessions = array();
    }

    return array('stat'=>'ok', 'sessions'=>$sessions);
}
?>
