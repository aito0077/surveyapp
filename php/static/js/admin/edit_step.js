(function(){

    var self = this;

    if (typeof Admin == 'undefined') {
        Admin = self.Admin = {};
    }

    Admin.EditStep = Uif.View.extend({
        el: $('#dialog_editar_activity'),
        code_process: 'default',

        events: {
            'click #button_cancel_activity': 'cancel_activity',
            'click #button_save_activity': 'save_activity',
            'click #button_activity_question_add': 'add_answer',
            'click #button_activity_question_save': 'save_edition_answer',
            'click #button_activity_question_cancel': 'cancel_edition_answer',

            'change #activity_specials_video_resources': 'set_property',
            'change #activity_specials_flash_resources': 'set_property',
            'change #activity_super_type_content': 'set_property',
            'change #activity_super_type_question': 'set_property',
            'change #activity_summary': 'set_property',
            'change #activity_question': 'set_property',
            'change #activity_super_type_type': 'set_property',

            'click #activity_super_type_content': 'set_super_type',
            'click #activity_super_type_question': 'set_super_type',
            'click #activity_super_type_specials': 'set_super_type'

        },

        initialize: function() {
            _.bindAll(this, 'render', 'reset', 'setup_bindings', 'save_activity', 'cancel_activity', 'validate_activity', 'set_property', 'analyse_insert_activity', 'set_super_type', 'view_super_type', 'setup_combos', 'render_answers',  're_arrange_answers', 'validate_answer', 'save_edition_answer', 'cancel_edition_answer', 'view_super_type_question', 'view_super_type_content', 'view_super_type_specialses', 'view_type', 'view_type_question', 'reset_super_type_type_question', 'set_text', 'view_type_specials', 'set_data_activity', 'create_view');

            this.code_process = this.options.code_process;
            this.model = new Uif.Model;
            this.answers_view = new Array;
            this.answers = new Uif.Collection;
            this.activity_x_type = new Uif.Collection;
            this.render();
        },

        render: function() {
            this.setup_components();
            this.setup_services();
            this.setup_bindings();
        },

        reset: function(options) {
            this.model.clear();
            this.model.set({modify: false});
            this.answers_view = new Array();
            if(typeof(options) != 'undefined') {
                if(options.activity) {
                    this.model.set(_.extend(
                        options.activity.toJSON(),
                        {
                            code_process: options.code_process,
                            modify: true
                        }
                    ));
                } else {
                    this.model.set({
                        code_process: options.code_process,
                        code: options.code_activity
                    });
                }
            }
            this.get_resources();
       },

        setup_components: function() {
            var self = this;
            this.setup_combos();
            
            $('#activity_content_text').redactor({
                lang: 'en',
                plugins: ['fullscreen', 'clips'],
                imageUpload: '/admin/resources/upload_image_redactor/'+this.code_process
            });
        },

        setup_combos: function() {
            this.types_resources = new Uif.Collection;
            this.resources = new Uif.Collection;
            this.types_activity = new Uif.Collection([
                {super_type: 'question', code: 'MC', description: 'Single Selection'},
                {super_type: 'question', code: 'SM', description: 'Multiple Selection'},
                {super_type: 'question', code: 'TL', description: 'Text'},
                {super_type: 'question', code: 'V', description: 'Custom Value'},
                {super_type: 'content', code: 'TP', description: 'Content'},
                {super_type: 'specials', code: 'FLASH', description: 'Flash'},
                {super_type: 'specials', code: 'VIDEO', description: 'Video'}
            ]);

            $('#activity_super_type_type').datacombo(this.activity_x_type, {
                code: 'code',
                description: 'description',
                required: true
            });

            $('#activity_specials_video_resources').datacombo(this.resources, {
                code: 'path',
                description: 'filename',
                required: true
            });

            $('#activity_specials_flash_resources').datacombo(this.resources, {
                code: 'path',
                description: 'filename',
                required: true
            });


            

        },

        setup_services: function() {
            this.resources = new Uif.Model({
                url: '/admin/resources/list_resources'
            });
        },

        setup_bindings: function() {
            this.model.on('change:super_type', this.view_super_type);
            this.model.on('change:type', this.view_type);
            this.model.on('change:modify', this.set_data_activity);
            this.answers.on('add', this.render_answers);
        },

        set_data_activity: function(model, attribute) {
            if(attribute === true) {
                $('#activity_super_type_'+model.get('super_type')).trigger('click');

                $('#activity_specials_video_resources').attr('value', model.get('datapath'));
                $('#activity_specials_flash_resources').attr('value', model.get('datapath'));

                $('#activity_super_type_content').attr('value', model.get('super_type_contentido'));
                $('#activity_super_type_question').attr('value', model.get('super_type_question'));
                $('#activity_summary').attr('value', model.get('description'));
                $('#activity_question').attr('value', model.get('question'));
                $('#activity_super_type_type').attr('value', model.get('type'));
                if(model.has('answers')) {
                    var  self = this;
                    _.each(model.get('answers'), function(answer) {
                        var answer_model = new Uif.Model(answer);
                        self.create_view(answer_model);
                        self.answers.add(answer_model);
                    });
                    this.render_answers();
                }

            } else {
                this.answers.reset();
                this.render_answers();
                $('#activity_edit_answers').html();
                this.reset_edition_answer();
                $('#activity_super_type_content').removeClass('active');
                $('#activity_super_type_specials').removeClass('active');
                $('#activity_super_type_question').removeClass('active');
                $('#activity_summary').attr('value', '').change();
                $('#activity_question').attr('value', '').change();
                $('#activity_super_type_type').attr('value', '').change();
                $('#activity_content_text').setCode('');
                this.view_super_type();
            }
        },

        get_information_activity: function() {

        },

        analyse_information_activity: function() {

        },
        
        save_activity: function() {
            if(this.validate_activity()) {
                if(_.contains(['MC', 'SM'], this.model.get('type'))) {
                    this.model.set({
                        answers: this.answers
                    });
                }
                if(this.model.get('super_type') == 'content') {
                    this.set_text();
                }
                this.trigger('save_activity', this.model);
            }
        },

        cancel_activity: function() {
            this.trigger('cancel_activity');
        },

        analyse_insert_activity: function(response) {
            //console.dir(response);
        },

        validate_activity: function() {
            return true;
        },

        set_super_type: function(e) {
            var super_type = '';
            switch(e.target.id) {
                case 'activity_super_type_content':
                    super_type = 'content';
                    break;
                case 'activity_super_type_question':
                    super_type = 'question';
                    break;
                case 'activity_super_type_specials':
                    super_type = 'specials';
                    break;
                default:
                    super_type = 'none';
                    break;
            }

            this.model.set({
                super_type: super_type
            });
        },

        view_super_type: function(model, attribute) {

            $('#body_step').css('display', attribute ? 'block' : 'none');
            if(!attribute) {
                $('#activity_super_type_content').button('reset');
                $('#activity_super_type_question').button('reset');
                $('#activity_super_type_specialses').button('reset');
                return;
            }
            this.activity_x_type.reset(_.filter(this.types_activity.models, function(model) {
                return (model.get('super_type') == attribute); 
            }));
            $('#row_type_activity').css('display', attribute ? 'inline' : 'none');

            this.view_super_type_question(attribute == 'question');
            this.view_super_type_content(attribute == 'content', model.get('text'));
            this.view_super_type_specialses(attribute == 'specials');
        },
        

        view_type: function(model, attribute) {
            var super_type = this.model.get('super_type'),
                type = attribute;            

            switch(super_type) {
                case 'content':
                    super_type = 'content';
                    break;
                case 'question':
                    this.view_type_question(type);
                    break;
                case 'specials':
                    this.view_type_specials(type);
                    break;
                default:
                    super_type = 'none';
                    break;
            }

        },

        view_type_specials: function(type) {
            var video_pattern = /\.(avi|mkv|mov)$/i;
                flash_pattern = /\.swf$/i;
            $('#activity_edit_type_specialses').css('display', 'block');
            $('#specials_login').css('display', 'none');
            $('#specials_video').css('display', 'none');
            $('#specials_flash').css('display', 'none');

            console.log(type);
            switch(type) {
                case 'FLASH':
                    this.resources.reset(_.filter(this.types_resources.models, function(model) {
                        return flash_pattern.test(model.get('filename'));
                    }));
                    $('#specials_flash').css('display', 'block');
                    break;
                case 'VIDEO':
                    this.resources.reset(_.filter(this.types_resources.models, function(model) {
                        return video_pattern.test(model.get('filename'));
                    }));
                    $('#specials_video').css('display', 'block');
                    break;
                case 'LG':
                    $('#specials_login').css('display', 'block');
                    break;
                default:
                    break;

            }
        },
 

        view_type_question: function(attribute) {
            var type_question = _.contains(['MC', 'SM'], attribute );
            $('#row_activity_edition_question').css('display', type_question ? 'block' : 'none');
            if(type_question) {
                //$('#right_checkbox').css('display', (attribute == 'MC') ? 'inline' : 'none');
                this.reset_super_type_type_question();
                this.reset_edition_answer();
            }
        },

        view_super_type_question: function(visible) {
            $('#activity_edit_type_question').css('display', visible ? 'block' : 'none');
        },

        view_super_type_content: function(visible, text) {
            $('#activity_content_text').setCode(text ? text : '');
            $('#activity_edit_type_content').css('display', visible ? 'block' : 'none');
        },

        view_super_type_specialses: function(visible) {
            $('#activity_edit_type_specialses').css('display', visible ? 'block' : 'none');
        },

        edit_answer: function(answer) {
            $('#activity_answer_text').attr('value', answer.get('text'));
            $('#activity_answer_value').attr('value', answer.get('value'));
            if(answer.get('right')) {
                $('#activity_answer_right').attr('checked', 'checked');
            } 
            $('#button_activity_question_add').css('display', 'none');
            $('#button_activity_question_save').css('display', 'inline');
            $('#button_activity_question_cancel').css('display', 'inline');

        },

        save_edition_answer: function() {
            this.reset_edition_answer();
        },

        cancel_edition_answer: function() {
            this.reset_edition_answer();
        },

        add_answer: function() {
            var index = this.answers.length,
                right = ($('#activity_answer_right').is(':checked')), 
                answer = new Uif.Model({
                    code: this.model.get('code')+'_r_'+index,
                    text: $('#activity_answer_text').attr('value'),
                    value: $('#activity_answer_value').attr('value'),
                    order: index,
                    last: true,
                    type_question: this.model.get('type'),
                    right: right
                });
            this.create_view(answer);
       },

       create_view: function(answer) {
            var self = this;
            if(this.validate_answer(answer)) {
                var answer_view = new Admin.RespuestaItemView({
                    model: answer,
                    edit_mode: true
                });
                answer_view.bind('up_sort', self.re_arrange_answers);
                answer_view.bind('down_sort', self.re_arrange_answers);
                answer_view.bind('change_model', self.render_answers);
                answer_view.bind('edit', self.edit_answer);
                self.answers_view.push(answer_view);
                self.answers.add(answer);
            }

            this.reset_edition_answer();
        },

        reset_super_type_type_question: function() {
            this.answers_view.length = 0;
            this.answers.reset();
            $('#activity_edit_answers').html('');
        },

        reset_edition_answer: function() {
            $('#activity_answer_text').attr('value', '');
            $('#activity_answer_value').attr('value', '');
            $('#activity_answer_right').removeAttr('checked');
            $('#button_activity_question_add').css('display', 'inline');
            $('#button_activity_question_save').css('display', 'none');
            $('#button_activity_question_cancel').css('display', 'none');
        },

        validate_answer: function(answer) {
            if(answer.get('text') == '') {
                Message.show_error('Finsert text de answer');
                return false;
            }
            return true;
        },

        re_arrange_answers: function(answer, up) {
            var current_index = answer.get('order') ,
                next_index = up ? answer.get('order') -1 : answer.get('order') + 1;

            var answer_view = _.find(this.answers_view, function(view) {
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

        render_answers: function() {
            var model = this.model,
                self = this;

            $('#activity_edit_answers').html('');
            var answers_models = _.sortBy(this.answers_view, function(view) {
               return view.model.get('order'); 
            });
            _.each(answers_models, function(answer) {
                $('#activity_edit_answers').append(answer.render().el);
                answer.delegateEvents();
            });
        },

        set_text: function() {
            this.model.set({
                text: JSON.stringify($('#activity_content_text').getCode())
            });
        },

        get_resources: function(type) {
            var self = this;
            this.resources.fetch({
                url: '/admin/resources/list_resources',
	        	data: {
                    code_process: this.code_process
                },
                success: function() {
                    self.types_resources.reset(self.resources.context.get('resources'));
                }
            });
            
        },

        set_property: function(e) {
            var map = {};
            var property_id = this.input_property_map[e.target.id];
            var value = e.target.value;
            map[property_id] =  value;
            this.model.set(map);
        },

        input_property_map: {
            activity_specials_video_resources: 'datapath',
            activity_specials_flash_resources: 'datapath',
            activity_super_type_content: 'super_type_contentido',
            activity_super_type_question: 'super_type_question',
            activity_summary: 'description',
            activity_question: 'question',
            activity_super_type_type: 'type'
        }

    });

}).call(this);

