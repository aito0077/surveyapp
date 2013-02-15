<!DOCTYPE html>
<html metal:use-macro="./wrapper/main.html/layout">
    <div id="main_edit" metal:fill-slot="main_content">
        <table class="table table-striped table-hover">
            <caption><h2>Processes</h2></caption>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Summary</th>
                    <th>State</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr tal:repeat="process result/processes"  tal:attributes="class php: process.state == 'INACTIVO' ? 'error' : NULL">
                    <td tal:content="process/code">COD1</td>
                    <td tal:content="process/summary">Blah blah</td>
                    <td tal:content="process/state">ACTIVE</td>
                    <td><a class="btn btn-small btn-primary" href="/admin/edit/view/${process/code}">View</a></td>
                </tr>
           </tbody>
        </table>

    </div> <!-- /container -->

</html>
