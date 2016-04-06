//
// This app will handle the listing, additions and deletions of conferences.  These are associated business.
//
function ciniki_conferences_main() {
	//
	// Panels
	//
    this.statuses = {
        '10':'Active',
        '50':'Archive',
        };
	this.init = function() {
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
				'addFn':'M.ciniki_conferences_main.conferenceEdit(\'M.ciniki_conferences_main.menuShow();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
            return d.name;
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_conferences_main.conferenceShow(\'M.ciniki_conferences_main.menuShow();\',\'' + d.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_conferences_main.conferenceEdit(\'M.ciniki_conferences_main.menuShow();\');');
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
                'reviewers':{'label':'Reviewers', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"reviewers");'},
                'presentations':{'label':'Presentations', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations");'},
                'cfplogs':{'label':'CFP', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"cfplogs");'},
                }},
            '_presentationtabs':{'label':'', 'type':'paneltabs', 'selected':'all', 
                'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations'?'yes':'no';},
                'tabs':{
                    'all':{'label':'All', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","all");'},
                    'submitted':{'label':'Submitted', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","submitted");'},
                    'accepted':{'label':'Accepted', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","accepted");'},
                    'rejected':{'label':'Rejected', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","rejected");'},
                    'papers':{'label':'Papers', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","papers");'},
                    'panels':{'label':'Panels', 'fn':'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","panels");'},
                }},
            'presentations':{'label':'Presentations', 'type':'simplegrid', 'num_cols':2, 
                'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='presentations'?'yes':'no';},
                'cellClasses':['multiline', 'multiline'],
                'noData':'No Submissions',
                },
			'cfplogs':{'label':'Call For Proposals', 'type':'simplegrid', 'num_cols':2,
                'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='cfplogs'?'yes':'no';},
                'cellClasses':['multiline', ''],
                'noData':'No Call For Proposals',
                'addTxt':'Add Call For Proposal',
                'addFn':'M.ciniki_conferences_main.cfplogEdit(\'M.ciniki_conferences_main.conferenceShow();\',M.ciniki_conferences_main.conference.conference_id,0);',
            },
			'reviewers':{'label':'Reviewers', 'type':'simplegrid', 'num_cols':2,
                'visible':function() {return M.ciniki_conferences_main.conference.sections._tabs.selected=='reviewers'?'yes':'no';},
                'cellClasses':['multiline', 'multiline'],
                'noData':'No Reviewers',
            },
		};
		this.conference.sectionData = function(s) {
            if( s == 'info' ) { return this.sections[s].list; }
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
        this.conference.headerValue = function(s, i, d) {
            if( s == 'cfplogs' ) {
                switch (i) {
                    case 0: return 'Name';
                    case 1: return 'Date';
                }
            }
        };
        this.conference.cellValue = function(s, i, j, d) {
            if( s == 'presentation_stats' || s == 'presentation_types' ) {
                return d.name + ' <span class="count">' + d.count + '</span>'; 
            } else if( s == 'cfplogs' ) {
                switch (j) {
                    case 0: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.email + ((d.email!=''&&d.url!='')?'/':'') + d.url + '</span>';
                    case 1: return d.sent_date;
                }
            } else if( s == 'presentations' ) {
                switch (j) {
                    case 0: return '<span class="maintext">' + d.title + '</span><span class="subtext">' + d.display_name + '</span>';
                    case 1: return '<span class="maintext">' + d.status_text + '</span><span class="subtext">' + d.submission_date + '</span>';
                }
            }
        };
        this.conference.rowFn = function(s, i, d) {
            if( s == 'cfplogs' ) {
                return 'M.ciniki_conferences_main.cfplogEdit(\'M.ciniki_conferences_main.conferenceShow();\',M.ciniki_conferences_main.conference.conference_id,\'' + d.id + '\');';
            } else if( s == 'presentation_stats' ) {
                switch(i) {
                    case '10': return 'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","submitted");';
                    case '30': return 'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","accepted");';
                    case '50': return 'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","rejected");';
                }
//                return 'M.startApp(\'ciniki.conferences.presentations\',null,\'M.ciniki_conferences_main.conferenceShow();\',\'mc\',{\'conference_id\':M.ciniki_conferences_main.conference.conference_id,\'status\':\'' + i + '\'});';
            } else if( s == 'presentation_types' ) {
                switch(i) {
                    case '10': return 'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","papers");';
                    case '20': return 'M.ciniki_conferences_main.conferenceShow(null,null,"presentations","panels");';
                }
//                return 'M.startApp(\'ciniki.conferences.presentations\',null,\'M.ciniki_conferences_main.conferenceShow();\',\'mc\',{\'conference_id\':M.ciniki_conferences_main.conference.conference_id,\'type\':\'' + i + '\'});';
            } else if( s == 'presentations' ) {
                return 'M.startApp(\'ciniki.conferences.presentations\',null,\'M.ciniki_conferences_main.conferenceShow();\',\'mc\',{\'conference_id\':M.ciniki_conferences_main.conference.conference_id,\'presentation_id\':\'' + d.id + '\'});';
            }
        };
        this.conference.addButton('edit', 'Edit', 'M.ciniki_conferences_main.conferenceEdit(\'M.ciniki_conferences_main.conferenceShow();\',M.ciniki_conferences_main.conference.conference_id);');
		this.conference.addClose('Back');

		//
		// The panel for editing an conference
		//
		this.edit = new M.panel('Conference',
			'ciniki_conferences_main', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.conferences.conference.edit');
		this.edit.data = null;
		this.edit.conference_id = 0;
        this.edit.sections = { 
            'general':{'label':'Service', 'aside':'yes', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'status':{'label':'Status', 'type':'toggle', 'toggles':this.statuses},
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
                'save':{'label':'Save', 'fn':'M.ciniki_conferences_main.conferenceSave();'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_main.conferenceDelete();'},
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
		this.edit.addButton('save', 'Save', 'M.ciniki_conferences_main.conferenceSave();');
		this.edit.addClose('Cancel');
	}

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

        this.menuShow(cb);
	}

	this.menuShow = function(cb) {
		this.menu.data = {};
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

	this.conferenceShow = function(cb, cid, tab, subtab) {
		if( cid != null ) { this.conference.conference_id = cid; }
        if( tab != null ) { this.conference.sections._tabs.selected = tab; }
        if( subtab != null ) {
            if( this.conference.sections._tabs.selected == 'presentations' ) {
                this.conference.sections._presentationtabs.selected = subtab;
            }
        }
        var args = {'business_id':M.curBusinessID, 'conference_id':this.conference.conference_id};
        if( this.conference.sections._tabs.selected == 'presentations' ) {
            args['presentations'] = 'yes';
            switch (this.conference.sections._presentationtabs.selected) {
                case 'submitted': args['presentation_status'] = 10; break;
                case 'accepted': args['presentation_status'] = 30; break;
                case 'rejected': args['presentation_status'] = 50; break;
                case 'papers': args['presentation_type'] = 10; break;
                case 'panels': args['presentation_type'] = 20; break;
            }
        } else if( this.conference.sections._tabs.selected == 'cfplogs' ) {
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
            p.refresh();
            p.show(cb);
        });
	};

	this.conferenceEdit = function(cb, sid) {
		this.edit.reset();
		if( sid != null ) { this.edit.conference_id = sid; }
		this.edit.sections._buttons.buttons.delete.visible = (this.edit.conference_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.conferenceGet', {'business_id':M.curBusinessID, 'conference_id':this.edit.conference_id}, function(rsp) {
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

	this.conferenceSave = function() {
		if( this.edit.conference_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.conferences.conferenceUpdate', {'business_id':M.curBusinessID, 'conference_id':M.ciniki_conferences_main.edit.conference_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_conferences_main.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.conferenceAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                if( rsp.id > 0 ) {
                    var cb = M.ciniki_conferences_main.edit.cb;
                    M.ciniki_conferences_main.edit.close();
                    M.ciniki_conferences_main.conferenceShow(cb,rsp.id);
                } else {
                    M.ciniki_conferences_main.edit.close();
                }
            });
		}
	};

	this.conferenceDelete = function() {
		if( confirm("Are you sure you want to remove '" + this.edit.data.name + "'?") ) {
			M.api.getJSONCb('ciniki.conferences.conferenceDelete', 
				{'business_id':M.curBusinessID, 'conference_id':M.ciniki_conferences_main.edit.conference_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_conferences_main.edit.close();
				});
		}
	};

    this.cfplogEdit = function(cb, cid, lid) {
        M.startApp('ciniki.conferences.cfplogs',null,cb,'mc',{'conference_id':cid, 'cfplog_id':lid});
    }
};
