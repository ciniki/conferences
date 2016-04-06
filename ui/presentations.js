//
// This app will handle the listing, additions and deletions of conferences.  These are associated business.
//
function ciniki_conferences_presentations() {
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
		this.menu = new M.panel('Presentations',
			'ciniki_conferences_presentations', 'menu',
			'mc', 'large', 'sectioned', 'ciniki.conferences.presentations.menu');
        this.menu.conference_id = 0;
        this.menu.sections = {
            'status':{'label':'', 'type':'paneltabs', 'selected':'0', 'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_conferences_presentations.menuShow(null,null,0);'},
                '10':{'label':'Submitted', 'fn':'M.ciniki_conferences_presentations.menuShow(null,null,10);'},
                '30':{'label':'Accepted', 'fn':'M.ciniki_conferences_presentations.menuShow(null,null,30);'},
                '50':{'label':'Rejected', 'fn':'M.ciniki_conferences_presentations.menuShow(null,null,50);'},
                }},
			'presentations':{'label':'Presentations', 'type':'simplegrid', 'num_cols':2,
                'cellClasses':['multiline', 'multiline'],
				'noData':'No presentations',
				'addTxt':'Add Presentation',
				'addFn':'M.ciniki_conferences_presentations.presentationEdit(\'M.ciniki_conferences_presentations.menuShow();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.title + '</span><span class="subtext">' + d.display_name + '</span>';
                case 1: return d.status_text;
            }
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_conferences_presentations.presentationShow(\'M.ciniki_conferences_presentations.menuShow();\',\'' + d.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_conferences_presentations.presentationEdit(\'M.ciniki_conferences_presentations.menuShow();\');');
		this.menu.addClose('Back');

		//
		// The presentation panel 
		//
		this.presentation = new M.panel('Presentation',
			'ciniki_conferences_presentations', 'presentation',
			'mc', 'large', 'sectioned', 'ciniki.conferences.presentations.presentation');
		this.presentation.data = {};
		this.presentation.presentation_id = 0;
		this.presentation.sections = {
			'info':{'label':'Presentation', 'list':{
				'title':{'label':'Title'},
				'display_name':{'label':'Presenter'},
				'status_text':{'label':'Status'},
				'field':{'label':'Field'},
				'presentation_type_text':{'label':'Type'},
				'submission_date':{'label':'Submitted On'},
                }},
			'description':{'label':'Description', 'type':'html'},
            'reviews':{'label':'Reviewers', 'type':'simplegrid', 'num_cols':2,
                'addTxt':'Add Reviewer',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_conferences_presentations.presentationShow();\',\'mc\',{\'next\':\'M.ciniki_conferences_presentations.presentationAddReview\',\'customer_id\':0});',
                },
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.ciniki_conferences_presentations.presentationEdit(\'M.ciniki_conferences_presentations.presentationShow();\',M.ciniki_conferences_presentations.presentation.presentation_id);'},
                }},
		};
		this.presentation.sectionData = function(s) {
            if( s == 'info' ) { return this.sections[s].list; }
            if( s == 'description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
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
                return this.data[i] + ' <button onclick=\'event.stopPropagation(); M.ciniki_conferences_presentations.presentationAccept("' + this.data['id'] + '"); return false;\'>Accept</button>'
                    + ' <button onclick=\'event.stopPropagation(); M.ciniki_conferences_presentations.presentationReject("' + this.data['id'] + '"); return false;\'>Reject</button>';
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
        this.presentation.rowFn = function(s, i, d) {
            if( s == 'reviews' ) {
                return 'M.startApp(\'ciniki.conferences.reviewers\',null,\'M.ciniki_conferences_presentations.presentationShow();\',\'mc\',{\'conference_id\':\'' + d.conference_id + '\', \'customer_id\':\'' + d.customer_id + '\'});';
            }
        };
        this.presentation.addButton('edit', 'Edit', 'M.ciniki_conferences_presentations.presentationEdit(\'M.ciniki_conferences_presentations.presentationShow();\',M.ciniki_conferences_presentations.presentation.presentation_id);');
		this.presentation.addClose('Back');

		//
		// The panel for editing an conference
		//
		this.edit = new M.panel('Presentation',
			'ciniki_conferences_presentations', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.conferences.presentations.edit');
		this.edit.data = null;
		this.edit.presentation_id = 0;
        this.edit.sections = { 
            'general':{'label':'Presentation', 'aside':'yes', 'fields':{
                'title':{'label':'Title', 'type':'text'},
                'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Submitted', '30':'Accepted', '50':'Rejected'}},
                'field':{'label':'Field', 'type':'text'},
                'presentation_type':{'label':'Type', 'type':'toggle', 'toggles':{'10':'Individual Paper', '20':'Panel'}},
                }}, 
//            'presenter':{'label':'Presenter', },
			'_description':{'label':'Description', 'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
                }},
			'_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_conferences_presentations.presentationSave();'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_presentations.presentationDelete();'},
                }},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.conferences.presentationHistory', 'args':{'business_id':M.curBusinessID, 
				'presentation_id':this.presentation_id, 'field':i}};
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_conferences_presentations.presentationSave();');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_conferences_presentations', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

        if( args.presentation_id != null ) {
            this.presentationShow(cb, args.presentation_id);
        } else {
            this.menuShow(cb, args.conference_id, args.status);
        }
	}

	this.menuShow = function(cb, cid, status) {
        if( cid != null ) { this.menu.conference_id = cid; }
        if( status != null ) { this.menu.sections.status.selected = status; }
		this.menu.data = {};
        M.api.getJSONCb('ciniki.conferences.presentationList', 
            {'business_id':M.curBusinessID, 'conference_id':this.menu.conference_id, 'status':this.menu.sections.status.selected},
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_presentations.menu;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
	};

	this.presentationShow = function(cb, sid) {
		if( sid != null ) { this.presentation.presentation_id = sid; }
		M.api.getJSONCb('ciniki.conferences.presentationGet', {'business_id':M.curBusinessID, 'presentation_id':this.presentation.presentation_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_presentations.presentation;
            p.data = rsp.presentation;
            p.refresh();
            p.show(cb);
        });
	};

    this.presentationAddReview = function(cid) {
        if( cid != null && this.presentation.data.customer_id != cid ) {
            M.api.getJSONCb('ciniki.conferences.presentationReviewAdd', {'business_id':M.curBusinessID,
                'presentation_id':this.presentation.presentation_id, 'customer_id':cid, 'conference_id':this.presentation.data.conference_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_conferences_presentations.presentationShow();
                });
        }
    };

	this.presentationEdit = function(cb, pid) {
		this.edit.reset();
		if( pid != null ) { this.edit.presentation_id = pid; }
		this.edit.sections._buttons.buttons.delete.visible = (this.edit.presentation_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.presentationGet', {'business_id':M.curBusinessID, 'presentation_id':this.edit.presentation_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_presentations.edit;
            p.data = rsp.presentation;
            p.refresh();
            p.show(cb);
        });
	};

	this.presentationSave = function() {
		if( this.edit.presentation_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.conferences.presentationUpdate', {'business_id':M.curBusinessID, 'presentation_id':M.ciniki_conferences_presentations.edit.presentation_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_conferences_presentations.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.presentationAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                if( rsp.id > 0 ) {
                    var cb = M.ciniki_conferences_presentations.edit.cb;
                    M.ciniki_conferences_presentations.edit.close();
                    M.ciniki_conferences_presentations.presentationShow(cb,rsp.id);
                } else {
                    M.ciniki_conferences_presentations.edit.close();
                }
            });
		}
	};

    this.presentationAccept = function(pid) {
        M.api.getJSONCb('ciniki.conferences.presentationUpdate', {'business_id':M.curBusinessID, 'presentation_id':pid, 'status':30}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_conferences_presentations.presentation.close();
        });
    };

    this.presentationReject = function(pid) {
        M.api.getJSONCb('ciniki.conferences.presentationUpdate', {'business_id':M.curBusinessID, 'presentation_id':pid, 'status':50}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_conferences_presentations.presentation.close();
        });
    };

	this.presentationDelete = function() {
		if( confirm("Are you sure you want to remove '" + this.edit.data.name + "'?") ) {
			M.api.getJSONCb('ciniki.conferences.presentationDelete', 
				{'business_id':M.curBusinessID, 'presentation_id':M.ciniki_conferences_presentations.edit.presentation_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_conferences_presentations.edit.close();
				});
		}
	};
};
