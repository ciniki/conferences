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
    // Select any active conferences
    //
    $strsql = "SELECT ciniki_conferences.id, "
        . "ciniki_conferences.name "
        . "FROM ciniki_conferences "
        . "WHERE ciniki_conferences.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND status = 10 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.conferences', array(
        array('container'=>'conferences', 'fname'=>'id', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['conferences']) && count($rc['conferences']) > 0 ) {
        foreach($rc['conferences'] as $conf) {
            $menu_item = array(
                'priority'=>8200,
                'label'=>$conf['name'], 
                'edit'=>array('app'=>'ciniki.conferences.main', 'args'=>array('conference_id'=>$conf['id'])),
                );
            $rsp['menu_items'][] = $menu_item;
        }
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
            'priority'=>2200,
            'label'=>'Conferences', 
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
