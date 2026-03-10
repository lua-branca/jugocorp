<?php
session_start();

// CSRFトークンとPOSTリクエストの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('不正なリクエストです(CSRF token validation failed)');
}

// データのサニタイズ（無害化）
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
$post_data = sanitize($_POST);
$_SESSION['form_data'] = $post_data; // 戻る時のためにセッションに保存

// バリデーション（入力チェック）
$errors = [];
if (empty($post_data['name'])) $errors[] = 'お名前が入力されていません。';
if (empty($post_data['email']) || !filter_var($post_data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = '正しいメールアドレスを入力してください。';
if (empty($post_data['phone']) || !preg_match('/\A[0-9\-]+\z/', $post_data['phone'])) $errors[] = '電話番号は数字とハイフンで入力してください。';
if (empty($post_data['zip'])) $errors[] = '郵便番号を入力してください。';
if (empty($post_data['pref'])) $errors[] = '都道府県を選択してください。';
if (empty($post_data['address_line1'])) $errors[] = '市区町村・番地を入力してください。';
if (empty($post_data['item_set']) || !in_array($post_data['item_set'], ['6', '12', '24'])) $errors[] = '購入セットを正しく選択してください。';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: form.php');
    exit;
}

// 追加送料のチェック（北海道・沖縄かどうか）
$additional_shipping = false;
$shipping_message = '送料無料';
if ($post_data['pref'] === '北海道' || $post_data['pref'] === '沖縄県') {
    $additional_shipping = true;
    $shipping_message = '+800円 (北海道・沖縄宛追加送料)';
}

$csrf_token = $_SESSION['csrf_token'];

$item_set_labels = [
    '6' => '6個セット (3,480円・送料込)',
    '12' => '12個セット (5,980円・送料込)',
    '24' => '24個セット (10,980円・送料込)'
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>十郷米缶 入力内容の確認</title>
    <!-- GA -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-X987DFDHNH"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-X987DFDHNH');
    </script>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&family=Shippori+Mincho:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .form-container { max-width: 700px; margin: 60px auto; padding: 50px 40px; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-container h2 { font-family: 'Shippori Mincho', serif; color: #1a2a44; font-size: 2rem; text-align: center; margin-bottom: 20px; }
        .form-container > p { text-align: center; color: #555; margin-bottom: 40px; line-height: 1.6; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; border-top: 2px solid #1a2a44; }
        th, td { padding: 15px 20px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #fdfcf9; width: 35%; color: #1a2a44; font-weight: bold; }
        td { color: #333; line-height: 1.6; }
        
        .btn-group { display: flex; justify-content: center; gap: 20px; margin-top: 20px; }
        .submit-btn, .back-btn { padding: 18px 30px; font-size: 1.1em; font-weight: bold; letter-spacing: 0.05em; border-radius: 50px; text-align: center; text-decoration: none; transition: all 0.3s ease; min-width: 200px; border: 2px solid #1a2a44; cursor: pointer; }
        .submit-btn { background-color: #1a2a44; color: #fff; }
        .submit-btn:hover { background-color: #fff; color: #1a2a44; }
        .back-btn { background-color: #fff; color: #1a2a44; }
        .back-btn:hover { background-color: #f4f5f7; }
        
        @media (max-width: 600px) {
            .btn-group { flex-direction: column; }
            .submit-btn, .back-btn { width: 100%; min-width: auto; }
            th, td { display: block; width: 100%; }
            th { border-bottom: none; padding-bottom: 5px; }
            td { padding-top: 0; padding-bottom: 20px; }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>お申し込み内容の確認</h2>
        <p>以下の内容でよろしければ、ボタンを押してください。<br>
        <?php if ($post_data['payment_method'] === 'bank'): ?>
            ※次ページで振込先口座をご案内します。
        <?php else: ?>
            ※次ページでクレジットカード決済用のリンクをご案内します。
        <?php endif; ?>
        </p>

        <table>
            <tr><th>お名前</th><td><?php echo $post_data['name']; ?></td></tr>
            <tr><th>メール</th><td><?php echo $post_data['email']; ?></td></tr>
            <tr><th>電話番号</th><td><?php echo $post_data['phone']; ?></td></tr>
            <tr><th>送付先</th><td>
                〒<?php echo $post_data['zip']; ?><br>
                <?php echo $post_data['pref'] . $post_data['address_line1'] . '<br>' . $post_data['address_line2']; ?>
            </td></tr>
            <tr><th>購入セット</th><td><?php echo isset($item_set_labels[$post_data['item_set']]) ? $item_set_labels[$post_data['item_set']] : $post_data['item_set'] . '個セット'; ?></td></tr>
            <tr><th>お支払い方法</th><td><?php echo ($post_data['payment_method'] === 'bank') ? '銀行振込' : 'クレジットカード決済'; ?></td></tr>
            <tr><th>送料について</th><td style="color: <?php echo $additional_shipping ? 'red' : 'green'; ?>; font-weight: bold;"><?php echo $shipping_message; ?></td></tr>
            <tr><th>備考</th><td><?php echo nl2br($post_data['remarks']); ?></td></tr>
        </table>

        <form action="complete.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="btn-group">
                <a href="form.php" class="back-btn">修正する</a>
                <button type="submit" class="submit-btn" id="submitBtn">
                    <?php echo ($post_data['payment_method'] === 'bank') ? '注文を確定する' : '決済画面へ進む'; ?>
                </button>
            </div>
        </form>
        <script>
            // 二重送信防止処理
            document.querySelector('form').addEventListener('submit', function() {
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('submitBtn').innerHTML = '処理中...';
            });
        </script>
    </div>
</body>
</html>
