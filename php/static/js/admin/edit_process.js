(function(){

    var self = this;

    if (typeof Admin == 'undefined') {
        Admin = self.Admin = {};
    }

    Admin.EditProcess = Uif.View.extend({

        template_activity: _.template($('#item-activity-template').html()),
        tabs: {
            'tab_process': {
                id: 'process',
                first: true,
                next: 'tab_activity'
             },    
            'tab_activity': {
                id: 'activity',
                back: 'tab_process',
                next: 'tab_resources'
             },    
            'tab_resources': {
                id: 'resources',
                back: 'tab_activity',
                next: 'tab_users'
             },    
             'tab_users': {
                id: 'users',
                back: 'tab_resources',
                //next: 'tab_templates'
                last: true
             }
             /*
             'tab_templates': {
                id: 'templates',
                back: 'tab_users',
                last: true 
             }    
             */
 
        },

        events: {
            'click #button_add_activity': 'add_activity',
            'click #button_next': 'change_tab',
            'click #button_back': 'change_tab',
            'click #button_demo': 'open_demo',
            'click #button_login_process': 'login_process',


            'change #process_theme': 'set_property',
            'change #process_code': 'set_property',
            'change #process_summary': 'set_property',
            'change #process_type': 'set_property',
            'changeDate #process_date_start': 'set_property',
            'changeDate #process_date_finish': 'set_property'

        },

        initialize: function(options) {
            _.bindAll(this, 'render', 'add_activity', 'view_activities', 'setup_bindings', 'save_process', 'validate_tab_process', 'set_property', 'analyse_insert_process', 'analyse_save_activity', 'tab_swap', 'change_tab', 'validate_tab_activities', 'validate_tab_resources', 'validate_tab_users', 'validate_tab_templates', 'get_information_process', 'reset', 'analyse_information_process', 'swap_edition_state', 'analyse_insert_activity', 'setup_components', 'reorder_activities', 'refresh_activities', 'analyse_update_activity', 'remove_activity', 'delete_activity', 'analyse_baja_activity', 'modify_activity', 'instantiate_editor_activity', 'instantiate_editor_resources', 'instantiate_editor_participants', 'instantiate_editor_templates', 'open_demo', 'login_process', 'get_activities', 'process_activities', 'delete_views', 'process_bases', 'set_bases');
            this.model = new Uif.Model;
            this.bases = new Uif.Model;
            this.activities = new Uif.Collection;
            this.activities_service = new Uif.Model;
            this.activities_views = new Array();
            this.activities_sorted = [];
            this.types_process = new Uif.Collection;
            this.themes = new Uif.Collection;
            this.setup_services();
            this.setup_bindings();
            this.setup_components();
            this.render();
        },

        render: function() {
            this.current_tab = this.tabs['tab_process'];
            $('#button_back').css('visibility', this.current_tab.first ? 'hidden' : 'visible');
            $('#button_next').css('visibility', this.current_tab.last? 'hidden' : 'visible');
        },

        reset: function(options) {
            if(typeof options != 'undefined') {
                if(options.code) {
                    this.get_information_process(options.code);
                }
            } else {
                this.model.set({
                    process_persisted: false
                });
            }
        },

        setup_components: function() {
            var self = this;

            this.types_process.reset([
                {code: 'contest', description: 'Contest'},
                {code: 'evaluation', description: 'Evaluation'},
                {code: 'poll', description: 'Poll'},
                {code: 'presentation', description: 'Presentation'}
            ]);

            this.themes_service.fetch({
                url: '/admin/resources/list_themes',
                data: {},
                success: function() {
                    self.themes.reset( self.themes_service.context.get('themes'));
                    console.dir(self.themes.toJSON());
                    $('#process_theme').datacombo(self.themes, {
                        code: 'theme',
                        description: 'description',
                        required: true
                    });
                }

            });

            /*
            $('#process_type').datacombo(this.types_process, {
                code: 'code',
                description: 'description'
            });
            */

            $('#bases_text').redactor({
                lang: 'en',
                plugins: ['fullscreen', 'clips'],
                imageUpload: '/admin/resources/upload_image_redactor/'+this.code_process
            });

            $(".collapse").collapse({
                toggle: false   
            });
            $('.datepicker').datepicker();
            $('#dialog_edit_activity').modal({
                show: false
            });

            $('a[data-toggle="tab"]').on('shown', this.tab_swap);
            $('#questionnaire').sortable({ 
                opacity: 0.6, 
                cursor: 'move', 
                items: '.accordion-group',
                update: function(e, ui) {
                    self.reorder_activities();
                    //self.activities_sorted = $(this).sortable('toArray');
                }
            });

            $('#questionnaire').disableSelection();
        },

        login_process: function() {
            var self = this;
            bootbox.confirm("This action will close the administrator and open the process. Do you want to continue?", function(confirmed) {
                if(confirmed) {
                    window.open('/admin/login/logout/'+self.model.get('code'));
                    window.location = '/admin/login';
                }
            });

        },

        open_demo: function() {
            window.open('/p/'+this.model.get('code'));
        },

        reorder_activities: function() {
            var new_order = $('#questionnaire').sortable('toArray');
            this.analyse_activities(_.reject(new_order, function(eleme){ return eleme === ''; }));
        },

        setup_services: function() {
            this.service_save_process = new Uif.Model;
            this.process = new Uif.Model;
            this.activity_service = new Uif.Model;
            this.themes_service = new Uif.Model;
        },

        setup_bindings: function() {
            //this.activities.bind('all', this.view_activities);
            this.activities.bind('add', this.view_activities);
            this.activities.bind('remove', this.view_activities);
            this.activities.bind('reset', this.view_activities);
            this.model.bind('change:process_persisted', this.swap_edition_state);
            this.model.bind('change:code', function(model, attribute) {if(attribute){
                $('#url_process_code').html(attribute);
            } });
            this.model.bind('change:bases', this.set_bases);
            this.binds_for_model(this.model_binding, this.model);
        },

        model_binding: [
            { ui: 'process_theme', property: 'theme'},
            { ui: 'process_code', property: 'code'},
            { ui: 'process_summary', property: 'summary'},
            { ui: 'process_type', property: 'type'},
            { ui: 'process_date_start', property: 'date_start'},
            { ui: 'process_date_finish', property: 'date_finish'}
        ],

        swap_edition_state: function(model, attribute) {
            console.log("process_persisted: "+attribute);
            if(attribute) {
                $('#process_code').attr('disabled', attribute);
                $('#button_demo').css('visibility', 'visible');
                $('#button_login_process').css('visibility', 'visible');
                $('#url_label').css('visibility', 'visible');
            } else {
                $('#process_code').removeAttr('disabled');
                $('#button_demo').css('visibility', 'hidden');
                $('#button_login_process').css('visibility', 'hidden');
                $('#url_label').css('visibility', 'hidden');
            }
        },

        change_tab: function(e) {
            console.log(e.target.id);
            if(e.target.id == 'button_next') {
                if(!this.current_tab.last) {
                    if(this.validate_tab(this.current_tab.id)) {
                        $('#'+this.current_tab.next).tab('show');
                    }
                }
            } else {
                if(!this.current_tab.first) {
                    $('#'+this.current_tab.back).tab('show');
                }
            }
        },

        tab_swap: function(e) {
            var current = e.target.id,
                last = e.relatedTarget.id;
                
            switch(current) {
                case 'tab_activity':
                    this.activities.reset(this.model.get('activities'));
                    break;
                case 'tab_resources':
                    this.instantiate_editor_resources();
                    break;
                case 'tab_users':
                    this.instantiate_editor_participants();
                    break;
                case 'tab_templates':
                    this.instantiate_editor_templates();
                    break;

                default:
                    break;
            }
            switch(last) {
                case 'tab_process':
                    this.save_process();
                    break;
                case 'tab_activity':
                    this.model.set({
                        activities: this.activities.toJSON()
                    });
                    this.analyse_activities();
                    break;
                case 'tab_resources':
                    break;
                case 'tab_users':

                    break;
                case 'tab_templates':

                    break;
                default:
                    break;
            }

            this.current_tab = this.tabs[e.target.id];
            
            $('#button_back').css('visibility', this.current_tab.first ? 'hidden' : 'visible');
            $('#button_next').css('visibility', this.current_tab.last? 'hidden' : 'visible');
        },

        instantiate_editor_resources: function() {
            if(typeof(Admin.editor_resources) == 'undefined') {
                Admin.editor_resources = new Admin.EditResources({
                    el: $('#admin_resources')
                });
            }
            Admin.editor_resources.reset({
                code_process: this.model.get('code')
            });
        },

        instantiate_editor_participants: function() {
            if(typeof(Admin.editor_participants) == 'undefined') {
                Admin.editor_participants = new Admin.EditProcessParticipants({
                    el: $('#edit_process_participants')
                });
            }
            Admin.editor_participants.reset({
                code_process: this.model.get('code'),
                batch_id: this.model.get('batch_id')
            });
        },

        instantiate_editor_templates: function() {
            if(typeof(Admin.editor_templates) == 'undefined') {
                Admin.editor_templates = new Admin.EditTemplates({
                    el: $('#edit_process_templates')
                });
            }
            Admin.editor_templates.reset({
                code_process: this.model.get('code'),
                activities: this.activities.toJSON()
            });
        },

        add_activity: function() {
            var index = this.activities.length;
            this.instantiate_editor_activity();

            $('#dialog_edit_activity').modal({
                show: true    
            });
            Admin.editor_activity.reset({
                code_process: this.model.get('code'),
                code_activity: this.model.get('code')+'_'+index
            });
        },

        instantiate_editor_activity:  function() {
            if(typeof(Admin.editor_activity) == 'undefined') {
                Admin.editor_activity = new Admin.EditStep({
                    el: $('#dialog_edit_activity'),
                    code_process: this.model.get('code')
                });
                Admin.editor_activity.bind('save_activity', this.analyse_save_activity);
                Admin.editor_activity.bind('cancelar_activity', this.cancelar_activity);
            }
        },

        modify_activity: function(activity_code) {
            var index = this.activities.length;
            this.instantiate_editor_activity();

            var activity = _.find(this.activities.models, function(model) {
                return model.get('code') == activity_code;
            });
            if(activity) {
                $('#dialog_edit_activity').modal({
                    show: true    
                });
                Admin.editor_activity.reset({
                    code_process: this.model.get('code'),
                    activity: activity
                });

            }
        },

        remove_activity: function(activity_code) {
            console.log('Eliminar activity: '+activity_code+' del process: '+this.model.get('code'));
            var self = this;
            bootbox.confirm("Desea remove la activity seleccionada?", function(confirmed) {
                if(confirmed) {
                    self.delete_activity(activity_code);
                }
            });
        },


        get_activities: function() {
            var self = this;
            this.activities_service.fetch({
                url: '/admin/process/get_activities',
	        	data: {
                    process_code: this.model.get('code')
                },
                success: self.process_activities
            });
        },

        process_activities: function(service) {
            var self = this;
            self.activities.reset();
            _.each(service.context.attributes, function(model) {
                if(model) {
                    self.activities.add(model);    
                }
            });
        },

        delete_activity: function(activity_code) {
            var self = this;
            this.activity_service.fetch({
                url: '/admin/process/remove_activity',
	        	data: {
                    process_code: this.model.get('code'),
                    activity_code: activity_code   
                },
                success: function() {
                    self.analyse_baja_activity(activity_code);
                }
            });


        },

        close_dialog_activity: function() {
            $('#dialog_edit_activity').modal('hide');
        },

        analyse_save_activity: function(activity) {
            var index = this.activities.length+1,
                activity_map = {},
                self = this,
                order = activity.get('modify') ? activity.get('order') : index;
            activity_map = {
                code_process: this.model.get('code'),
                code: activity.get('code'),
                order: order,
                description: activity.get('description'),
                pregunta: activity.get('pregunta'),
                super_type: activity.get('super_type'),
                type: activity.get('type'),
                text: activity.get('text'),
                datapath: encodeURI(activity.get('datapath')),
                answers: JSON.stringify(activity.get('answers'))
            };
            if(activity.get('modify')) {
                this.activity_service.fetch({
                    url: '/admin/process/update_activity',
                    data: activity_map,
                    success: self.analyse_update_activity
                });
            } else {
                this.activity_service.fetch({
                    url: '/admin/process/add_activity',
                    data: activity_map,
                    success: this.analyse_insert_activity
                });
            }

            this.close_dialog_activity();
         },
            
         analyse_baja_activity: function(activity_code) {
            var activity = _.find(this.activities.models, function(model) {
                return model.get('code') == activity_code;
            });
            if(activity) {
                this.activities.remove(activity);
            }
            this.reorder_activities();
         },

         analyse_insert_activity: function(model, response) {
            var activity_nueva = new Uif.Model;
            activity_nueva.set(this.activity_service.context.toJSON());
            if(activity_nueva.get('type') != 'BASES') {
                this.activities.add(activity_nueva.toJSON());
            }
        },

        analyse_update_activity: function(model, response) {
            console.log(this.activity_service.context.toJSON());
            this.get_activities();
        },


        delete_views: function() {
            _.each(this.activities_views, function(view) {
                if(view) {
                    view.remove();
                }
            });
        },

        view_activities: function() {
            console.log('view activities');
            var self = this;
            this.delete_views();
            $('#questionnaire').html('');
            var sorted_activities = _.sortBy(this.activities.models, function(model) {
                return model.get('order');
            });
            _.each(sorted_activities , function(activity) {

                var activity_view = new Admin.ActividadItemView({
                    model: activity
                });

                activity_view.bind('delete_activity', self.remove_activity);
                activity_view.bind('edit_activity', self.modify_activity);
                
                self.activities_views.push(activity_view);

                $('#questionnaire').append(activity_view.render().el);
                activity_view.render_answers();

                activity_view.delegateEvents();
            });
        },
        
        refresh_activities: function() {
            console.log('refresh activities');
            var self = this;
            $('#questionnaire').html('');

            this.activities_views = _.sortBy(this.activities_views, function(view) {
               return view.model.get('order'); 
            });

            _.each(this.activities_views, function(view) {
                if(view) {
                    $('#questionnaire').append(view.render().el);
                    view.render_answers();
                    view.delegateEvents();
                }
            });

        },

        get_information_process: function(code_process) {
            this.process.fetch({
                url: '/admin/process/get_process',
	        	data: {
                    code: code_process
                },
                success: this.analyse_information_process
            });
        },

        analyse_information_process: function(model, response) {
            this.model.set(_.extend({
                process_persisted: true
            }, response.process));
            //this.activities.reset(this.model.get('activities'));
        },
        
        save_process: function() {
            console.log('save process');
            if(this.validate_tab_process()) {
                $('#button_next').button('loading');
                if(!this.model.get('process_persisted')) {
                    this.service_save_process.fetch({
                        url: '/admin/process/insert_process',
                        data: {
                            code: this.model.get('code'),   
                            summary: this.model.get('summary'),
                            theme: this.model.get('theme'),   
                            type: this.model.get('type'),   
                            date_start: this.model.get('date_start'),   
                            date_finish: this.model.get('date_finish'),   
                            state: 'INACTIVE',
                            bases: this.bases.toJSON()
                        },
                        success: this.analyse_insert_process
                    });
                } else {
                    this.service_save_process.fetch({
                        url: '/admin/process/update_process',
                        data: {
                            code: this.model.get('code'),   
                            summary: this.model.get('summary'),   
                            type: this.model.get('type'),   
                            date_start: this.model.get('date_start'),   
                            date_finish: this.model.get('date_finish'),   
                            state: 'INACTIVE',
                            bases: this.bases.toJSON()
                        },
                        success: this.analyse_insert_process
                    });

                }
            }
        },

        analyse_activities: function(new_order) {
            var activities = new_order || this.activities_sorted,
                activities_originales = this.activities,
                activities_new_order = new Array(),
                index = 1;

            if(activities && activities.length > 0) {

                for(index = 1; index <= activities.length; index++) {
                    var activity = _.find(activities_originales.models, function(model) {
                        return ('q_'+model.get('code')) == activities[index];
                    });
                    if(activity) {
                        var order = activity.get('order');
                        if(order != index) {
                           activity.set({
                                order: index,
                                previous_order: order,
                                modified: true  
                           }); 
                           console.log('update activity '+activity.get('code')+' order: '+activity.get('order'));
                           activities_new_order.push({
                               code: activity.get('code'),
                                order: activity.get('order')
                           });
                        }
                    }
                }
            }

            if(activities_new_order.length) {
                this.activity_service.fetch({
                    url: '/admin/process/reorder_activities',
                    data: {
                        code_process: this.model.get('code'),
                        activities_order: JSON.stringify(activities_new_order)
                    },
                    success: this.analyse_update_activity
                });
 
            }
            /*
            _.each(activities_originales.models, function(model) {
                if(model.get('modified')) {
                    this.activity_service.fetch({
                        url: '/admin/process/update_activity',
                        data: activity_map,
                        success: this.analyse_update_activity
                    });
                }
            });
            */
        },

        analyse_insert_process: function(response) {
            this.model.set({
              process_persisted: true  
            });
            $('#button_next').button('reset');
        },

        validate_tab: function(tab_id) {
            switch(tab_id) {
                case 'process':
                    return this.validate_tab_process();
                case 'activity':
                    return this.validate_tab_activities();
                case 'resources':
                    return this.validate_tab_resources();
                case 'users':
                    return this.validate_tab_users();
                case 'templates':
                    return this.validate_tab_templates();
                default:
                    return true;
            }
        },

        validate_tab_process: function() {
            if(!this.model.get('code')) {
                Message.show_error('Insert code');
                return false;
            }
            if(!this.model.get('summary')) {
                Message.show_error('Insert summary');
                return false;
            }

            if(!this.model.get('type')) {
                this.model.set({
                    type: $('#process_type').attr('value')
                });
            }
            if(!this.model.get('theme')) {
                this.model.set({
                    theme: $('#process_theme').attr('value')
                });
            }
            this.process_bases();
            return true;
        },

        process_bases: function() {
            var description = $('#bases_summary').attr('value'),
                text = JSON.stringify($('#bases_text').getCode());
            this.bases.set({
                description: description,
                text: text
            });
        },

        set_bases: function(model, attribute) {
            if(attribute) {
                $('#bases_summary').attr('value', attribute.description);
                $('#bases_text').setCode(attribute.text);
            }
        },

        validate_tab_activities: function() {
            return true;
        },

        validate_tab_resources: function() {
            return true;
        },

        validate_tab_users: function() {
            return true;
        },

        validate_tab_templates: function() {
            return true;
        },


        set_property: function(e) {
            var map = {};
            var property_id = this.input_property_map[e.target.id];
            var value = e.target.value;
            if(property_id == 'code') {
                value = value.toUpperCase();
            }
            map[property_id] =  value;
            this.model.set(map);
        },

        input_property_map: {
            process_theme: 'theme',
            process_code: 'code',
            process_summary: 'summary',
            process_type: 'type',
            process_date_start: 'date_start',
            process_date_finish: 'date_finish'
        }

    });


    Admin.ActividadItemView = Uif.View.extend({
        tagName: 'div',
        template_activity: _.template($('#item-activity-template').html()),

        events: {
            'click .btn-trash': 'remove_activity',
            'click .btn-edit': 'modify_activity'
        },
 
        initialize: function() {
            _.bindAll(this, 'render', 'render_answers', 'setup_answers', 're_arrange_answers', 'remove_activity', 'modify_activity');
        
            this.answers = new Array();
            this.setup_answers();
        },

        setup_answers: function() {
            var model = this.model,
                self = this;

            if(model.has('answers')) {
                var answers_models = new Uif.Collection(model.get('answers')); 

                _.each(answers_models.models, function(answer) {
                    answer.set({
                        last: answer.get('order') + 1 == self.answers.length
                    });
                    var answer_view = new Admin.RespuestaItemView({
                        model: answer
                    });
                    answer_view.bind('up_sort', self.re_arrange_answers);
                    answer_view.bind('down_sort', self.re_arrange_answers);
                    answer_view.bind('change_model', self.render_answers);
                    self.answers.push(answer_view);
                });
            }
        },

        re_arrange_answers: function(answer, up) {
            var current_index = answer.get('order') ,
                next_index = up ? answer.get('order') -1 : answer.get('order') + 1;


            var answer_view = _.find(this.answers, function(view) {
                return (view.model.get('order') == next_index);
            });
            if(answer_view) {
                answer_view.model.set({
                    last: (current_index + 1 == this.answers.length),
                    order: current_index 
                });
                answer.set({
                    last: (next_index + 1 == this.answers.length),
                    order: next_index
                });
            }
            this.render_answers();
        },

        render: function() {
            this.undelegateEvents();
        //    console.log('Orden: '+this.model.get('order'));
        //    return this.template_activity(_.extend({has_answers: this.answers.length}, this.model.toJSON()));
            this.$el.html(this.template_activity(_.extend({has_answers: this.answers.length}, this.model.toJSON())));
            return this;
        },

        render_answers: function() {
            var model = this.model,
                self = this;

            $('#answers_'+model.get('code')).html('');
            var answers_models = _.sortBy(this.answers, function(view) {
               return view.model.get('order'); 
            });
            _.each(answers_models, function(answer) {
                $('#answers_'+model.get('code')).append(answer.render().el);
            });
        },

        remove_activity: function() {
            console.log('Eliminar activity: '+this.model.get('code'));
            this.trigger('delete_activity', this.model.get('code'));
        },

        modify_activity: function() {
            console.log('Modificar activity: '+this.model.get('code'));
            this.trigger('edit_activity', this.model.get('code'));
        }

    });

    Admin.RespuestaItemView = Uif.View.extend({
        tagName: 'div',
        template_answer: _.template($('#item-answer-template').html()),
        template_answer_edition: _.template($('#item-edit-answer-template').html()),


        events: {
            'click .icon-arrow-up': 'do_up',
            'click .icon-arrow-down': 'do_down',
            'click .icon-remove-sign': 'do_remove',
            'click .icon-pencil': 'do_edit',
            'click .icon-repeat': 'do_undo'
        },

        initialize: function() {
            _.bindAll(this, 'render', 'do_up', 'do_down', 'do_remove', 'do_edit', 'do_undo', 'fire_change');
            this.edit_mode = this.options.edit_mode ? true : false;
            this.setup_bindings();
        },

        setup_bindings: function() {
            this.model.on('change:removed', this.fire_change);
            this.model.on('change', this.render);
        },

        render: function() {
            if(this.edit_mode) {
                this.$el.html(this.template_answer_edition(this.model.toJSON()));
            } else {
                this.$el.html(this.template_answer(this.model.toJSON()));
            }
            return this;
        },

        do_up: function() {
            this.trigger('up_sort', this.model, true);
        },

        do_down: function() {
            this.trigger('down_sort', this.model, false);
        },

        do_remove: function() {
            this.model.set({
                removed: true
            });
        },

        do_undo: function() {
            this.model.unset('removed');
        },

        do_edit: function() {
            this.trigger('edit', this.model);
        },

        fire_change: function() {
            this.trigger('change_model', this.model);
        }
    });

}).call(this);

