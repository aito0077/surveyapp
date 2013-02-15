<!DOCTYPE html>
<html metal:use-macro="./wrapper/main.html/layout">
    <div id="main_edit" metal:fill-slot="main_content">
        <section id="section_process">
            <div class="page-header">
                <h3>Proceso</h3>
            </div>
            <div id="form_process">
                <div class="controls controls-row">
                    <label class="span2" for="process_code">Code</label>
                    <input type="text" id="process_code" class="input-small span2"  placeholder="Code..."/>
                </div>
                <div class="controls controls-row">
                    <label class="span2" for="process_summary">Summary</label>
                    <input type="text" id="process_summary" class="input-xxlarge span6"  placeholder="Summary..."/>
                </div>

                <div class="controls controls-row">
                    <label class="span2" for="process_type">Type</label>
                    <select class="span5" id="process_type">
                        <option value="competition" selected="true">Competition</option>
                        <option value="evaluation">Evaluation</option>
                        <option value="poll">Poll</option>
                    </select>
                </div>

                <div class="controls controls-row">
                    <label class="span2" for="process_date_start">Date Start</label>
                    <input type="text" id="process_date_start" class="input-small span2 datepicker"  placeholder="Date Start..."/>
                    <label class="span2" for="process_date_end">Date End</label>
                    <input type="text" id="process_date_end" class="input-small span2 datepicker"  placeholder="Date End..."/>
                 </div>
             </div>
        </section>

        <section id="section_questionnaire">
            <div class="page-header">
                <div class="row">
                    <div class="span2">
                        <h3>Questionnaire</h3>
                    </div>
                    <div class="span3 offset9">
                        <div class="btn btn-primary" id="button_add_question">Add Question</div>
                    </div>
                </div>

            </div>
            <div class="accordion" id="questionnaire">
                <div class="accordion-group">
                    <div class="accordion-heading">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#questionnaire" href="#question_1">
                           <i class="icon-list"></i> Question 1
                        </a>
                    </div>
                    <div id="question_1" class="accordion-body collapse in">
                        <div class="accordion-inner">
                            <div class="row">
                                <div class="span5">Primera Answer</div>
                                <div class="span1"></div>
                                <div class="span1"><button class="btn btn-link"><i class="icon-pencil"></i></button></div>
                                <div class="span1"><button class="btn btn-link"><i class="icon-remove-sign"></i></button></div>
                                <div class="span1"></div>
                                <div class="span1"><button class="btn btn-link"><i class="icon-arrow-up"></i></button></div>
                                <div class="span1"><button class="btn btn-link"><i class="icon-arrow-down"></i></button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
        <div class="form-actions">
            <button type="button" id="button_guardar_process" class="btn btn-primary">Guardar</button>
            <button type="button" id="button_cancelar_process" class="btn">Cancelar</button>
        </div>

        <!--dialogs-->
        <div class="modal" id="dialog_editar_question" style="display:none" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-show="false">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel">Editor Question</h3>
            </div>
            <div class="modal-body">

                <div class="form-horizontal">
                    <label>Descripcion</label>
                    <input type="text" id="process_question" class="input-small"  placeholder="Code..."/>
                    <label>Type</label>
                    <select>
                        <option>Seleccion &uacute;nica</option>
                        <option>Seleccion m&uacute;ltiple</option>
                        <option>Libre</option>
                        <option>Valor</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="button_cancelar_question" class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
                <button id="button_guardar_question" class="btn btn-primary">Guardar</button>
            </div>
        </div>
        <!-- /dialogs -->
    </div>

    <tal:block metal:fill-slot="main_js_templates">
    <!-- templates -->
    <script type="text/template" id="item-question-template">
        <div class="accordion-group" id="grupo_question_{{code_question}}"> 
            <div class="accordion-heading"> 
                <div class="row">
                    <div class="span9">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#questionnaire" href="#{{code_question}}">                    <i class="icon-list"></i> {{descripcion_question}}
                        </a>
                    </div>
                </div>
            </div> 
            <div id="{{code_question}}" class="accordion-body collapse in"> 
               <div class="accordion-inner"> 
                    <div class="row">
                        <div class="span3 offset9">
                            <div class="btn btn-success" id="button_add_question">Add Answer</div>
                        </div>
                    </div>
                    <div id="answers_{{code_question}}">
                    </div>
                </div> 
            </div> 
         </div> 
    </script>
    <script type="text/template" id="item-answer-template">
        <div id="{{code_answer}}" class="row"> 
            <div class="span5">{{descripcion_answer}}</div> 
            <div class="span1"></div> 
            <div class="span1">
                <button class="btn btn-link">
                   <i class="icon-pencil"></i>
                </button>
            </div> 
            <div class="span1">
                <button class="btn btn-link">
                    <i class="icon-remove-sign"></i>
                </button>
            </div> 
            <div class="span1"> </div> 
            <div class="span1"></div> 
            <div class="span1"></div> 
        </div> 
    </script>

    <script src="/static/js/admin/edit.js"></script>
    <script>
        var edit = new Admin.Edit({
            el: $('#main_edit')
        });
    </script>

    </tal:block> <!-- /container -->
</html>
