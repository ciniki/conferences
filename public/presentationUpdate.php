<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_conferences_presentationUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'presentation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Presentation'),
        'conference_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Conference'),
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
        'presentation_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'registration'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration'),
        'submission_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Submission Date'),
        'field'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Field of Study'),
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Check if item exists
    //
    $strsql = "SELECT id, customer_id, conference_id "
        . "FROM ciniki_conferences_presentations "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3093', 'msg'=>'Presentation does not exist'));
    }
    $item = $rc['item'];

    //
    // Check permalink if title is updated
    //
    if( isset($args['title']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);

        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_conferences_presentations "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND conference_id = '" . ciniki_core_dbQuote($ciniki, (isset($args['conference_id'])?$args['conference_id']:$item['conference_id'])) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'presentation');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3077', 'msg'=>'You already have a presentation with this title, please choose another title.'));
        }
    }

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
    // Update the Presentation in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.conferences.presentation', $args['presentation_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
        return $rc;
    }

    //
    // Check if registration set
    // 
    if( isset($args['registration']) && $args['registration'] != '' ) {
        //
        // Check if customer already exists in attendees
        //
        $strsql = "SELECT id, conference_id, customer_id, status "
            . "FROM ciniki_conferences_attendees "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND conference_id = '" . ciniki_core_dbQuote($ciniki, $item['conference_id']) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, isset($args['customer_id']) ? $args['customer_id'] : $item['customer_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $attendee = $rc['item'];
        if( !isset($rc['item']) ) {
            //
            // Add the attendee
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.conferences.attendee', array(
                'conference_id'=>$item['conference_id'],
                'customer_id'=>(isset($args['customer_id']) ? $args['customer_id'] : $item['customer_id']),
                'status'=>$args['registration'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
                return $rc;
            }
            $attendee_id = $rc['id'];
        } elseif( $attendee['status'] != $args['registration'] ) {
            //
            // Update the attendee
            //
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.conferences.attendee', $attendee['id'], array(
                'status'=>$args['registration'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.conferences');
                return $rc;
            }
        }
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
