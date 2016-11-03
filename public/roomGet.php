<?php
//
// Description
// ===========
// This method will return all the information about an conference room.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the conference room is attached to.
// room_id:          The ID of the conference room to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_roomGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'room_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference Room'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.roomGet');
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
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Conference Room
    //
    if( $args['room_id'] == 0 ) {
        $room = array('id'=>0,
            'conference_id'=>'',
            'name'=>'',
            'sequence'=>'1',
        );
    }

    //
    // Get the details for an existing Conference Room
    //
    else {
        $strsql = "SELECT ciniki_conferences_rooms.id, "
            . "ciniki_conferences_rooms.conference_id, "
            . "ciniki_conferences_rooms.name, "
            . "ciniki_conferences_rooms.sequence "
            . "FROM ciniki_conferences_rooms "
            . "WHERE ciniki_conferences_rooms.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences_rooms.id = '" . ciniki_core_dbQuote($ciniki, $args['room_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'room');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.39', 'msg'=>'Conference Room not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['room']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.40', 'msg'=>'Unable to find Conference Room'));
        }
        $room = $rc['room'];
    }

    return array('stat'=>'ok', 'room'=>$room);
}
?>
