<?php
//
// Description
// -----------
// This method will add a new presentation for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the Presentation to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_conferences_presentationAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'presentation_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'session_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Session'),
        'registration'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration'),
        'submission_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Submission Date'),
        'field'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Field of Study'),
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'),
        'permalink'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permalink'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($args['permalink']) || $args['permalink'] == '' ) {    
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id "
        . "FROM ciniki_conferences_presentations "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
        . "AND conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' " 
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'presentation');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.23', 'msg'=>'You already have a presentation with this title, please choose another title.'));
    }

    //
    // FIXME: Get the next presentation_number
    //

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.conferences');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the presentation to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.conferences.presentation', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
        return $rc;
    }
    $presentation_id = $rc['id'];

    //
    // Check if registration set
    // 
    if( isset($args['registration']) && $args['registration'] > 0 ) {
        $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.conferences.attendee', array(
            'conference_id'=>$args['conference_id'],
            'customer_id'=>$args['customer_id'],
            'status'=>$args['registration'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
            return $rc;
        }
        $presentation_id = $rc['id'];
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

    return array('stat'=>'ok', 'id'=>$presentation_id);
}
?>
