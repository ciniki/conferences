//
function ciniki_conferences_settings() {
    this.toggleOptions = {'no':'Hide', 'yes':'Display'};
    this.yesNoOptions = {'no':'No', 'yes':'Yes'};
    this.viewEditOptions = {'view':'View', 'edit':'Edit'};
    this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};
    this.weightUnits = {
        '10':'lb',
        '20':'kg',
        };

    this.init = function() {
        //
        // The menu panel
        //
        this.menu = new M.panel('Settings',
            'ciniki_conferences_settings', 'menu',
            'mc', 'narrow', 'sectioned', 'ciniki.conferences.settings.menu');
        this.menu.sections = {
            '_':{'label':'', 'list':{
                'reviewer-emails':{'label':'Reviewer Email', 'fn':'M.ciniki_conferences_settings.settingsEdit("reviewers");'},
        //        'accepted-email':{'label':'Accepted Email', 'fn':'M.ciniki_conferences_settings.settingsEdit("submissions");'},
                }},
        };
        this.menu.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.conferences.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
        }
        this.menu.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.menu.addButton('save', 'Save', 'M.ciniki_conferences_settings.settingsSave();');
        this.menu.addClose('Back');

        //
        // The reviewer email which contains the submissions to review
        //
        this.reviewers = new M.panel('Reviewer Email',
            'ciniki_conferences_settings', 'reviewers',
            'mc', 'medium', 'sectioned', 'ciniki.conferences.settings.reviewers');
        this.reviewers.sections = {
            '_subject':{'label':'Reviewer Message', 'fields':{
                'reviewers-message-reviews-subject':{'label':'Subject', 'type':'text'},
                }},
            '_content':{'label':'', 'fields':{
                'reviewers-message-reviews-content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_conferences_settings.settingsSave("reviewers");'},
                }},
        };
        this.reviewers.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.conferences.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
        }
        this.reviewers.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.reviewers.addButton('save', 'Save', 'M.ciniki_conferences_settings.settingsSave("reviewers");');
        this.reviewers.addClose('Back');

        //
        // The accepted email for accepting a submission to the conference
        //
        this.submissions = new M.panel('Submissions Emails',
            'ciniki_conferences_settings', 'submissions',
            'mc', 'medium', 'sectioned', 'ciniki.conferences.settings.submissions');
        this.submissions.sections = {
            '_subject':{'label':'Accepted Message', 'fields':{
                'submissions-message-accepted-subject':{'label':'Subject', 'type':'text'},
                }},
            '_content':{'label':'', 'fields':{
                'submissions-message-accepted-content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_conferences_settings.settingsSave("submissions");'},
                }},
        };
        this.submissions.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.conferences.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
        }
        this.submissions.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.submissions.addButton('save', 'Save', 'M.ciniki_conferences_settings.settingsSave("submissions");');
        this.submissions.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_conferences_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.showMenu(cb);
    }

    //
    // show the paypal settings
    //
    this.showMenu = function(cb) {
        this.menu.refresh();
        this.menu.show(cb);
    };

    //
    // show the paypal settings
    //
    this.settingsEdit = function(panel) {
        M.api.getJSONCb('ciniki.conferences.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_conferences_settings[panel];
            p.data = rsp.settings;
            p.refresh();
            p.show('M.ciniki_conferences_settings.showMenu();');
        });
    };

    //
    // Save the Paypal settings
    //
    this.settingsSave = function(panel) {
        var c = this[panel].serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.conferences.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_conferences_settings[panel].close();
            });
        } else {
            this[panel].close();
        }
    };
}
