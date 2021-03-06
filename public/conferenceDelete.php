<?php
//
// Description
// -----------
// This method will delete an conference.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the conference is attached to.
// conference_id:            The ID of the conference to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_conferences_conferenceDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Conference'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.conferenceDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the conference
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_conferences "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['conference']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.16', 'msg'=>'Airlock does not exist.'));
    }
    $conference = $rc['conference'];

    //
    // Check if there are CFP Logs still
    //
    $strsql = "SELECT COUNT(id) AS num_logs "
        . "FROM ciniki_conferences_cfplogs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['conference']['num_logs']) && $rc['conference']['num_logs'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.17', 'msg'=>'Conference still has CFP logs.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.conferences');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the conference
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.conferences.conference',
        $args['conference_id'], $conference['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
        return $rc;
    }
    

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.conferences');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'conferences');

    return array('stat'=>'ok');
}
?>
