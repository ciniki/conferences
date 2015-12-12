//
// This app will handle the listing, additions and deletions of conferences.  These are associated business.
//
function ciniki_conferences_cfplogs() {
	//
	// Panels
	//
	this.init = function() {
        //
        // The edit panel
        //
		this.edit = new M.panel('Call For Proposal',
			'ciniki_conferences_cfplogs', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.conferences.cfplogs.edit');
		this.edit.data = null;
		this.edit.cfplog_id = 0;
        this.edit.sections = { 
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
                'save':{'label':'Save', 'fn':'M.ciniki_conferences_cfplogs.cfplogSave();'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_conferences_cfplogs.cfplogDelete();'},
                }},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.conferences.CFPLogHistory', 'args':{'business_id':M.curBusinessID, 
				'cfplog_id':this.cfplog_id, 'field':i}};
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_conferences_cfplogs.cfplogSave();');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_conferences_cfplogs', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

        if( args.cfplog_id != null && args.cfplog_id != '' ) {
            this.cfplogEdit(cb, 0, args.cfplog_id);
        } else if( args.conference_id != null && args.conference_id != '' ) {
            this.cfplogEdit(cb, args.conference_id, 0);
        } else {
            return false;
        }
	}

    this.cfplogEdit = function(cb, cid, lid) {
        if( cid != null ) { this.edit.conference_id = cid; }
        if( lid != null ) { this.edit.cfplog_id = lid; }
		this.edit.reset();
		this.edit.sections._buttons.buttons.delete.visible = (this.edit.cfplog_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.CFPLogGet', {'business_id':M.curBusinessID, 
            'cfplog_id':this.edit.cfplog_id,
            'categories':'yes',
            }, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_cfplogs.edit;
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

    this.cfplogSave = function() {
		if( this.edit.cfplog_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.conferences.CFPLogUpdate', {'business_id':M.curBusinessID, 
                    'cfplog_id':this.edit.cfplog_id,
                    }, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
                        M.ciniki_conferences_cfplogs.edit.close();
                        });
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.conferences.CFPLogAdd', {'business_id':M.curBusinessID, 
                'conference_id':this.edit.conference_id, 
                }, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_conferences_cfplogs.edit.close();
                });
		}
    };

	this.cfplogDelete = function() {
		if( confirm("Are you sure you want to remove this CFP?") ) {
			M.api.getJSONCb('ciniki.conferences.CFPLogDelete', 
				{'business_id':M.curBusinessID, 'cfplog_id':M.ciniki_conferences_cfplogs.edit.cfplog_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_conferences_cfplogs.edit.close();
				});
		}
	};

};
