(function(){

    var self = this;

    if (typeof Admin == 'undefined') {
        Admin = self.Admin = {};
    }

    Admin.EditResources = Uif.View.extend({
        el: $('#admin_resources'),

        events: {

        },

        initialize: function() {
            _.bindAll(this, 'render', 'reset');
            this.model = new Uif.Model;
        },

        reset: function(options) {
            this.model.clear();
            this.model.set({
                code_process: options.code_process
            });
            this.render();
        },

        render: function() {
            var elf = $('#elfinder').elfinder({
                lang: 'en',
                url : '/admin/resources/elfinder_init/'+this.model.get('code_process'),

                _uiOptions : {
                    toolbar : [
                        ['back', 'forward'],
                        ['reload'],
                        ['home', 'up'],
                        ['mkdir', 'mkfile', 'upload'],
                        ['open', 'download', 'getfile'],
                        ['info'],
                        ['quicklook'],
                        ['copy', 'cut', 'paste'],
                        ['rm'],
                        ['duplicate', 'rename', 'edit', 'resize'],
                        ['extract', 'archive'],
                        ['search'],
                        ['view']
                    ],

                    tree : {
                        openRootOnLoad : true,
                        syncTree : true
                    },

                    sync: 0,

                    navbar : {
                        minWidth : 150,
                        maxWidth : 500
                    },

                    cwd : {
                        oldSchool : false
                    }
                }
            }).elfinder('instance');    
        }

    });

}).call(this);

