<?php
//
// Description
// -----------
// This method will delete an conference room.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the conference room is attached to.
// room_id:            The ID of the conference room to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_conferences_roomDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'room_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Conference Room'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.roomDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the conference room
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_conferences_rooms "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['room_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'room');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['room']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3592', 'msg'=>'Conference Room does not exist.'));
    }
    $room = $rc['room'];

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
    // Remove the room
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.conferences.room',
        $args['room_id'], $room['uuid'], 0x04);
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
