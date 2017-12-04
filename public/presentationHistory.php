<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an presentation.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// presentation_id:          The ID of the presentation to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Presentation Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_conferences_presentationHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'presentation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Presentation'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.presentationHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'registration' ) {
        $strsql = "SELECT ciniki_conferences_attendees.id "
            . "FROM ciniki_conferences_presentations, ciniki_conferences_attendees "
            . "WHERE ciniki_conferences_presentations.id = '" . ciniki_core_dbQuote($ciniki, $args['presentation_id']) . "' "
            . "AND ciniki_conferences_presentations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_conferences_presentations.customer_id = ciniki_conferences_attendees.customer_id "
            . "AND ciniki_conferences_presentations.conference_id = ciniki_conferences_attendees.conference_id "
            . "AND ciniki_conferences_attendees.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.conferences', 'attendee');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.conferences', 'ciniki_conferences_history', $args['tnid'], 'ciniki_conferences_attendees', $rc['attendee']['id'], 'status'); 
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.conferences', 'ciniki_conferences_history', $args['tnid'], 'ciniki_conferences_presentations', $args['presentation_id'], $args['field']);
}
?>
