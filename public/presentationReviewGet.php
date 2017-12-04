<?php
//
// Description
// ===========
// This method will return all the information about an presentation review.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the presentation review is attached to.
// review_id:          The ID of the presentation review to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_presentationReviewGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'review_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Presentation Review'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.presentationReviewGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

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

    //
    // Return default for new Presentation Review
    //
    if( $args['review_id'] == 0 ) {
        $presentationreview = array('id'=>0,
            'conference_id'=>'',
            'presentation_id'=>'',
            'customer_id'=>'',
            'vote'=>'0',
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing Presentation Review
    //
    else {
        $strsql = "SELECT ciniki_conferences_presentation_reviews.id, "
            . "ciniki_conferences_presentation_reviews.conference_id, "
            . "ciniki_conferences_presentation_reviews.presentation_id, "
            . "ciniki_conferences_presentation_reviews.customer_id, "
            . "ciniki_conferences_presentation_reviews.vote, "
            . "ciniki_conferences_presentation_reviews.notes "
            . "FROM ciniki_conferences_presentation_reviews "
            . "WHERE ciniki_conferences_presentation_reviews.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_conferences_presentation_reviews.id = '" . ciniki_core_dbQuote($ciniki, $args['review_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'presentationreview');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.29', 'msg'=>'Presentation Review not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['presentationreview']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.30', 'msg'=>'Unable to find Presentation Review'));
        }
        $presentationreview = $rc['presentationreview'];

        //
        // Get the customer details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
            array('customer_id'=>$presentationreview['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $presentationreview['customer_details'] = $rc['details']; 

        //
        // Get the presentation details
        //
        $strsql = "SELECT ciniki_conferences_presentations.id, "
            . "ciniki_conferences_presentations.conference_id, "
            . "ciniki_conferences_presentations.presenters, "
//            . "ciniki_conferences_presentations.customer_id, "
//            . "ciniki_customers.display_name, "
            . "ciniki_conferences_presentations.presentation_number, "
            . "ciniki_conferences_presentations.presentation_type, "
            . "ciniki_conferences_presentations.presentation_type AS presentation_type_text, "
            . "ciniki_conferences_presentations.status, "
            . "ciniki_conferences_presentations.status AS status_text, "
            . "ciniki_conferences_presentations.title, "
            . "ciniki_conferences_presentations.field, "
            . "ciniki_conferences_presentations.submission_date "
            . "FROM ciniki_conferences_presentations "
//            . "LEFT JOIN ciniki_customers ON ("
//                . "ciniki_conferences_presentations.customer_id = ciniki_customers.id "
//                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                . ") "
            . "WHERE ciniki_conferences_presentations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_conferences_presentations.id = '" . ciniki_core_dbQuote($ciniki, $presentationreview['presentation_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'presentations', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'presenters', 'presentation_number', 'presentation_type', 'presentation_type_text',
                    'status', 'status_text', 'submission_date', 'field', 'title'),
                'maps'=>array(
                    'presentation_type_text'=>$maps['presentation']['presentation_type'],
                    'status_text'=>$maps['presentation']['status'],
                    ),
                'utctotz'=>array('submission_date'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone)),
                 ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.31', 'msg'=>'Presentation not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['presentations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.32', 'msg'=>'Unable to find Presentation'));
        }
        $presentation = $rc['presentations'][0];
        $presentationreview['presentation_details'] = array(
            array('label'=>'Title', 'value'=>sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title']),
            array('label'=>'Field', 'value'=>$presentation['field']),
            array('label'=>'Presenters', 'value'=>$presentation['presenters']),
            );
    }

    return array('stat'=>'ok', 'review'=>$presentationreview);
}
?>
