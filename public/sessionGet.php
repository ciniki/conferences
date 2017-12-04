<?php
//
// Description
// ===========
// This method will return all the information about an conference session.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the conference session is attached to.
// session_id:          The ID of the conference session to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_sessionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'session_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference Session'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.sessionGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Conference Session
    //
    if( $args['session_id'] == 0 ) {
        $session = array('id'=>0,
            'conference_id'=>'',
            'room_id'=>'',
            'name'=>'',
            'session_start'=>'',
            'session_end'=>'',
        );
    }

    //
    // Get the details for an existing Conference Session
    //
    else {
        $strsql = "SELECT ciniki_conferences_sessions.id, "
            . "ciniki_conferences_sessions.conference_id, "
            . "ciniki_conferences_sessions.room_id, "
            . "ciniki_conferences_sessions.name, "
            . "ciniki_conferences_sessions.session_start, "
            . "ciniki_conferences_sessions.session_end "
            . "FROM ciniki_conferences_sessions "
            . "WHERE ciniki_conferences_sessions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_conferences_sessions.id = '" . ciniki_core_dbQuote($ciniki, $args['session_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'sessions', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'room_id', 'name', 'session_start', 'session_end'),
                'utctotz'=>array(
                    'session_start'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                    'session_end'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.42', 'msg'=>'Conference Session not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['sessions'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.43', 'msg'=>'Unable to find Conference Session'));
        }
        $session = $rc['sessions'][0];
    }

    //
    // Get the list of rooms
    //
    $strsql = "SELECT ciniki_conferences_rooms.id, "
        . "ciniki_conferences_rooms.conference_id, "
        . "ciniki_conferences_rooms.name, "
        . "ciniki_conferences_rooms.sequence "
        . "FROM ciniki_conferences_rooms "
        . "WHERE ciniki_conferences_rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_conferences_rooms.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "ORDER BY ciniki_conferences_rooms.sequence, ciniki_conferences_rooms.name "
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
        $rooms = $rc['rooms'];
    } else {
        $rooms = array();
    }

    return array('stat'=>'ok', 'session'=>$session, 'rooms'=>$rooms);
}
?>
