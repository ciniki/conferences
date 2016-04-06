<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_conferences_hooks_checkObjectUsed($ciniki, $business_id, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	// Set the default to not used
	$used = 'no';
	$count = 0;
	$msg = '';

	if( $args['object'] == 'ciniki.customers.customer' ) {
		//
		// Check the invoice presentation submissions
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_conferences_presentations "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count item" . ($count==1?'':'s') . " in conference presentations for this customer.";
		}
		//
		// Check the reviewers
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_conferences_reviewers "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count item" . ($count==1?'':'s') . " in conferences this customer is a review for.";
		}
		//
		// Check the reviewer presentations
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_conferences_reviewer_presentations "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count item" . ($count==1?'':'s') . " in conference presentations this customer is a reviewer for.";
		}
	}

	return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
