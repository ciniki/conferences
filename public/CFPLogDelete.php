<?php
//
// Description
// -----------
// This method will delete an cfp log.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the cfp log is attached to.
// cfplog_id:            The ID of the cfp log to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_conferences_CFPLogDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'cfplog_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'CFP Log'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.CFPLogDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the cfp log
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_conferences_cfplogs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['cfplog_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'cfplog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['cfplog']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.4', 'msg'=>'Airlock does not exist.'));
    }
    $cfplog = $rc['cfplog'];

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
    // Remove any tags
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsDelete');
    $rc = ciniki_core_tagsDelete($ciniki, 'ciniki.conferences', 'tag', $args['tnid'],
        'ciniki_conferences_cfplog_tags', 'ciniki_conferences_history', 'cfplog_id', $args['cfplog_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
        return $rc;
    }

    //
    // Remove the cfplog
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.conferences.cfplog',
        $args['cfplog_id'], $cfplog['uuid'], 0x04);
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
