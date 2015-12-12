<?php
//
// Description
// -----------
// This method will return the list of Conferences for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Conference for.
//
// Returns
// -------
//
function ciniki_conferences_conferenceList($ciniki) {
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.conferenceList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of conferences
    //
    $strsql = "SELECT ciniki_conferences.id, "
        . "ciniki_conferences.name, "
        . "ciniki_conferences.permalink, "
        . "ciniki_conferences.status, "
        . "ciniki_conferences.flags, "
        . "ciniki_conferences.start_date, "
        . "ciniki_conferences.end_date, "
        . "ciniki_conferences.synopsis, "
        . "ciniki_conferences.description "
        . "FROM ciniki_conferences "
        . "WHERE ciniki_conferences.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'conferences', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'start_date', 'end_date', 'flags', 'synopsis', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['conferences']) ) {
        $conferences = $rc['conferences'];
    } else {
        $conferences = array();
    }

    return array('stat'=>'ok', 'conferences'=>$conferences);
}
?>
