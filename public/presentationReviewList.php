<?php
//
// Description
// -----------
// This method will return the list of Presentation Reviews for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Presentation Review for.
//
// Returns
// -------
//
function ciniki_conferences_presentationReviewList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.presentationReviewList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of presentationreviews
    //
    $strsql = "SELECT ciniki_conferences_presentation_reviews.id, "
        . "ciniki_conferences_presentation_reviews.conference_id, "
        . "ciniki_conferences_presentation_reviews.presentation_id, "
        . "ciniki_conferences_presentation_reviews.customer_id, "
        . "ciniki_conferences_presentation_reviews.vote, "
        . "ciniki_conferences_presentation_reviews.notes "
        . "FROM ciniki_conferences_presentation_reviews "
        . "WHERE ciniki_conferences_presentation_reviews.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'presentationreviews', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'presentation_id', 'customer_id', 'vote', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['presentationreviews']) ) {
        $presentationreviews = $rc['presentationreviews'];
    } else {
        $presentationreviews = array();
    }

    return array('stat'=>'ok', 'presentationreviews'=>$presentationreviews);
}
?>
