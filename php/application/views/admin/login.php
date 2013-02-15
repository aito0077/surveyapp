<!DOCTYPE html>
<html metal:use-macro="./wrapper/main.html/layout">
    <div id="main_edit" metal:fill-slot="main_content">

        <div tal:condition="authentication">
            <div class="alert" tal:condition="authentication/error">
                <button type="button" class="close" data-dismiss="alert">×</button>
                <strong tal:replace="authentication/error">Error Message</strong>
            </div>
        </div>

        <form class="form-horizontal" method="POST" action="/admin/login/do_login">
            <div class="control-group">
                <label class="control-label" for="user">User</label>
                <div class="controls">
                    <input type="text" id="user" name="user" placeholder="User" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="password">Password</label>
                <div class="controls">
                    <input type="password" id="password" name="password" placeholder="Password" />
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn">Login</button>
                </div>
            </div>
        </form>

    </div> <!-- /container -->

    <tal:block metal:fill-slot="main_js_templates">
        <script>
            $('#user').focus();
        </script>
    </tal:block>
</html>
