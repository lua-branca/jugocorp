<?php
header('Content-Type: application/json; charset=utf-8');

// Configuration
$ADMIN_EMAIL = 'contact@jugo-japan.jp, fukuda.misako@gmail.com'; // TODO: テスト完了後にfukuda.misako@gmail.comを削除する
$SENDER_EMAIL = 'contact@jugo-japan.jp';
$SENDER_NAME = '一般社団法人 十郷';

// GAS Web App URL for Google Spreadsheet integration
$GAS_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbytB-zF9_39p9BTd2hGkypWSmf_iV_4D5Enw2Lyr8Qf7QBVEp0ryhVL3SHSaJc_tSLv/exec';

// Security: Simple Honeypot Check
// If you add a hidden field 'confirm_code' in HTML, it acts as a trap for bots.
if (!empty($_POST['confirm_code'])) {
    echo json_encode(['result' => 'success', 'bot_detected' => true]);
    exit;
}

// Basic Sanitization
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Function to send data to GAS
function sendToGas($url, $data)
{
    if (empty($url))
        return;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject_input = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['result' => 'error', 'message' => '必須項目が入力されていません。']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['result' => 'error', 'message' => 'メールアドレスの形式が正しくありません。']);
        exit;
    }

    // Send to Google Sheets (GAS)
    $gas_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject_input,
        'message' => $message
    ];
    sendToGas($GAS_SCRIPT_URL, $gas_data);

    // Prepare Email Headers
    $admin_headers = "From: " . mb_encode_mimeheader($SENDER_NAME) . " <" . $SENDER_EMAIL . ">\r\n";
    $admin_headers .= "Reply-To: " . $email . "\r\n";

    $user_headers = "From: " . mb_encode_mimeheader($SENDER_NAME) . " <" . $SENDER_EMAIL . ">\r\n";

    // ---------------------------------------------------------
    // User Auto-Reply Email
    // ---------------------------------------------------------
    $user_subject = "【十郷(JUGO)】お問い合わせありがとうございます";

    $user_body = "{$name} 様\n\n";
    $user_body .= "この度はお問い合わせいただき、誠にありがとうございます。\n";
    $user_body .= "以下の内容でメッセージを受け付けました。\n\n";
    $user_body .= "--------------------------------------------------\n";
    $user_body .= "■お名前：{$name}\n";
    $user_body .= "■メールアドレス：{$email}\n";
    $user_body .= "■電話番号：{$phone}\n";
    $user_body .= "■件名：{$subject_input}\n";
    $user_body .= "■メッセージ：\n{$message}\n";
    $user_body .= "--------------------------------------------------\n\n";
    $user_body .= "内容を確認の上、通常3営業日以内に担当者よりご返信申し上げます。\n";
    $user_body .= "今しばらくお待ちいただけますようお願い申し上げます。\n\n";
    $user_body .= "--\n";
    $user_body .= "一般社団法人 十郷 (JUGO)\n";
    $user_body .= "URL: https://jugo-japan.jp\n";

    // ---------------------------------------------------------
    // Admin Notification Email
    // ---------------------------------------------------------
    $admin_subject = "【十郷】Webサイトから新しいお問い合わせがありました（{$name}様）";

    $admin_body = "Webサイトのコンタクトフォームより、新しいお問い合わせがありました。\n";
    $admin_body .= "このメールに返信すると、お問い合わせ者（{$email}）へメールが送れます。\n\n";
    $admin_body .= "【お問い合わせ内容】\n";
    $admin_body .= "--------------------------------------------------\n";
    $admin_body .= "■お名前：{$name}\n";
    $admin_body .= "■メールアドレス：{$email}\n";
    $admin_body .= "■電話番号：{$phone}\n";
    $admin_body .= "■件名：{$subject_input}\n";
    $admin_body .= "■メッセージ：\n{$message}\n";
    $admin_body .= "--------------------------------------------------\n\n";

    // Send Emails
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");

    mb_send_mail($ADMIN_EMAIL, $admin_subject, $admin_body, $admin_headers);
    mb_send_mail($email, $user_subject, $user_body, $user_headers);

    // Return JSON success
    echo json_encode(['result' => 'success']);
    exit;

} else {
    echo json_encode(['result' => 'error', 'message' => '不正なリクエストです。']);
    exit;
}
?>