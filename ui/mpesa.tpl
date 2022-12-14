{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/mpesa" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">M-Pesa</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_consumer_key" name="mpesa_consumer_key" placeholder="xxxxxxxxxxxxxxxxx" value="{$_c['mpesa_consumer_key']}">
                            <a href="https://developer.safaricom.co.ke/MyApps" target="_blank" class="help-block">https://developer.safaricom.co.ke/MyApps</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Secret</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="mpesa_consumer_secret" name="mpesa_consumer_secret" placeholder="xxxxxxxxxxxxxxxxx" value="{$_c['mpesa_consumer_secret']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Business Shortcode</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_business_code" name="mpesa_business_code" placeholder="xxxxxxx" maxlength="7" value="{$_c['mpesa_business_code']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Url Notification</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()" value="{$_url}callback/mpesa">
                            <p class="help-block">CallBack URL</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                        </div>
                    </div>
                        <pre>/ip hotspot walled-garden
add dst-host=duitku.com
add dst-host=*.duitku.com</pre>
                </div>
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}
