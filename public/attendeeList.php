<?php
//
// Description
// -----------
// This method will return the list of Attendees for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Attendee for.
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.attendeeList');
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
        . "WHERE ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
