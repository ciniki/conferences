<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_conferences_maps($ciniki) {
	$maps = array();
	$maps['conference'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Archived',
            ),
        'flags'=>array(
            0x01=>'Visible',
            0x10=>'Open for proposals',
            0x20=>'Open for registrations',
		));

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
