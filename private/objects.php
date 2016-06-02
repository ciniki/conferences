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
function ciniki_conferences_objects($ciniki) {
	
	$objects = array();
	$objects['conference'] = array(
		'name'=>'Conference',
        'o_name'=>'conference',
        'o_container'=>'conferences',
		'sync'=>'yes',
		'table'=>'ciniki_conferences',
		'fields'=>array(
			'name'=>array('name'=>'Name'),
			'permalink'=>array('name'=>'Permalink'),
			'status'=>array('name'=>'Status', 'default'=>'10'),
			'flags'=>array('name'=>'Flags', 'default'=>'0'),
			'start_date'=>array('name'=>'Start Date'),
			'end_date'=>array('name'=>'End Date'),
			'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
			'description'=>array('name'=>'Description', 'default'=>''),
			'imap_mailbox'=>array('name'=>'IMAP Mailbox', 'default'=>''),
			'imap_username'=>array('name'=>'IMAP Username', 'default'=>''),
			'imap_password'=>array('name'=>'IMAP Password', 'default'=>''),
			'imap_subject'=>array('name'=>'IMAP Subject', 'default'=>''),
			),
		'history_table'=>'ciniki_conferences_history',
		);
	$objects['attendee'] = array(
		'name'=>'Attendee',
        'o_name'=>'attendee',
        'o_container'=>'attendees',
		'sync'=>'yes',
		'table'=>'ciniki_conferences_attendees',
		'fields'=>array(
			'conference_id'=>array('name'=>'Conference', 'ref'=>'ciniki.conferences.conference'),
			'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
			'status'=>array('name'=>'Status', 'default'=>'0'),
			),
		'history_table'=>'ciniki_conferences_history',
		);
	$objects['presentation'] = array(
		'name'=>'Presentation',
        'o_name'=>'presentation',
        'o_container'=>'presentations',
		'sync'=>'yes',
		'table'=>'ciniki_conferences_presentations',
		'fields'=>array(
			'conference_id'=>array('name'=>'Conference', 'ref'=>'ciniki.conferences.conference'),
			'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
			'presentation_number'=>array('name'=>'Type', 'default'=>'0'),
			'presentation_type'=>array('name'=>'Type'),
			'status'=>array('name'=>'Status', 'default'=>'10'),
			'registration'=>array('name'=>'Registration', 'default'=>'0'),
			'submission_date'=>array('name'=>'Submission Date'),
			'field'=>array('name'=>'Field of Study', 'default'=>''),
			'title'=>array('name'=>'Title'),
			'permalink'=>array('name'=>'Permalink'),
			'description'=>array('name'=>'Description', 'default'=>''),
			),
		'history_table'=>'ciniki_conferences_history',
		);
	$objects['presentationreview'] = array(
		'name'=>'Presentation Review',
        'o_name'=>'presentationreview',
        'o_container'=>'presentationreviews',
		'sync'=>'yes',
		'table'=>'ciniki_conferences_presentation_reviews',
		'fields'=>array(
			'conference_id'=>array('name'=>'Conference', 'ref'=>'ciniki.conferences.conference'),
			'presentation_id'=>array('name'=>'Presentation', 'ref'=>'ciniki.conferences.presentation'),
			'customer_id'=>array('name'=>'Reviewer', 'ref'=>'ciniki.customers.customer'),
			'vote'=>array('name'=>'Vote', 'default'=>'0'),
			'notes'=>array('name'=>'Notes', 'default'=>''),
			),
		'history_table'=>'ciniki_conferences_history',
		);
	$objects['cfplog'] = array(
		'name'=>'CFP Log',
        'o_name'=>'cfplog',
        'o_container'=>'cfplogs',
		'sync'=>'yes',
		'table'=>'ciniki_conferences_cfplogs',
		'fields'=>array(
			'conference_id'=>array('name'=>'Conference', 'ref'=>'ciniki.conferences.conference'),
			'name'=>array('name'=>'Name'),
			'url'=>array('name'=>'URL', 'default'=>''),
			'email'=>array('name'=>'Email', 'default'=>''),
			'sent_date'=>array('name'=>'Date', 'default'=>''),
			'notes'=>array('name'=>'Notes', 'default'=>''),
			),
		'history_table'=>'ciniki_conferences_history',
		);
	$objects['cfplogtag'] = array(
		'name'=>'CFP Log Tag',
        'o_name'=>'tag',
        'o_container'=>'tags',
		'sync'=>'yes',
		'table'=>'ciniki_conferences_tags',
		'fields'=>array(
			'conference_id'=>array('name'=>'Conference', 'ref'=>'ciniki.conferences.conference'),
			'tag_type'=>array('name'=>'Tag Type'),
			'tag_name'=>array('name'=>'Tag Name'),
			'permalink'=>array('name'=>'Permalink'),
			),
		'history_table'=>'ciniki_conferences_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
