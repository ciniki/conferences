<?php
//
// Description
// ===========
// This method will return all the information about an cfp log.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the cfp log is attached to.
// cfplog_id:          The ID of the cfp log to get the details for.
//
// Returns
// -------
//
function ciniki_conferences_CFPLogGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'cfplog_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'CFP Log'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
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
    $rc = ciniki_conferences_checkAccess($ciniki, $args['business_id'], 'ciniki.conferences.CFPLogGet');
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
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new CFP Log
    //
    if( $args['cfplog_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimeZone($intl_timezone));
        $cfplog = array('id'=>0,
            'conference_id'=>'',
            'name'=>'',
            'url'=>'',
            'email'=>'',
            'sent_date'=>$dt->format($php_date_format),
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing CFP Log
    //
    else {
        $strsql = "SELECT ciniki_conferences_cfplogs.id, "
            . "ciniki_conferences_cfplogs.conference_id, "
            . "ciniki_conferences_cfplogs.name, "
            . "ciniki_conferences_cfplogs.url, "
            . "ciniki_conferences_cfplogs.email, "
            . "DATE_FORMAT(ciniki_conferences_cfplogs.sent_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sent_date, "
            . "ciniki_conferences_cfplogs.notes "
            . "FROM ciniki_conferences_cfplogs "
            . "WHERE ciniki_conferences_cfplogs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_conferences_cfplogs.id = '" . ciniki_core_dbQuote($ciniki, $args['cfplog_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'cfplog');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.5', 'msg'=>'CFP Log not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['cfplog']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.6', 'msg'=>'Unable to find CFP Log'));
        }
        $cfplog = $rc['cfplog'];

        //
        // Get the categories
        //
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_conferences_cfplog_tags "
            . "WHERE cfplog_id = '" . ciniki_core_dbQuote($ciniki, $args['cfplog_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.conferences', array(
            array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tags']['tag_type'] == 10 ) {
                    $cfplog['categories'] = $tags['tags']['lists'];
                }
            }
        }
    }

    $rsp = array('stat'=>'ok', 'cfplog'=>$cfplog);

    //
    // Get all the categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.conferences', $args['business_id'], 'ciniki_conferences_cfplog_tags', 10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.7', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['categories'] = $rc['tags'];
        }
    }

    return $rsp;
}
?>
