<?php
//
// Description
// -----------
// This method will return the list of CFP Logs for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get CFP Log for.
//
// Returns
// -------
//
function ciniki_conferences_CFPLogList($ciniki) {
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.CFPLogList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of cfplogs
    //
    $strsql = "SELECT ciniki_conferences_cfplog.id, "
        . "ciniki_conferences_cfplog.conference_id, "
        . "ciniki_conferences_cfplog.name, "
        . "ciniki_conferences_cfplog.url, "
        . "ciniki_conferences_cfplog.email, "
        . "ciniki_conferences_cfplog.notes "
        . "FROM ciniki_conferences_cfplog "
        . "WHERE ciniki_conferences_cfplog.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'cfplogs', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'name', 'url', 'email', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['cfplogs']) ) {
        $cfplogs = $rc['cfplogs'];
    } else {
        $cfplogs = array();
    }

    return array('stat'=>'ok', 'cfplogs'=>$cfplogs);
}
?>
