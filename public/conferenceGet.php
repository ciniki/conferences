<?php
//
// Description
// ===========
// This method will return all the information about an conference.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the conference is attached to.
// conference_id:          The ID of the conference to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_conferenceGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'cfplogs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'CFP Log Entries'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.conferenceGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Return default for new Conference
    //
    if( $args['conference_id'] == 0 ) {
        $conference = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'flags'=>'0',
            'start_date'=>'',
            'end_date'=>'',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Conference
    //
    else {
        $strsql = "SELECT ciniki_conferences.id, "
            . "ciniki_conferences.name, "
            . "ciniki_conferences.permalink, "
            . "ciniki_conferences.status, "
            . "ciniki_conferences.flags, "
            . "DATE_FORMAT(ciniki_conferences.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
            . "DATE_FORMAT(ciniki_conferences.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "ciniki_conferences.synopsis, "
            . "ciniki_conferences.description "
            . "FROM ciniki_conferences "
            . "WHERE ciniki_conferences.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences.id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'conference');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2840', 'msg'=>'Conference not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['conference']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2841', 'msg'=>'Unable to find Conference'));
        }
        $conference = $rc['conference'];
        

        //
        // Check if CFP logs should be returned
        //
        if( isset($args['cfplogs']) && $args['cfplogs'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_cfplogs.id, "
                . "ciniki_conferences_cfplogs.name, "
                . "ciniki_conferences_cfplogs.url, "
                . "DATE_FORMAT(ciniki_conferences_cfplogs.sent_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sent_date, "
                . "ciniki_conferences_cfplogs.email "
                . "FROM ciniki_conferences_cfplogs "
                . "WHERE ciniki_conferences_cfplogs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_cfplogs.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'cfplogs', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'url', 'email', 'sent_date')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['cfplogs']) ) {
                $conference['cfplogs'] = $rc['cfplogs'];
            } else {
                $conference['cfplogs'] = array();
            }
        }
    }

    return array('stat'=>'ok', 'conference'=>$conference);
}
?>
