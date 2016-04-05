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
	$maps['presentation'] = array(
        'presentation_type'=>array(
            '10'=>'Individual Paper',
            '20'=>'Panel',
            ),
        'status'=>array(
            '10'=>'Submitted',
            '30'=>'Accepted',
            '50'=>'Rejected',
		));

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
