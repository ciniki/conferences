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
// tnid:         The ID of the tenant the presentation is attached to.
// presentation_id:          The ID of the presentation to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_reviewerGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'reviewer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reviewer'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.reviewerGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'maps');
    $rc = ciniki_conferences_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $reviewer = array();

    //
    // Get the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
        array('customer_id'=>$args['reviewer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $reviewer['customer_details'] = $rc['details'];

    //
    // Lookup reviews
    //
    $strsql = "SELECT ciniki_conferences_presentation_reviews.id, "
        . "ciniki_conferences_presentation_reviews.conference_id, "
        . "ciniki_conferences_presentation_reviews.customer_id, "
        . "ciniki_conferences_presentations.title, "
        . "ciniki_conferences_presentations.presentation_number, "
        . "ciniki_customers.display_name, "
        . "ciniki_conferences_presentation_reviews.vote, "
        . "ciniki_conferences_presentation_reviews.vote AS vote_text "
        . "FROM ciniki_conferences_presentation_reviews "
        . "INNER JOIN ciniki_conferences_presentations ON ("
            . "ciniki_conferences_presentation_reviews.presentation_id = ciniki_conferences_presentations.id "
            . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
            . "AND ciniki_conferences_presentations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_conferences_presentation_reviews.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_conferences_presentation_reviews.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['reviewer_id']) . "' "
        . "AND ciniki_conferences_presentation_reviews.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'reviews', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'customer_id', 'presentation_number', 'title', 'display_name', 'vote', 'vote_text'),
            'maps'=>array('vote_text'=>$maps['presentationreview']['vote']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.35', 'msg'=>'Unable to get list of reviews', 'err'=>$rc['err']));
    }
    if( isset($rc['reviews']) ) {
        $reviewer['reviews'] = $rc['reviews'];
        foreach($reviewer['reviews'] as $rid => $presentation) {
            $reviewer['reviews'][$rid]['display_title'] = sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title'];
        }
    } else {
        $reviewer['reviews'] = array();
    }

    //
    // Check for any messages sent
    //
    if( isset($ciniki['tenant']['modules']['ciniki.mail']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], array('object'=>'ciniki.conferences.conferencereviewer', 'object_id'=>$args['conference_id'] . '-' . $args['reviewer_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            $reviewer['messages'] = $rc['messages'];
        }
    } 

    return array('stat'=>'ok', 'reviewer'=>$reviewer);
}
?>
