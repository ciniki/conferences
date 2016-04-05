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
        'presentations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latest Presentations'),
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'),
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
	// Load conference maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'maps');
	$rc = ciniki_conferences_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

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
            . "ciniki_conferences.status AS status_text, "
            . "ciniki_conferences.flags, "
            . "DATE_FORMAT(ciniki_conferences.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
            . "DATE_FORMAT(ciniki_conferences.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "ciniki_conferences.synopsis, "
            . "ciniki_conferences.description, "
            . "ciniki_conferences.imap_mailbox, "
            . "ciniki_conferences.imap_username, "
            . "ciniki_conferences.imap_password, "
            . "ciniki_conferences.imap_subject "
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
        if( isset($maps['conference']['status'][$conference['status_text']]) ) {
            $conference['status_text'] = $maps['conference']['status'][$conference['status_text']];
        }

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

        //
        // Check if presentations should be returned
        //
        if( isset($args['presentations']) && $args['presentations'] == 'yes' ) {
            $strsql = "SELECT ciniki_conferences_presentations.id, "
                . "ciniki_conferences_presentations.title, "
                . "ciniki_conferences_presentations.customer_id, "
                . "ciniki_conferences_presentations.status, "
                . "ciniki_conferences_presentations.status AS status_text, "
                . "ciniki_customers.display_name "
                . "FROM ciniki_conferences_presentations "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_conferences_presentations.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "ORDER BY submission_date DESC "
                . "LIMIT 5 "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'presentations', 'fname'=>'id', 
                    'fields'=>array('id', 'title', 'customer_id', 'status', 'status_text', 'display_name'),
                    'maps'=>array('status_text'=>$maps['presentation']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['presentations']) ) {
                $conference['presentations'] = $rc['presentations'];
            } else {
                $conference['presentations'] = array();
            }
        }

        //
        // Get the stats for the conference
        //
        if( isset($args['stats']) && $args['stats'] == 'yes' ) {
            $strsql = "SELECT status, COUNT(status) AS num_presentations "
                . "FROM ciniki_conferences_presentations "
                . "WHERE ciniki_conferences_presentations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_conferences_presentations.conference_id = '" . ciniki_core_dbQuote($ciniki, $args['conference_id']) . "' "
                . "GROUP BY status "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.conferences', array(
                array('container'=>'stats', 'fname'=>'status', 'fields'=>array('status', 'num_presentations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $conference['presentation_stats'] = array();
            foreach($maps['presentation']['status'] as $status => $status_text) {
                $conference['presentation_stats'][$status] = array(
                    'name'=>$status_text,
                    'count'=>(isset($rc['stats'][$status]['num_presentations'])?$rc['stats'][$status]['num_presentations']:0),
                    );
            }
        }
    }

    return array('stat'=>'ok', 'conference'=>$conference);
}
?>
