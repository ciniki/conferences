<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:        The ID of the business to get conferences for.
//
// Returns
// -------
//
function ciniki_conferences_hooks_uiSettings($ciniki, $business_id, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'settings'=>array(), 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_conferences_settings', 'business_id', $business_id, 'ciniki.conferences', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $rsp['settings'] = $rc['settings'];
    }

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['business']['modules']['ciniki.conferences'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>6200,
            'label'=>'Conference Manager', 
            'edit'=>array('app'=>'ciniki.conferences.main'),
            );
        $rsp['menu_items'][] = $menu_item;

    } 
    
    if( isset($ciniki['business']['modules']['ciniki.conferences'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>6200, 'label'=>'Conferences', 'edit'=>array('app'=>'ciniki.conferences.settings'));
    }

    return $rsp;
}
?>
