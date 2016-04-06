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
function ciniki_conferences_presentationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'presentation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Presentation'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
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
    // Return default for new Presentation
    //
    if( $args['presentation_id'] == 0 ) {
        $presentation = array('id'=>0,
            'conference_id'=>'',
            'customer_id'=>'',
            'presentation_type'=>'',
            'status'=>'10',
            'submission_date'=>'',
            'field'=>'',
            'title'=>'',
            'permalink'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Presentation
    //
    else {
        $strsql = "SELECT ciniki_conferences_presentations.id, "
            . "ciniki_conferences_presentations.conference_id, "
            . "ciniki_conferences_presentations.customer_id, "
            . "ciniki_customers.display_name, "
            . "ciniki_conferences_presentations.presentation_type, "
            . "ciniki_conferences_presentations.presentation_type AS presentation_type_text, "
            . "ciniki_conferences_presentations.status, "
            . "ciniki_conferences_presentations.status AS status_text, "
            . "ciniki_conferences_presentations.submission_date, "
            . "ciniki_conferences_presentations.field, "
            . "ciniki_conferences_presentations.title, "
            . "ciniki_conferences_presentations.permalink, "
            . "ciniki_conferences_presentations.description "
            . "FROM ciniki_conferences_presentations "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_conferences_presentations.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences_presentations.id = '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'presentations', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'customer_id', 'display_name', 'presentation_type', 'presentation_type_text',
                    'status', 'status_text', 'submission_date', 'field', 'title', 'permalink', 'description'),
                'maps'=>array(
                    'presentation_type_text'=>$maps['presentation']['presentation_type'],
                    'status_text'=>$maps['presentation']['status'],
                    ),
                'utctotz'=>array('submission_date'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone)),
                 ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3081', 'msg'=>'Presentation not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['presentations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3082', 'msg'=>'Unable to find Presentation'));
        }
        $presentation = $rc['presentations'][0];

        //
        // Lookup reviews
        //
        $strsql = "SELECT ciniki_conferences_presentation_reviews.id, "
            . "ciniki_conferences_presentation_reviews.conference_id, "
            . "ciniki_conferences_presentation_reviews.customer_id, "
            . "ciniki_customers.display_name, "
            . "ciniki_conferences_presentation_reviews.vote, "
            . "ciniki_conferences_presentation_reviews.vote AS vote_text "
            . "FROM ciniki_conferences_presentation_reviews "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_conferences_presentation_reviews.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_conferences_presentation_reviews.presentation_id = '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
            . "AND ciniki_conferences_presentation_reviews.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'reviews', 'fname'=>'id', 
                'fields'=>array('id', 'conference_id', 'customer_id', 'display_name', 'vote', 'vote_text'),
                'maps'=>array('vote_text'=>$maps['presentationreview']['vote']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3188', 'msg'=>'Unable to get list of reviews', 'err'=>$rc['err']));
        }
        if( isset($rc['reviews']) ) {
            $presentation['reviews'] = $rc['reviews'];
        } else {
            $presentation['reviews'] = array();
        }
    }

    return array('stat'=>'ok', 'presentation'=>$presentation);
}
?>
