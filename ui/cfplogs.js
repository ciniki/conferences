    //
    // The Call For Proposal edit panel
    //
    this.cfplog = new M.panel('Call For Proposal',
        'ciniki_conferences_main', 'edit',
        'mc', 'medium', 'sectioned', 'ciniki.conferences.main.edit');
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
        return {'method':'ciniki.conferences.CFPLogHistory', 'args':{'tnid':M.curTenantID, 'cfplog_id':this.cfplog_id, 'field':i}};
    }
    this.cfplog.edit = function(cb, cid, lid) {
        if( cid != null ) { this.conference_id = cid; }
        if( lid != null ) { this.cfplog_id = lid; }
        this.reset();
        this.sections._buttons.buttons.delete.visible = (this.cfplog_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.conferences.CFPLogGet', {'tnid':M.curTenantID, 
            'cfplog_id':this.cfplog_id,
            'categories':'yes',
            }, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_conferences_main.edit;
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
                M.api.postJSONCb('ciniki.conferences.CFPLogUpdate', {'tnid':M.curTenantID, 'cfplog_id':this.cfplog_id, }, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.conferences.CFPLogAdd', {'tnid':M.curTenantID, 'conference_id':this.conference_id, }, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_conferences_main.edit.close();
                });
        }
    };
    this.cfplog.remove = function() {
        M.confirm("Are you sure you want to remove this CFP?",null,function() {
            M.api.getJSONCb('ciniki.conferences.CFPLogDelete', {'tnid':M.curTenantID, 'cfplog_id':M..cfplog.cfplog_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_conferences_main.edit.close();
            });
        });
    };
    this.cfplog.addButton('save', 'Save', 'M.ciniki_conferences_main.cfplog.save();');
    this.cfplog.addClose('Cancel');
