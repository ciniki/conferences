<?php
//
// Description
// ===========
// This method will return all the information about an presentation.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the presentation is attached to.
// presentation_id:          The ID of the presentation to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_presentationReviewerPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'reviewer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reviewer'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationReviewerGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the reviewer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], 
        array('customer_id'=>$args['reviewer_id'], 'phones'=>'no', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $reviewer = $rc['customer'];

    //
    // Generate the PDF
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'templates', 'reviewerPresentationsPDF');
    $rc = ciniki_conferences_templates_reviewerPresentationsPDF($ciniki, $args['business_id'], $args['reviewer_id'], $args['conference_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $conference = $rc['conference'];

    $title = $reviewer['display_name'] . '-' . $conference['name'];

    $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($filename . '.pdf', 'D');
    }

    return array('stat'=>'exit');
}
?>
