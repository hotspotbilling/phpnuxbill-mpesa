
<?php


/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * Payment Gateway tripay.com
 **/

function mpesa_validate_config()
{
    global $config;
    if (empty($config['mpesa_consumer_key']) || empty($config['mpesa_consumer_secret'])) {
        sendTelegram("M-Pesa payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup M-Pesa payment gateway, please tell admin"));
    }
}

function mpesa_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'M-Pesa - Payment Gateway - ' . $config['CompanyName']);
    $ui->display('mpesa.tpl');
}

function mpesa_save_config()
{
    global $admin, $_L;
    $mpesa_consumer_key = _post('mpesa_consumer_key');
    $mpesa_consumer_secret = _post('mpesa_consumer_secret');
    $mpesa_business_code = _post('mpesa_business_code');
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_consumer_key')->find_one();
    if ($d) {
        $d->value = $mpesa_consumer_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_consumer_key';
        $d->value = $mpesa_consumer_key;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_consumer_secret')->find_one();
    if ($d) {
        $d->value = $mpesa_consumer_secret;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_consumer_secret';
        $d->value = $mpesa_consumer_secret;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_business_code')->find_one();
    if ($d) {
        $d->value = $mpesa_business_code;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_business_code';
        $d->value = $mpesa_business_code;
        $d->save();
    }

    _log('[' . $admin['username'] . ']: M-Pesa ' . $_L['Settings_Saved_Successfully'] . json_encode($_POST['mpesa_channel']), 'Admin', $admin['id']);

    r2(U . 'paymentgateway/mpesa', 's', $_L['Settings_Saved_Successfully']);
}


function mpesa_create_transaction($trx, $user)
{
    global $config, $routes;
    $timestamp = date('YmdHis');
    $json = [
        'BusinessShortCode' => $config['mpesa_business_code'],
        'Password' => sha1($timestamp),
        'Amount' => $trx['price'],
        'Timestamp' => $timestamp,
        'TransactionType' =>  'CustomerPayBillOnline',
        'PartyA' => $user['phonenumber'],
        'PhoneNumber' => $user['phonenumber'],
        'PartyB' => $config['mpesa_business_code'],
        'AccountReference' => $config['App_Name'],
        'TransactionDesc' => $trx['plan_name'],
        'CallBackURL' => U . 'callback/mpesa'
    ];
    $result = json_decode(Http::postJsonData(mpesa_get_server() . 'transaction/create', $json, ['Authorization: Bearer ' . $config['mpesa_api_key']]), true);
    if ($result['success'] != 1) {
        sendTelegram("Tripay payment failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction."));
    }
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $result['data']['reference'];
    $d->pg_url_payment = $result['data']['checkout_url'];
    $d->pg_request = json_encode($result);
    $d->expired_date = date('Y-m-d H:i:s', $result['data']['expired_time']);
    $d->save();
    r2(U . "order/view/" . $d['id'], 's', Lang::T("Create Transaction Success"));

}

function mpesa_get_status($trx, $user)
{
    global $config;
    $result = json_decode(Http::getData(mpesa_get_server() . 'transaction/detail?' . http_build_query(['reference' => $trx['gateway_trx_id']]), [
        'Authorization: Bearer ' . $config['mpesa_api_key']
    ]), true);
    if ($result['success'] != 1) {
        sendTelegram("Tripay payment status failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Payment check failed."));
    }
    $result =  $result['data'];
    if ($result['status'] == 'UNPAID') {
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    } else if (in_array($result['status'], ['PAID', 'SETTLED']) && $trx['status'] != 2) {
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'],  $result['payment_name'])) {
            r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Failed to activate your Package, try again later."));
        }

        $trx->pg_paid_response = json_encode($result);
        $trx->payment_method = $result['payment_method'];
        $trx->payment_channel = $result['payment_name'];
        $trx->paid_date = date('Y-m-d H:i:s', $result['paid_at']);
        $trx->status = 2;
        $trx->save();

        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } else if (in_array($result['status'], ['EXPIRED', 'FAILED', 'REFUND'])) {
        $trx->pg_paid_response = json_encode($result);
        $trx->status = 3;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction expired."));
    } else if ($trx['status'] == 2) {
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction has been paid.."));
    }
}

function mpesa_payment_notification(){

}

/**
 * security credentials
 * AixVls8CgSd8hJSVKkBAizjhrRqQzn6aqBavbmF3xlD1StBJDgEqiFusq/1YIMjjtKgH3iDOemMFfbeyIFy3qbcowY8ehvuv+vIu+i6hCRrpjq7Xbgf54Gp/8l9RyawtWpmjChZ1zr1eg60nurRC6EyT17CcmdbRUZ1Hs56ruWQg3Oal+daSL5dvAMUX4iCnBk4ktyaTUXEAq/3s9zRvIvdAx9ySsXKTh0T+GRw8BxSIX7aNNQbae/wjqmpu8MltMpY0tplyrCimq1DY+o0IKbGz8oNJUF6lWGtm/VyLFoDoxqr3IG08L4VV6KtNhhd+zvvUY5+wShkhZFrBFIA10A==
 */

function mpesa_get_access_token()
{
    global $config;
    $cacheFile = 'system/cache/'.md5($config['mpesa_consumer_key']).'.nux';
    // check if the cache file exists and not expired
    if(file_exists($cacheFile) && time()-filemtime($cacheFile)<3500){
        return file_get_contents($cacheFile);
    }
    $result = Http::getData(mpesa_get_server().'generate?grant_type=client_credentials',['Authorization: Bearer ' .
        base64_encode( $config['mpesa_consumer_key'].':'. $config['mpesa_consumer_secret'])
    ]);
    /**
        {
        "access_token": "",
        "expires_in": "3599"
        }
     */
    $json = json_decode($result,true);
    // cache the result
    file_put_contents($cacheFile, $json['access_token']);
    return $json['access_token'];
}


function mpesa_get_server()
{
    global $_app_stage;
    if ($_app_stage == 'Live') {
        return 'https://sandbox.safaricom.co.ke/oauth/v1/';
    } else {
        return 'https://safaricom.co.ke/oauth/v1/';
    }
}
