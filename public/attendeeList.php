<?php
//
// Description
// -----------
// This method will return the list of Attendees for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Attendee for.
//
// Returns
// -------
//
function ciniki_conferences_attendeeList($ciniki) {
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.attendeeList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of attendees
    //
    $strsql = "SELECT ciniki_conferences_attendees.id, "
        . "ciniki_conferences_attendees.conference_id, "
        . "ciniki_conferences_attendees.customer_id, "
        . "ciniki_conferences_attendees.status "
        . "FROM ciniki_conferences_attendees "
        . "WHERE ciniki_conferences_attendees.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'attendees', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'customer_id', 'status')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['attendees']) ) {
        $attendees = $rc['attendees'];
    } else {
        $attendees = array();
    }

    return array('stat'=>'ok', 'attendees'=>$attendees);
}
?>
