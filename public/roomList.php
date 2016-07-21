<?php
//
// Description
// -----------
// This method will return the list of Conference Rooms for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Conference Room for.
//
// Returns
// -------
//
function ciniki_conferences_roomList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.roomList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of rooms
    //
    $strsql = "SELECT ciniki_conferences_rooms.id, "
        . "ciniki_conferences_rooms.conference_id, "
        . "ciniki_conferences_rooms.name, "
        . "ciniki_conferences_rooms.sequence "
        . "FROM ciniki_conferences_rooms "
        . "WHERE ciniki_conferences_rooms.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_conferences_rooms.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
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

    return array('stat'=>'ok', 'rooms'=>$rooms);
}
?>
