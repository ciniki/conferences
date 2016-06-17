<?php
//
// Description
// ===========
// This method will return all the information about an attendee.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the attendee is attached to.
// attendee_id:          The ID of the attendee to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_attendeeGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'attendee_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Attendee'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.attendeeGet');
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
    // Return default for new Attendee
    //
    if( $args['attendee_id'] == 0 ) {
        $attendee = array('id'=>0,
            'conference_id'=>'',
            'customer_id'=> (isset($args['customer_id']) ? $args['customer_id'] : 0),
            'status'=>'0',
        );
    }

    //
    // Get the details for an existing Attendee
    //
    else {
        $strsql = "SELECT ciniki_conferences_attendees.id, "
            . "ciniki_conferences_attendees.conference_id, "
            . "ciniki_conferences_attendees.customer_id, "
            . "ciniki_conferences_attendees.status "
            . "FROM ciniki_conferences_attendees "
            . "WHERE ciniki_conferences_attendees.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences_attendees.id = '" . ciniki_core_dbQuote($ciniki, $args['attendee_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'attendee');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3501', 'msg'=>'Attendee not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['attendee']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3502', 'msg'=>'Unable to find Attendee'));
        }
        $attendee = $rc['attendee'];
    }

    if( $attendee['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array('customer_id'=>$attendee['customer_id'], 'phones'=>'yes', 'emails'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $attendee['customer'] = $rc['customer'];
        $attendee['customer_details'] = $rc['details'];
    }

    return array('stat'=>'ok', 'attendee'=>$attendee);
}
?>
