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
				'display_title':{'label':'Title'},
				'display_name':{'label':'Presenter'},
				'status_text':{'label':'Status'},
				'field':{'label':'Field'},
				'presentation_type_text':{'label':'Type'},
				'submission_date':{'label':'Submitted On'},
                }},
			'full_bio':{'label':'Bio', 'type':'html'},
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
                return 'M.ciniki_conferences_presentations.reviewEdit(\'M.ciniki_conferences_presentations.presentationShow();\',\'' + d.id + '\');';
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

		//
		// The panel for editing an conference
		//
		this.reviewer = new M.panel('Reviewer',
			'ciniki_conferences_presentations', 'reviewer',
			'mc', 'medium', 'sectioned', 'ciniki.conferences.presentations.reviewer');
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
                'downloadpdf':{'label':'Download PDF', 'fn':'M.ciniki_conferences_presentations.reviewerPDF();'},
                }},
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
                return 'M.ciniki_conferences_presentations.reviewEdit(\'M.ciniki_conferences_presentations.reviewerShow();\',\'' + d.id + '\');';
            }
		};
		this.reviewer.addClose('Back');

        //
        // The panel for changing and individual review
        //
		this.review = new M.panel('Presentation',
			'ciniki_conferences_presentations', 'review',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.conferences.presentations.review');
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
                'save':{'label':'Save', 'fn':'M.ciniki_conferences_presentations.reviewSave();'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_presentations.reviewDelete();'},
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
		this.review.addButton('save', 'Save', 'M.ciniki_conferences_presentations.reviewSave();');
		this.review.addClose('Cancel');
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

        if( args.reviewer_id != null ) {
            this.reviewerShow(cb, args.reviewer_id, args.conference_id);
        } else if( args.review_id != null ) {
            this.reviewEdit(cb, args.review_id);
        } else if( args.presentation_id != null ) {
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

    //
    // Presentation functions
    //
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

    //
    // Reviewer functions
    //
	this.reviewerShow = function(cb, rid, cid) {
		if( rid != null ) { this.reviewer.reviewer_id = rid; }
		if( cid != null ) { this.reviewer.conference_id = cid; }
		M.api.getJSONCb('ciniki.conferences.presentationReviewerGet', {'business_id':M.curBusinessID, 
            'reviewer_id':this.reviewer.reviewer_id, 'conference_id':this.reviewer.conference_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_presentations.reviewer;
                p.data = rsp.reviewer;
                p.refresh();
                p.show(cb);
            });
	};

	this.reviewerPDF = function(cb) {
		M.api.openFile('ciniki.conferences.presentationReviewerPDF', {'business_id':M.curBusinessID, 
            'reviewer_id':this.reviewer.reviewer_id, 'conference_id':this.reviewer.conference_id});
	};

    //
    // Review Managements
    //
	this.reviewEdit = function(cb, rid) {
		this.review.reset();
		if( rid != null ) { this.review.review_id = rid; }
		this.review.sections._buttons.buttons.delete.visible = (this.review.review_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.presentationReviewGet', {'business_id':M.curBusinessID, 'review_id':this.review.review_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_presentations.review;
            p.data = rsp.review;
            p.refresh();
            p.show(cb);
        });
	};

	this.reviewSave = function() {
		if( this.review.review_id > 0 ) {
			var c = this.review.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.conferences.presentationReviewUpdate', {'business_id':M.curBusinessID, 'review_id':M.ciniki_conferences_presentations.review.review_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_conferences_presentations.review.close();
					});
			} else {
				this.review.close();
			}
		} else {
			var c = this.review.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.presentationReviewAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_conferences_presentations.review.close();
            });
		}
	};

	this.reviewDelete = function() {
		if( confirm("Are you sure you want to remove this review?") ) {
			M.api.getJSONCb('ciniki.conferences.presentationReviewDelete', 
				{'business_id':M.curBusinessID, 'review_id':M.ciniki_conferences_presentations.review.review_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_conferences_presentations.review.close();
				});
		}
	};
};
