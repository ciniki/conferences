<?php
//
// Description
// -----------
// This method will return the list of Conference Sessions for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Conference Session for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.sessionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
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
        . "ciniki_conferences_sessions.name, "
        . "ciniki_conferences_rooms.name AS room, "
        . "ciniki_conferences_rooms.sequence, "
        . "ciniki_conferences_sessions.session_start, "
        . "ciniki_conferences_sessions.session_end "
        . "FROM ciniki_conferences_sessions "
        . "WHERE ciniki_conferences_sessions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'sessions', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'name', 'room_id', 'room', 'sequence', 'session_start', 'session_end'),
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
