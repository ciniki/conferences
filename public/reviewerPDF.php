<?php
//
// Description
// ===========
// This method will return all the information about an presentation.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the presentation is attached to.
//
// Returns
// -------
//
function ciniki_conferences_reviewerPDF(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'conference_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Conference'),
        'reviewer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reviewer'),
        'subject'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Message Subject'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Message Content'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Email PDF'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'private', 'checkAccess');
    $rc = ciniki_conferences_checkAccess($ciniki, $args['tnid'], 'ciniki.conferences.reviewerPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the reviewer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
        array('customer_id'=>$args['reviewer_id'], 'phones'=>'no', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $reviewer = $rc['customer'];

    $customer_email = '';
    if( isset($rc['customer']['emails'][0]['email']['address']) ) {
        $customer_email = $rc['customer']['emails'][0]['email']['address'];
    }

    //
    // Generate the PDF
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'conferences', 'templates', 'reviewerPresentationsPDF');
    $rc = ciniki_conferences_templates_reviewerPresentationsPDF($ciniki, $args['tnid'], $args['reviewer_id'], $args['conference_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $conference = $rc['conference'];

    $title = $reviewer['display_name'] . '-' . $conference['name'];

    $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));

    if( isset($rc['pdf']) ) {
        $pdf = $rc['pdf'];
        if( $args['email'] == 'yes' && $args['subject'] != '' && $args['content'] != '' ) {
            if( $customer_email == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.36', 'msg'=>'No email specified for this reviewer'));
            }

            //
            // Add to the mail module
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
            $rc = ciniki_mail_hooks_addMessage($ciniki, $args['tnid'], array(
                'object'=>'ciniki.conferences.conferencereviewer',
                'object_id'=>$args['conference_id'] . '-' . $args['reviewer_id'],
                'customer_id'=>$args['reviewer_id'],
                'customer_email'=>$customer_email,
                'customer_name'=>(isset($reviewer['display_name'])?$reviewer['display_name']:''),
                'subject'=>$args['subject'],
                'html_content'=>$args['content'],
                'text_content'=>$args['content'],
                'attachments'=>array(array('content'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename . '.pdf')),
                ));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.conferences.37', 'msg'=>'Unable to create mail message.', 'err'=>$rc['err']));
            }
            $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$args['tnid']);

            return array('stat'=>'ok');
        } else {
            $rc['pdf']->Output($filename . '.pdf', 'D');
        }
    }

    return array('stat'=>'exit');
}
?>
