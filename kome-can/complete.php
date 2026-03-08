<?php
session_start();

// 直接アクセス・CSRF防止
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['form_data']) || empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('不正なリクエストです(Session data missing or CSRF invalid)。');
}

$post_data = $_SESSION['form_data'];

// 設定値
$GAS_URL = 'https://script.google.com/macros/s/AKfycbzGiDx6Nuqi5AoOBjbgeMxDC9mEv5hD5jVa7OwYvQDyC8vRjRLgop6bBddQ_PrOMoADjg/exec';

// Stripe決済リンクの定義
$stripe_links = [
    'standard' => [
        '6' => 'https://buy.stripe.com/cNiaEY1h3fxo5nY5Md5c401',
        '12'=> 'https://buy.stripe.com/14A5kEbVH2KC5nY5Md5c402',
        '24'=> 'https://buy.stripe.com/28E6oIe3P84WaIi4I95c403'
    ],
    'additional' => [
        '6' => 'https://buy.stripe.com/eVqaEYgbXgBs7w61vX5c404',
        '12'=> 'https://buy.stripe.com/bJe8wQ4tffxo7w68Yp5c405',
        '24'=> 'https://buy.stripe.com/cNibJ2gbXad4bMm7Ul5c406'
    ]
];

$set_size = $post_data['item_set'];
$is_additional = ($post_data['pref'] === '北海道' || $post_data['pref'] === '沖縄県');
$link_type = $is_additional ? 'additional' : 'standard';
$payment_url = $stripe_links[$link_type][$set_size];

$item_set_labels = [
    '6' => '6個セット (3,480円・送料込)',
    '12' => '12個セット (5,980円・送料込)',
    '24' => '24個セット (10,980円・送料込)'
];
$set_label = isset($item_set_labels[$set_size]) ? $item_set_labels[$set_size] : $set_size . '個セット';

$post_data_for_gas = $post_data;
$post_data_for_gas['item_set'] = $set_label;

// GASへのデータ送信処理 (cURL)
// ※GASのURLはセキュリティ上必ず302リダイレクトされるため、CURLOPT_FOLLOWLOCATIONが必須です
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $GAS_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data_for_gas));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
$response = curl_exec($ch);

// メールの送信処理
mb_language("Japanese");
mb_internal_encoding("UTF-8");

$admin_email = "contact@jugo-japan.jp";
$sender_email = "contact@jugo-japan.jp";
$sender_name = "一般社団法人 十郷 (JUGO)";

// ヘッダー生成関数 (なりすまし防止・Return-Path設定)
$headers = "From: " . mb_encode_mimeheader($sender_name) . " <" . $sender_email . ">\n";
$headers .= "Reply-To: " . $sender_email . "\n";
$return_path = "-f " . $sender_email;

// お客様への自動返信メール
$user_subject = "【十郷(JUGO)】米缶のご注文を受け付けました（決済のお願い）";
$user_body = $post_data['name'] . " 様\n\n";
$user_body .= "この度は「十郷米缶」をお申し込みいただき、誠にありがとうございます。\n";
$user_body .= "以下のURLより、クレジットカードでの決済（お支払い）をお願いいたします。\n\n";
$user_body .= "▼決済用URL（Stripe）\n";
$user_body .= $payment_url . "\n\n";
$user_body .= "※決済が完了した時点で、ご注文の確定となります。\n";
$user_body .= "--------------------------------------------------\n";
$user_body .= "■お名前：" . $post_data['name'] . "\n";
$user_body .= "■ご住所：〒" . $post_data['zip'] . " " . $post_data['pref'] . $post_data['address_line1'] . " " . $post_data['address_line2'] . "\n";
$user_body .= "■ご注文セット：" . $set_label . "\n";
$user_body .= "--------------------------------------------------\n\n";
$user_body .= "--\n一般社団法人 十郷 (JUGO)\nURL: https://jugo-japan.jp\n";

@mb_send_mail($post_data['email'], $user_subject, $user_body, $headers, $return_path);

// 管理者への通知メール
$admin_subject = "【自動通知】米缶の新規お申し込みがありました";
$admin_body = "Webサイトより、米缶の新規お申し込みがありました。\n";
$admin_body .= "データはスプレッドシートにも自動記録されています。\n\n";
$admin_body .= "【ご注文内容】\n";
$admin_body .= "■お名前：" . $post_data['name'] . "\n";
$admin_body .= "■メール：" . $post_data['email'] . "\n";
$admin_body .= "■電話番号：" . $post_data['phone'] . "\n";
$admin_body .= "■ご住所：〒" . $post_data['zip'] . " " . $post_data['pref'] . $post_data['address_line1'] . " " . $post_data['address_line2'] . "\n";
$admin_body .= "■セット：" . $set_label . "\n";
$admin_body .= "■備考：" . $post_data['remarks'] . "\n";
$admin_body .= "\n※現在は「未決済」状態です。Stripe決済完了後にGASが自動で処理します。\n\n";
$admin_body .= "▼最新の注文状況（スプレッドシート）はこちらから確認できます：\n";
$admin_body .= "https://docs.google.com/spreadsheets/d/1L6a4ltEyOR3Wa26JlzdUDpnISLrq-UQqoJDsOuZMP6I/edit?gid=0#gid=0\n";

@mb_send_mail($admin_email, $admin_subject, $admin_body, $headers, $return_path);

// セッションクリア（注文完了したため）
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>十郷米缶 お申し込み完了</title>
    <!-- GA -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-X987DFDHNH"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-X987DFDHNH');
        
        // 注文完了（コンバージョン）トラッキング
        gtag('event', 'generate_lead', {
            'event_category': 'KomeCan',
            'event_label': 'Purchase_Complete'
        });
    </script>
    <!-- 決済ページへ自動リダイレクトさせる設定 -->
    <meta http-equiv="refresh" content="5;url=<?php echo $payment_url; ?>">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f9f9f9; }
        .form-container { max-width: 600px; margin: 60px auto; padding: 40px; border: none; background: #fff; text-align: center; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-container h2 { font-family: 'Shippori Mincho', serif; color: #1a2a44; margin-bottom: 20px; font-size: 1.8rem; }
        .form-container p { color: #555; margin-bottom: 30px; line-height: 1.6; }
        .spinner { margin: 30px auto; width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #c5a059; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .payment-btn { display: inline-block; padding: 18px 40px; background-color: #1a2a44; color: #fff; text-decoration: none; font-size: 1.1em; font-weight: bold; border-radius: 50px; margin-top: 20px; transition: all 0.3s ease; border: 2px solid #1a2a44; }
        .payment-btn:hover { background-color: #fff; color: #1a2a44; }
        hr { border: none; border-top: 1px solid #eee; margin: 30px 0; }
        h3 { color: #1a2a44; font-size: 1.2rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>ご注文（仮予約）を<br>受け付けました</h2>
        <p>ご入力いただいたメールアドレス宛に<br>お申し込み控えを自動送信しました。</p>
        <hr>
        <h3>続いて、クレジットカード決済にお進みください。</h3>
        <p style="font-size: 0.9em; color: #e50012;">※決済が完了した時点で、ご注文の確定となります。</p>
        
        <div class="spinner"></div>
        <p>5秒後に自動的に決済画面（Stripe）へ移動します。</p>
        
        <p>自動で移動しない場合は、下のボタンを押してください。</p>
        <a href="<?php echo $payment_url; ?>" class="payment-btn">クレジットカード決済画面へ</a>
    </div>
</body>
</html>
