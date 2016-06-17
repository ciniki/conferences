//
// This app will handle the listing, additions and deletions of conferences.  These are associated business.
//
function ciniki_conferences_main() {
    //
    // conferences panel
    //
    this.menu = new M.panel('Conferences',
        'ciniki_conferences_main', 'menu',
        'mc', 'medium', 'sectioned', 'ciniki.conferences.main.menu');
    this.menu.sections = {
        'conferences':{'label':'Active Conferences', 'type':'simplegrid', 'num_cols':1,
            'noData':'No active conferences',
            'addTxt':'Add Conference',
            'addFn':'M.ciniki_conferences_main.edit.edit(\'M.ciniki_conferences_main.menu.open();\',0);',
            },
        };
    this.menu.sectionData = function(s) { return this.data[s]; }
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        return d.name;
    };
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_conferences_main.conference.open(\'M.ciniki_conferences_main.menu.open();\',\'' + d.id + '\');';
    };
    this.menu.open = function(cb) {
        this.data = {};
        M.api.getJSONCb('ciniki.conferences.conferenceList', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.menu.addButton('add', 'Add', 'M.ciniki_conferences_main.edit.edit(\'M.ciniki_conferences_main.menu.open();\');');
    this.menu.addClose('Back');

    //
    // The conference panel 
    //
    this.conference = new M.panel('Conference',
        'ciniki_conferences_main', 'conference',
        'mc', 'large narrowaside', 'sectioned', 'ciniki.conferences.conferences.conference');
    this.conference.data = {};
    this.conference.conference_id = 0;
    this.conference.sections = {
        'info':{'label':'Conference', 'aside':'yes', 'list':{
            'name':{'label':'Name'},
            'status_text':{'label':'Status'},
            'start_date':{'label':'Start'},
            'end_date':{'label':'End'},
        }},
        'presentation_stats':{'label':'Presentations', 'aside':'yes', 'type':'simplegrid', 'num_cols':1},
        'presentation_types':{'label':'Types', 'aside':'yes', 'type':'simplegrid', 'num_cols':1},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'presentations', 'tabs':{
            'attendees':{'label':'Attendees', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"attendees");'},
            'reviewers':{'label':'Reviewers', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"reviewers");'},
            'presentations':{'label':'Presentations', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations");'},
            'cfplogs':{'label':'CFP', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"cfplogs");'},
            }},
        '_attendeetabs':{'label':'', 'type':'paneltabs', 'selected':'all', 
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='attendees'?'yes':'no';},
            'tabs':{
                'all':{'label':'All', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"attendees","");'},
                'willregister':{'label':'Will Register', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"attendees","willregister");'},
                'registered':{'label':'Registered', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"attendees","registered");'},
                'notregistering':{'label':'Not Registered', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"attendees","notregistering");'},
            }},
        'attendees':{'label':'Attendees', 'type':'simplegrid', 'num_cols':3, 
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='attendees'?'yes':'no';},
            'sortable':'yes',
            'sortTypes':['alttext', 'text', 'text'],
            'headerValues':['Name', 'Presenter', 'Status'],
            'cellClasses':['multiline', '', 'multiline'],
            'noData':'No attendees',
            'addTxt':'Add Attendee',
            'addFn':'M.ciniki_conferences_main.attendee.edit(\'M.ciniki_conferences_main.conference.open();\',0,M.ciniki_conferences_main.conference.conference_id);',
            },
        '_presentationtabs':{'label':'', 'type':'paneltabs', 'selected':'all', 
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations'?'yes':'no';},
            'tabs':{
                'all':{'label':'All', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations","all");'},
                'submitted':{'label':'Submitted', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations","submitted");'},
                'accepted':{'label':'Accepted', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations","accepted");'},
                'rejected':{'label':'Rejected', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations","rejected");'},
                'papers':{'label':'Papers', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations","papers");'},
                'panels':{'label':'Panels', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations","panels");'},
            }},
        '_registrationtabs':{'label':'', 'type':'paneltabs', 'selected':'all', 
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations'?'yes':'no';},
            'count':function(tab) {
                console.log(M.ciniki_conferences_main.conference.data);
                switch (tab) {
                    case 'unknown': return (M.ciniki_conferences_main.conference.data.registration_statuses[0] != null ? M.ciniki_conferences_main.conference.data.registration_statuses[0].count : 0); break;
                    case 'willregister': return (M.ciniki_conferences_main.conference.data.registration_statuses[10] != null ? M.ciniki_conferences_main.conference.data.registration_statuses[10].count : 0); break;
                    case 'registered': return (M.ciniki_conferences_main.conference.data.registration_statuses[30] != null ? M.ciniki_conferences_main.conference.data.registration_statuses[30].count : 0); break;
                    case 'notregistering': return (M.ciniki_conferences_main.conference.data.registration_statuses[50] != null ? M.ciniki_conferences_main.conference.data.registration_statuses[50].count : 0); break;
                }
                return '';
            },
            'tabs':{
                'all':{'label':'All', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations",null,"all");'},
                'unknown':{'label':'Unknown', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations",null,"unknown");'},
                'willregister':{'label':'Will Register', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations",null,"willregister");'},
                'registered':{'label':'Registered', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations",null,"registered");'},
                'notregistering':{'label':'Not Registering', 'fn':'M.ciniki_conferences_main.conference.open(null,null,"presentations",null,"notregistering");'},
            }},
        'presentationsearch':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3,
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations'?'yes':'no';},
            'hint':'search customer or presentation name',
            'noData':'No presentations found',
            'headerValues':['Title', 'Reviews', 'Status'],
            'cellClasses':['multiline', 'multiline', 'multiline'],
            },
        'presentations':{'label':'Presentations', 'type':'simplegrid', 'num_cols':3, 
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations'?'yes':'no';},
            'sortable':'yes',
            'sortTypes':['altnumber', 'altnumber', 'altnumber'],
            'headerValues':['Title', 'Reviews', 'Status'],
            'cellClasses':['multiline', 'multiline', 'multiline'],
            'noData':'No Submissions',
            },
        'emails':{'label':'Emails', 'type':'html',
            'visible':function() {return (M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations' || M.ciniki_conferences_main.conference.sections._tabs.selected=='attendees')?'yes':'no';},
            },
        'cfplogs':{'label':'Call For Proposals', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='cfplogs'?'yes':'no';},
            'cellClasses':['multiline', ''],
            'headerValues':['Name', 'Date'],
            'noData':'No Call For Proposals',
            'addTxt':'Add Call For Proposal',
            'addFn':'M.ciniki_conferences_main.cfplog.edit(\'M.ciniki_conferences_main.conference.open();\',0,M.ciniki_conferences_main.conference.conference_id);',
        },
        'reviewers':{'label':'Reviewers', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='reviewers'?'yes':'no';},
            'headerValues':['Name', 'Votes'],
            'cellClasses':['', ''],
            'noData':'No Reviewers',
        },
    };
    this.conference.sectionData = function(s) {
        if( s == 'info' ) { return this.sections[s].list; }
        if( s == 'emails' ) { return this.data[s].replace(/\n/g, '<br/>'); }
        return this.data[s];
    };
    this.conference.noData = function(s) {
        if( this.sections[s].noData != null ) { return this.sections[s].noData; }
        return null;
    }
    this.conference.listLabel = function(s, i, d) {
        return d.label;
    };
    this.conference.listValue = function(s, i, d) {
        return this.data[i];
    };
    this.conference.cellSortValue = function(s, i, j, d) {
        if( s == 'attendees' ) {
            switch(j) {
                case 0: return d.sort_name;
                case 1: return d.status;
            }
        }
        if( s == 'presentations' ) {
            switch(j) {
                case 0: return d.presentation_number;
                case 1: 
                    if( d.total_reviews == 0 ) { return 0; }
                    return Math.floor((parseInt(d.votes_received)/parseInt(d.total_reviews)) * 10)+parseInt(d.total_reviews);
                case 2: return d.status;
            }
        }
    };
    this.conference.rowClass = function(s, i, d) {
        if( s == 'reviewers' ) {
            if( d.votes_received < d.total_reviews ) {
                if( d.votes_received > 0 ) {
                    return 'statusorange';
                } 
                return 'statusred';
            }
            return 'statusgreen';
        }
        if( s == 'attendees' ) {
            if( d.status == 0 ) { return 'statusgrey'; }
            if( d.status == 10 ) { return 'statusorange'; }
            if( d.status == 30 ) { return 'statusgreen'; }
            if( d.status == 50 ) { return 'statusred'; }
        }
        if( s == 'presentations' || s == 'presentationsearch' ) {
            if( d.status == 50 || d.registration == 50 ) { return 'statusred'; }
            else if( d.status == 30 && d.registration == 30 ) { return 'statusgreen'; }
            else if( d.status == 30 && d.registration < 10 ) { return 'statusgrey'; }
            else if( d.status == 30 && d.registration < 30 ) { return 'statusorange'; }
        }
        return '';
    };
    this.conference.cellValue = function(s, i, j, d) {
        if( s == 'presentation_stats' || s == 'presentation_types' ) {
            return d.name + ' <span class="count">' + d.count + '</span>'; 
        } else if( s == 'attendees' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + d.company + '</span>';
                case 1: return d.presenter;
                case 2: return d.status_text;
            }
        } else if( s == 'cfplogs' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.email + ((d.email!=''&&d.url!='')?'/':'') + d.url + '</span>';
                case 1: return d.sent_date;
            }
        } else if( s == 'reviewers' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + '</span>';
                case 1: return '<span class="maintext">' + d.votes_received + '/' + d.total_reviews + '</span><span class="subtext">' + '</span>';
            }
        } else if( s == 'presentations' || s == 'presentationsearch' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.display_title + '</span><span class="subtext">' + d.display_name + '</span>';
                case 1: return '<span class="maintext">' + d.votes_received + '/' + d.total_reviews + '</span><span class="subtext">' + d.submission_date + '</span>';
                case 2: return '<span class="maintext">' + d.status_text + '</span><span class="subtext">' + d.registration_text + '</span>';
            }
        }
    };
    this.conference.rowFn = function(s, i, d) {
        if( s == 'attendees' ) {
            return 'M.ciniki_conferences_main.attendee.edit(\'M.ciniki_conferences_main.conference.open();\',\'' + d.id + '\',M.ciniki_conferences_main.conference.conference_id);';
        } else if( s == 'cfplogs' ) {
            return 'M.ciniki_conferences_main.cfplog.edit(\'M.ciniki_conferences_main.conference.open();\',\'' + d.id + '\',M.ciniki_conferences_main.conference.conference_id);';
        } else if( s == 'presentation_stats' ) {
            switch(i) {
                case '10': return 'M.ciniki_conferences_main.conference.open(null,null,"presentations","submitted");';
                case '30': return 'M.ciniki_conferences_main.conference.open(null,null,"presentations","accepted");';
                case '50': return 'M.ciniki_conferences_main.conference.open(null,null,"presentations","rejected");';
            }
        } else if( s == 'presentation_types' ) {
            switch(i) {
                case '10': return 'M.ciniki_conferences_main.conference.open(null,null,"presentations","papers");';
                case '20': return 'M.ciniki_conferences_main.conference.open(null,null,"presentations","panels");';
            }
        } else if( s == 'presentations' || s == 'presentationsearch' ) {
            return 'M.ciniki_conferences_main.presentation.open(\'M.ciniki_conferences_main.conference.open();\',\'' + d.id + '\',M.ciniki_conferences_main.conference.conference_id);';
        } else if( s == 'reviewers' ) {
            return 'M.ciniki_conferences_main.reviewer.open(\'M.ciniki_conferences_main.conference.open();\',\'' + d.customer_id + '\',M.ciniki_conferences_main.conference.conference_id);';
//            return 'M.startApp(\'ciniki.conferences.presentations\',null,\'M.ciniki_conferences_main.conference.open();\',\'mc\',{\'conference_id\':M.ciniki_conferences_main.conference.conference_id,\'conference_id\':M.ciniki_conferences_main.conference.conference_id,\'reviewer_id\':\'' + d.customer_id + '\'});';
        }
    };
    this.conference.liveSearchCb = function(s, i, value) {
        if( s == 'presentationsearch' && value != '' ) {
            M.api.getJSONBgCb('ciniki.conferences.presentationSearch', {'business_id':M.curBusinessID, 'conference_id':this.conference_id, 'start_needle':value, 'limit':'15'}, 
                function(rsp) { 
                    M.ciniki_conferences_main.conference.liveSearchShow('presentationsearch', null, M.gE(M.ciniki_conferences_main.conference.panelUID + '_' + s), rsp.presentations); 
                });
            return true;
        }
    };
    this.conference.liveSearchResultValue = function(s, f, i, j, d) { return this.cellValue(s, i, j, d); }
    this.conference.liveSearchResultRowFn = function(s, f, i, j, d) { return this.rowFn(s, i, d); }
    this.conference.liveSearchResultRowClass = function(s, f, i, d) { return this.rowClass(s, i, d); }
    this.conference.open = function(cb, cid, tab, subtab, sstab) {
        if( cid != null ) { this.conference_id = cid; }
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( subtab != null ) {
            if( this.sections._tabs.selected == 'attendees' ) {
                this.sections._attendeetabs.selected = subtab;
            } else if( this.sections._tabs.selected == 'presentations' ) {
                this.sections._presentationtabs.selected = subtab;
            }
        }
        if( sstab != null ) {
            if( this.sections._tabs.selected == 'presentations' ) {
                this.sections._registrationtabs.selected = sstab;
            }
        }
        var args = {'business_id':M.curBusinessID, 'conference_id':this.conference_id};
        if( this.sections._tabs.selected == 'attendees' ) {
            args['attendees'] = 'yes';
            if( this.sections._attendeetabs.selected != '' ) {
                switch (this.sections._attendeetabs.selected) {
                    case 'willregister': args['attendee_status'] = 10; break;
                    case 'registered': args['attendee_status'] = 30; break;
                    case 'notregistering': args['attendee_status'] = 50; break;
                }
            }
        } else if( this.sections._tabs.selected == 'reviewers' ) {
            args['reviewers'] = 'yes';
        } else if( this.sections._tabs.selected == 'presentations' ) {
            args['presentations'] = 'yes';
            switch (this.sections._presentationtabs.selected) {
                case 'submitted': args['presentation_status'] = 10; break;
                case 'accepted': args['presentation_status'] = 30; break;
                case 'rejected': args['presentation_status'] = 50; break;
                case 'papers': args['presentation_type'] = 10; break;
                case 'panels': args['presentation_type'] = 20; break;
            }
            switch (this.sections._registrationtabs.selected) {
                case 'unknown': args['registration_status'] = 0; break;
                case 'willregister': args['registration_status'] = 10; break;
                case 'registered': args['registration_status'] = 30; break;
                case 'notregistering': args['registration_status'] = 50; break;
            }
        } else if( this.sections._tabs.selected == 'cfplogs' ) {
            args['cfplogs'] = 'yes';
        }
        args['stats'] = 'yes';
        M.api.getJSONCb('ciniki.conferences.conferenceGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.conference;
            p.data = rsp.conference;
            p.data.emails = rsp.emails;
            p.refresh();
            p.show(cb);
        });
    };
    this.conference.addButton('edit', 'Edit', 'M.ciniki_conferences_main.edit.edit(\'M.ciniki_conferences_main.conference.open();\',M.ciniki_conferences_main.conference.conference_id);');
    this.conference.addClose('Back');

    //
    // The panel for editing an conference
    //
    this.edit = new M.panel('Conference',
        'ciniki_conferences_main', 'edit',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.conferences.main.edit');
    this.edit.data = null;
    this.edit.conference_id = 0;
    this.edit.sections = { 
        'general':{'label':'Service', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '50':'Archive'}},
            'start_date':{'label':'Start Date', 'type':'date'},
            'end_date':{'label':'End Date', 'type':'date'},
            }}, 
        '_imap':{'label':'Email Submissions', 'aside':'yes',
            'active':function() {return (M.curBusiness.modules['ciniki.conferences'].flags&0x02)>0?'yes':'no';},
            'fields':{
            'imap_mailbox':{'label':'Mailbox', 'type':'text'},
            'imap_username':{'label':'Username', 'type':'text'},
            'imap_password':{'label':'Password', 'type':'text'},
            'imap_subject':{'label':'Subject', 'type':'text'},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_conferences_main.edit.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_main.edit.remove();'},
            }},
        };  
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.conferences.conferenceHistory', 'args':{'business_id':M.curBusinessID, 
            'conference_id':this.conference_id, 'field':i}};
    }
    this.edit.addDropImage = function(iid) {
        M.ciniki_conferences_main.edit.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.edit.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.edit.edit = function(cb, sid) {
        this.reset();
        if( sid != null ) { this.conference_id = sid; }
        this.sections._buttons.buttons.delete.visible = (this.conference_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.conferenceGet', {'business_id':M.curBusinessID, 'conference_id':this.conference_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.edit;
            p.data = rsp.conference;
            p.refresh();
            p.show(cb);
        });
    };
    this.edit.save = function() {
        if( this.conference_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.conferences.conferenceUpdate', {'business_id':M.curBusinessID, 'conference_id':this.conference_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_conferences_main.edit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.conferenceAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                if( rsp.id > 0 ) {
                    var cb = M.ciniki_conferences_main.edit.cb;
                    M.ciniki_conferences_main.edit.close();
                    M.ciniki_conferences_main.conference.open(cb,rsp.id);
                } else {
                    M.ciniki_conferences_main.edit.close();
                }
            });
        }
    };
    this.edit.remove = function() {
        if( confirm("Are you sure you want to remove '" + this.data.name + "'?") ) {
            M.api.getJSONCb('ciniki.conferences.conferenceDelete', 
                {'business_id':M.curBusinessID, 'conference_id':this.conference_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_conferences_main.edit.close();
                });
        }
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_conferences_main.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The attendee edit panel
    //
    this.attendee = new M.panel('Conference Attendee',
        'ciniki_conferences_main', 'attendee',
        'mc', 'medium', 'sectioned', 'ciniki.conferences.main.attendee');
    this.attendee.data = null;
    this.attendee.attendee_id = 0;
    this.attendee.sections = { 
        'customer_details':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_conferences_main.attendee.edit();\',\'mc\',{\'next\':\'M.ciniki_conferences_main.attendee.add\',\'customer_id\':M.ciniki_conferences_main.attendee.data.customer_id});',
            },
        '_status':{'label':'', 'fields':{
            'status':{'label':'', 'hidelabel':'yes', 'type':'toggle', 'toggles':{'0':'Unknown', '10':'Will Register', '30':'Registered', '50':'Not Registering'}},
            }}, 
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_conferences_main.attendee.save();'},
//                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_main.attendee.remove();'},
            }},
        };  
    this.attendee.fieldValue = function(s, i, d) { return this.data[i]; }
    this.attendee.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.conferences.attendeeHistory', 'args':{'business_id':M.curBusinessID, 'attendee_id':this.attendee_id, 'field':i}};
    }
    this.attendee.sectionData = function(s) { return this.data[s]; }
    this.attendee.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
    }
    this.attendee.edit = function(cb, aid, conf_id) {
        if( conf_id != null ) { this.conference_id = conf_id; }
        if( aid != null ) { this.attendee_id = aid; }
        if( this.attendee_id == 0 ) {
            this.cb = cb;
            M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_conferences_main.attendee.add','customer_id':0});
        } else {
            M.api.getJSONCb('ciniki.conferences.attendeeGet', {'business_id':M.curBusinessID, 'attendee_id':this.attendee_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_main.attendee;
                p.data = rsp.attendee;
                p.customer_id = rsp.attendee.customer_id
                p.refresh();
                p.show(cb);
            });
        }
    }
    this.attendee.add = function(cid) {
        M.api.getJSONCb('ciniki.conferences.attendeeGet', {'business_id':M.curBusinessID, 'attendee_id':0, 'customer_id':cid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.attendee;
            p.data = rsp.attendee;
            p.customer_id = rsp.attendee.customer_id
            p.refresh();
            p.show();
        });
    }
    this.attendee.save = function() {
        if( this.attendee_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.conferences.attendeeUpdate', {'business_id':M.curBusinessID, 'attendee_id':this.attendee_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_conferences_main.attendee.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.attendeeAdd', {'business_id':M.curBusinessID, 'conference_id':this.conference_id, 'customer_id':this.customer_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_conferences_main.attendee.close();
            });
        }
    }
    this.attendee.addButton('save', 'Save', 'M.ciniki_conferences_main.attendee.save();');
    this.attendee.addClose('Cancel');

    //
    // The presentation display panel 
    //
    this.presentation = new M.panel('Presentation',
        'ciniki_conferences_main', 'presentation',
        'mc', 'large', 'sectioned', 'ciniki.conferences.main.presentation');
    this.presentation.data = {};
    this.presentation.presentation_id = 0;
    this.presentation.sections = {
        'info':{'label':'Presentation', 'list':{
            'display_title':{'label':'Title'},
            'display_name':{'label':'Presenter'},
            'status_text':{'label':'Status'},
            'registration_text':{'label':'Registration'},
            'field':{'label':'Field'},
            'presentation_type_text':{'label':'Type'},
            'submission_date':{'label':'Submitted On'},
            }},
        'full_bio':{'label':'Bio', 'type':'html'},
        'description':{'label':'Description', 'type':'html'},
        'reviews':{'label':'Reviewers', 'type':'simplegrid', 'num_cols':2,
            'addTxt':'Add Reviewer',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_conferences_main.presentation.open();\',\'mc\',{\'next\':\'M.ciniki_conferences_main.presentation.addReview\',\'customer_id\':0});',
            },
        '_buttons':{'label':'', 'buttons':{
            'edit':{'label':'Edit', 'fn':'M.ciniki_conferences_main.presentationedit.edit(\'M.ciniki_conferences_main.presentation.open();\',M.ciniki_conferences_main.presentation.presentation_id);'},
            }},
    };
    this.presentation.sectionData = function(s) {
        if( s == 'info' ) { return this.sections[s].list; }
        if( s == 'description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
        if( s == 'full_bio' ) { return this.data[s].replace(/\n/g, '<br/>'); }
        return this.data[s];
    };
    this.presentation.noData = function(s) {
        if( this.sections[s].noData != null ) { return this.sections[s].noData; }
        return null;
    }
    this.presentation.listLabel = function(s, i, d) {
        return d.label;
    };
    this.presentation.listValue = function(s, i, d) {
        if( i == 'status_text' && this.data['status'] == '10' ) {
            return this.data[i] + ' <button onclick=\'event.stopPropagation(); M.ciniki_conferences_main.presentation.accept("' + this.data['id'] + '"); return false;\'>Accept</button>'
                + ' <button onclick=\'event.stopPropagation(); M.ciniki_conferences_main.presentation.reject("' + this.data['id'] + '"); return false;\'>Reject</button>';
        }
        return this.data[i];
    };
    this.presentation.cellValue = function(s, i, j, d) {
        if( s == 'reviews' ) {
            switch (j) {
                case 0: return d.display_name;
                case 1: return d.vote_text;
            }
        }
    };
    this.presentation.rowClass = function(s, i, d) {
        if( s == 'reviews' ) {
            switch(d.vote) {
                case '0': return 'statusorange';
                case '30': return 'statusgreen';
                case '50': return 'statusred';
            }
        }
    };
    this.presentation.rowFn = function(s, i, d) {
        if( s == 'reviews' ) {
            return 'M.ciniki_conferences_main.review.edit(\'M.ciniki_conferences_main.presentation.open();\',\'' + d.id + '\');';
        }
    };
    this.presentation.open = function(cb, sid) {
        if( sid != null ) { this.presentation_id = sid; }
        M.api.getJSONCb('ciniki.conferences.presentationGet', {'business_id':M.curBusinessID, 'presentation_id':this.presentation_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.presentation;
            p.data = rsp.presentation;
            p.refresh();
            p.show(cb);
        });
    };
    this.presentation.addReview = function(cid) {
        if( cid != null && this.data.customer_id != cid ) {
            M.api.getJSONCb('ciniki.conferences.presentationReviewAdd', {'business_id':M.curBusinessID,
                'presentation_id':this.presentation_id, 'customer_id':cid, 'conference_id':this.data.conference_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_conferences_main.presentation.show();
                });
        }
    };
    this.presentation.accept = function(pid) {
        M.api.getJSONCb('ciniki.conferences.presentationUpdate', {'business_id':M.curBusinessID, 'presentation_id':pid, 'status':30}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_conferences_main.presentation.close();
        });
    };
    this.presentation.reject = function(pid) {
        M.api.getJSONCb('ciniki.conferences.presentationUpdate', {'business_id':M.curBusinessID, 'presentation_id':pid, 'status':50}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_conferences_main.presentation.close();
        });
    };
    this.presentation.addButton('edit', 'Edit', 'M.ciniki_conferences_main.presentationedit.edit(\'M.ciniki_conferences_main.presentation.open();\',M.ciniki_conferences_main.presentation.presentation_id);');
    this.presentation.addClose('Back');

    //
    // The panel for editing an presentation
    //
    this.presentationedit = new M.panel('Presentation',
        'ciniki_conferences_main', 'presentationedit',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.conferences.main.presentationedit');
    this.presentationedit.data = null;
    this.presentationedit.presentation_id = 0;
    this.presentationedit.sections = { 
        'general':{'label':'Presentation', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Submitted', '30':'Accepted', '50':'Rejected'}},
            'registration':{'label':'Registration', 'type':'toggle', 'toggles':{'0':'Unknown', '10':'Will Register', '30':'Registered', '50':'Not Registering'}},
            'field':{'label':'Field', 'type':'text'},
            'presentation_type':{'label':'Type', 'type':'toggle', 'toggles':{'10':'Individual Paper', '20':'Panel'}},
            }}, 
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_conferences_main.presentationedit.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_main.presentation.remove();'},
            }},
        };  
    this.presentationedit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.presentationedit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.conferences.presentationHistory', 'args':{'business_id':M.curBusinessID, 
            'presentation_id':this.presentation_id, 'field':i}};
    }
    this.presentationedit.edit = function(cb, pid) {
        this.reset();
        if( pid != null ) { this.presentation_id = pid; }
        this.sections._buttons.buttons.delete.visible = (this.presentation_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.presentationGet', {'business_id':M.curBusinessID, 'presentation_id':this.presentation_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.presentationedit;
            p.data = rsp.presentation;
            p.refresh();
            p.show(cb);
        });
    };
    this.presentationedit.save = function() {
        if( this.presentation_id > 0 ) {
            var c = this.serializeForm('no');
            console.log(this.presentation_id);
            if( c != '' ) {
                M.api.postJSONCb('ciniki.conferences.presentationUpdate', {'business_id':M.curBusinessID, 'presentation_id':this.presentation_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_conferences_main.presentationedit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.presentationAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                if( rsp.id > 0 ) {
                    var cb = M.ciniki_conferences_main.edit.cb;
                    M.ciniki_conferences_main.presentationedit.close();
                    M.ciniki_conferences_main.presentation.open(cb,rsp.id);
                } else {
                    M.ciniki_conferences_main.presentationedit.close();
                }
            });
        }
    };
    this.presentationedit.remove = function() {
        if( confirm("Are you sure you want to remove '" + this.data.name + "'?") ) {
            M.api.getJSONCb('ciniki.conferences.presentation.remove', 
                {'business_id':M.curBusinessID, 'presentation_id':this.presentation_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_conferences_main.presentation.close();
                });
        }
    };
    this.presentationedit.addButton('save', 'Save', 'M.ciniki_conferences_main.presentationedit.save();');
    this.presentationedit.addClose('Cancel');

    //
    // The panel for editing an presentation reviewer
    //
    this.reviewer = new M.panel('Reviewer',
        'ciniki_conferences_main', 'reviewer',
        'mc', 'medium', 'sectioned', 'ciniki.conferences.main.reviewer');
    this.reviewer.data = null;
    this.reviewer.reviewer_id = 0;
    this.reviewer.conference_id = 0;
    this.reviewer.sections = { 
        'customer_details':{'label':'Reviewer', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            },
        'reviews':{'label':'Reviews', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['multiline', 'multiline'],
            'noData':'No presentations',
            },
        '_buttons':{'label':'', 'buttons':{
            'downloadpdf':{'label':'Download PDF', 'fn':'M.ciniki_conferences_main.reviewer.openPDF();'},
            }},
        'messages':{'label':'Messages', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['multiline', 'multiline'],
            'addTxt':'Email PDF to Reviewer',
            'addFn':'M.ciniki_conferences_main.revieweremail.open(\'M.ciniki_conferences_main.reviewer.open();\',M.ciniki_conferences_main.reviewer.reviewer_id,M.ciniki_conferences_main.reviewer.conference_id);',
            },
        };
    this.reviewer.sectionData = function(s) { return this.data[s]; }
    this.reviewer.noData = function(s) { return this.sections[s].noData; }
    this.reviewer.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return d.detail.value.replace(/\n/g, '<br/>');
            }
        } else if( s == 'reviews' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.display_title + '</span><span class="subtext">' + d.display_name + '</span>';
                case 1: return d.vote_text;
            }
        } else if( s == 'messages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.message.status_text + '</span><span class="subtext">' + d.message.date_sent + '</span>';
                case 1: return '<span class="maintext">' + d.message.customer_email + '</span><span class="subtext">' + d.message.subject + '</span>';
            }
        }
    };
    this.reviewer.rowClass = function(s, i, d) {
        if( s == 'reviews' ) {
            switch(d.vote) {
                case '0': return 'statusorange';
                case '30': return 'statusgreen';
                case '50': return 'statusred';
            }
        }
    };
    this.reviewer.rowFn = function(s, i, d) {
        if( s == 'reviews' ) {
            return 'M.ciniki_conferences_main.review.edit(\'M.ciniki_conferences_main.reviewer.open();\',\'' + d.id + '\');';
        }
    };
    this.reviewer.open = function(cb, rid, cid) {
        if( rid != null ) { this.reviewer_id = rid; }
        if( cid != null ) { this.conference_id = cid; }
        M.api.getJSONCb('ciniki.conferences.reviewerGet', {'business_id':M.curBusinessID, 
            'reviewer_id':this.reviewer_id, 'conference_id':this.conference_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_main.reviewer;
                p.data = rsp.reviewer;
                p.refresh();
                p.show(cb);
            });
    };
    this.reviewer.openPDF = function(cb) {
        M.api.openFile('ciniki.conferences.reviewerPDF', {'business_id':M.curBusinessID, 'reviewer_id':this.reviewer_id, 'conference_id':this.conference_id});
    };
    this.reviewer.addClose('Back');

    //
    // The panel to send a reviewer an email
    //
    this.revieweremail = new M.panel('Email Reviewer PDF',
        'ciniki_conferences_main', 'revieweremail',
        'mc', 'medium', 'sectioned', 'ciniki.conferences.main.revieweremail');
    this.revieweremail.reviewer_id = 0;
    this.revieweremail.conference_id = 0;
    this.revieweremail.data = {};
    this.revieweremail.sections = {
        '_subject':{'label':'', 'fields':{
            'subject':{'label':'Subject', 'type':'text', 'history':'no'},
            }},
        '_textmsg':{'label':'Message', 'fields':{
            'textmsg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large', 'history':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'send':{'label':'Send', 'fn':'M.ciniki_conferences_main.revieweremail.sendEmail();'},
            }},
    };
    this.revieweremail.fieldValue = function(s, i, d) {
        return this.data[i];
    };
    this.revieweremail.open = function(cb, rid, cid) {
        this.reviewer_id = rid;
        this.conference_id = cid;
        if( M.curBusiness.modules['ciniki.conferences'].settings['reviewers-message-reviews-subject'] != null ) {
            this.data.subject = M.curBusiness.modules['ciniki.conferences'].settings['reviewers-message-reviews-subject'];
        } else {
            this.data.subject = 'Conference submissions for your review';
        }
        if( M.curBusiness.modules['ciniki.conferences'].settings['reviewers-message-reviews-content'] != null ) {
            this.data.textmsg = M.curBusiness.modules['ciniki.conferences'].settings['reviewers-message-reviews-content'];
        } else {
            this.data.textmsg = 'Please review the following submissions and let us know which you thing are approriate.';
        }
        this.refresh();
        this.show(cb);
    };
    this.revieweremail.sendEmail = function() {
        var subject = this.formFieldValue(this.sections._subject.fields.subject, 'subject');
        var textmsg = this.formFieldValue(this.sections._textmsg.fields.textmsg, 'textmsg');
        M.api.getJSONCb('ciniki.conferences.reviewerPDF', {'business_id':M.curBusinessID, 
            'reviewer_id':this.reviewer_id, 'conference_id':this.conference_id, 'subject':subject, 'content':textmsg, 'output':'pdf', 'email':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_conferences_main.revieweremail.close();
            });
    };
    this.revieweremail.addClose('Cancel');

    //
    // The panel for changing and individual review
    //
    this.review = new M.panel('Presentation',
        'ciniki_conferences_main', 'review',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.conferences.main.review');
    this.review.data = null;
    this.review.review_id = 0;
    this.review.sections = { 
        'customer_details':{'label':'Reviewer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            },
        'presentation_details':{'label':'Presentation', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            },
        'details':{'label':'', 'aside':'yes', 'fields':{
            'vote':{'label':'Vote', 'type':'toggle', 'toggles':{'0':'Undecided', '30':'Accept', '50':'Reject'}},
            }}, 
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'medium', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_conferences_main.review.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_main.review.remove();'},
            }},
        };  
    this.review.fieldValue = function(s, i, d) { return this.data[i]; }
    this.review.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.conferences.presentationReviewHistory', 'args':{'business_id':M.curBusinessID, 'review_id':this.review_id, 'field':i}};
    }
    this.review.sectionData = function(s) { return this.data[s]; }
    this.review.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return d.detail.value.replace(/\n/g, '<br/>');
            }
        } else if( s == 'presentation_details' ) {
            switch (j) {
                case 0: return d.label;
                case 1: return d.value.replace(/\n/g, '<br/>');
            }
        }
    };
    this.review.edit = function(cb, rid) {
        this.reset();
        if( rid != null ) { this.review_id = rid; }
        this.sections._buttons.buttons.delete.visible = (this.review_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.presentationReviewGet', {'business_id':M.curBusinessID, 'review_id':this.review_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_main.review;
            p.data = rsp.review;
            p.refresh();
            p.show(cb);
        });
    };
    this.review.save = function() {
        if( this.review_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.conferences.presentationReviewUpdate', {'business_id':M.curBusinessID, 'review_id':this.review_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_conferences_main.review.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.presentationReviewAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_conferences_main.review.close();
            });
        }
    };
    this.review.remove = function() {
        if( confirm("Are you sure you want to remove this review?") ) {
            M.api.getJSONCb('ciniki.conferences.presentationReviewDelete', {'business_id':M.curBusinessID, 'review_id':this.review_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_conferences_main.review.close();
            });
        }
    };
    this.review.addButton('save', 'Save', 'M.ciniki_conferences_main.review.save();');
    this.review.addClose('Cancel');

    //
    // The Call For Proposal edit panel
    //
    this.cfplog = new M.panel('Call For Proposal',
        'ciniki_conferences_main', 'cfplog',
        'mc', 'medium', 'sectioned', 'ciniki.conferences.main.cfplog');
    this.cfplog.data = null;
    this.cfplog.cfplog_id = 0;
    this.cfplog.sections = { 
        'general':{'label':'Call For Proposal', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'url':{'label':'Website', 'type':'text'},
            'email':{'label':'Email', 'type':'text'},
            'sent_date':{'label':'Submitted', 'type':'date'},
            }},
        '_categories':{'label':'Categories', 'fields':{
            'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
            }}, 
        '_notes':{'label':'Categories', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }}, 
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_conferences_main.cfplog.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_main.cfplog.remove();'},
            }},
        };  
    this.cfplog.fieldValue = function(s, i, d) { return this.data[i]; }
    this.cfplog.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.conferences.CFPLogHistory', 'args':{'business_id':M.curBusinessID, 'cfplog_id':this.cfplog_id, 'field':i}};
    }
    this.cfplog.edit = function(cb, lid, cid) {
        if( lid != null ) { this.cfplog_id = lid; }
        if( cid != null ) { this.conference_id = cid; }
        this.reset();
        this.sections._buttons.buttons.delete.visible = (this.cfplog_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.CFPLogGet', {'business_id':M.curBusinessID, 
            'cfplog_id':this.cfplog_id,
            'categories':'yes',
            }, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_main.cfplog;
                p.data = rsp.cfplog;
                p.sections._categories.fields.categories.tags = [];
                if( rsp.categories != null ) {
                    for(i in rsp.categories) {
                        p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };
    this.cfplog.save = function() {
        if( this.cfplog_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.conferences.CFPLogUpdate', {'business_id':M.curBusinessID, 'cfplog_id':this.cfplog_id, }, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_conferences_main.cfplog.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.CFPLogAdd', {'business_id':M.curBusinessID, 'conference_id':this.conference_id, }, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_conferences_main.cfplog.close();
                });
        }
    };
    this.cfplog.remove = function() {
        if( confirm("Are you sure you want to remove this CFP?") ) {
            M.api.getJSONCb('ciniki.conferences.CFPLogDelete', {'business_id':M.curBusinessID, 'cfplog_id':this.cfplog_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_conferences_main.cfplog.close();
            });
        }
    };
    this.cfplog.addButton('save', 'Save', 'M.ciniki_conferences_main.cfplog.save();');
    this.cfplog.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_conferences_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
};
