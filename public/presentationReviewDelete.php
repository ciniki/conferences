<?php
//
// Description
// -----------
// This method will delete an presentation review.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the presentation review is attached to.
// review_id:            The ID of the presentation review to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_conferences_presentationReviewDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'review_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Presentation Review'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationReviewDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the presentation review
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_conferences_presentation_reviews "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['review_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'presentationreview');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['presentationreview']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.28', 'msg'=>'Presentation Review does not exist.'));
    }
    $presentationreview = $rc['presentationreview'];

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
    // Remove the presentationreview
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.conferences.presentationreview',
        $args['review_id'], $presentationreview['uuid'], 0x04);
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
