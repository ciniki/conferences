<?php
//
// Description
// -----------
// This method will delete an attendee.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the attendee is attached to.
// attendee_id:            The ID of the attendee to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_conferences_attendeeDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'attendee_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Attendee'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.attendeeDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the attendee
    //
    $strsql = "SELECT id, uuid, customer_id "
        . "FROM ciniki_conferences_attendees "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['attendee_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'attendee');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['attendee']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.8', 'msg'=>'Attendee does not exist.'));
    }
    $attendee = $rc['attendee'];

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
    // Remove the attendee
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.conferences.attendee',
        $args['attendee_id'], $attendee['uuid'], 0x04);
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
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'conferences');

    return array('stat'=>'ok');
}
?>
