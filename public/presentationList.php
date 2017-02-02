<?php
//
// Description
// -----------
// This method will return the list of Presentations for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Presentation for.
//
// Returns
// -------
//
function ciniki_conferences_presentationList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.presentationList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
    // Get the list of presentations
    //
    $strsql = "SELECT ciniki_conferences_presentations.id, "
        . "ciniki_conferences_presentations.conference_id, "
        . "ciniki_conferences_presentations.customer_id, "
        . "ciniki_customers.display_name, "
        . "ciniki_customer_emails.email, "
        . "ciniki_conferences_presentations.presentation_number, "
        . "ciniki_conferences_presentations.presentation_type, "
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
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "ciniki_customers.id = ciniki_customer_emails.id "
            . "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] > 0 ) {
        $strsql .= "AND ciniki_conferences_presentations.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "ORDER BY submission_date ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'presentations', 'fname'=>'id', 
            'fields'=>array('id', 'conference_id', 'customer_id', 'presentation_number', 'presentation_type', 
                'status', 'status_text', 'submission_date', 'field', 'title', 'display_name', 'permalink', 'description'),
             'maps'=>array(
                'status_text'=>$maps['presentation']['status'],
             )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $email_list = '';
    if( isset($rc['presentations']) ) {
        $presentations = $rc['presentations'];
        foreach($presentations as $pid => $presentation) {
            $presentations[$pid]['display_title'] = sprintf("#%03d: ", $presentation['presentation_number']) . $presentation['title'];
            $email_list .= ($email_list != '' ? ', ' : '') . '"' . $presentation['display_name'] . '" ' . $presentation['email'];
        }
    } else {
        $presentations = array();
    }

    return array('stat'=>'ok', 'presentations'=>$presentations, 'emails'=>$email_list);
}
?>
