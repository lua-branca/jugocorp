<?php
session_start();

// CSRFトークンの生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// セッションに保存された入力データがあれば復元（戻るボタン対策）
$data = $_SESSION['form_data'] ?? [];

// LPから「このセットを選択」ボタンで直接遷移してきた場合、そのセットを優先して選択する
if (isset($_GET['set']) && in_array($_GET['set'], ['6', '12', '24'])) {
    $data['item_set'] = $_GET['set'];
}

$prefs = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>十郷米缶 お申し込みフォーム</title>
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
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-weight: 700; color: #1a2a44; margin-bottom: 8px; font-size: 0.95rem; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"], .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; font-family: 'Noto Sans JP', sans-serif; transition: border-color 0.3s; background: #fafafa; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #c5a059; outline: none; background: #fff; }
        .form-group .required { color: #fff; background-color: #e50012; font-size: 0.75em; padding: 2px 6px; border-radius: 3px; margin-left: 8px; vertical-align: middle; font-weight: normal; }
        /* 商品選択カスタムカード */
        .item-select-group { display: flex; flex-direction: column; gap: 15px; margin-top: 10px; }
        .item-select-label { display: block; cursor: pointer; margin: 0; }
        .item-select-label input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .item-card-content { display: flex; align-items: center; padding: 25px 20px; border: 2px solid #eaeaea; border-radius: 12px; transition: all 0.3s ease; background: #fff; }
        .item-select-label:hover .item-card-content { border-color: #c5a059; background: #faf9f6; }
        .item-select-label input[type="radio"]:checked + .item-card-content { border-color: #c5a059; background: #faf9f6; box-shadow: 0 5px 15px rgba(197, 160, 89, 0.1); }
        
        .radio-mark { width: 24px; height: 24px; border: 2px solid #ccc; border-radius: 50%; display: inline-block; position: relative; margin-right: 20px; flex-shrink: 0; transition: all 0.3s; background: #fff; }
        .item-select-label input[type="radio"]:checked + .item-card-content .radio-mark { border-color: #c5a059; border-width: 7px; }
        
        .item-info { flex-grow: 1; display: flex; justify-content: space-between; align-items: center; }
        .item-name { font-size: 1.25rem; font-weight: bold; color: #1a2a44; font-family: 'Shippori Mincho', serif; display: flex; flex-direction: column; gap: 4px; }
        .item-name small { font-size: 0.8rem; color: #666; font-family: 'Noto Sans JP', sans-serif; font-weight: normal; }
        .item-price { font-size: 1.4rem; font-weight: bold; color: #e50012; }
        .item-price span { font-size: 0.9rem; font-weight: normal; color: #333; margin-left: 2px;}
        @media (max-width: 500px) {
            .item-info { flex-direction: column; align-items: flex-start; gap: 8px; }
            .item-card-content { padding: 20px 15px; }
        }
        .error-message { color: #e50012; font-weight: bold; margin-bottom: 30px; padding: 15px; background: #ffeeee; border-radius: 6px; border-left: 4px solid #e50012; }
        .error-message ul { padding-left: 20px; margin: 0; }
        .submit-btn { display: block; width: 100%; max-width: 300px; margin: 40px auto 0; padding: 18px 30px; background-color: #1a2a44; color: #fff; border: 2px solid #1a2a44; font-size: 1.1em; font-weight: bold; letter-spacing: 0.05em; cursor: pointer; border-radius: 50px; transition: all 0.3s ease; }
        .submit-btn:hover { background-color: #fff; color: #1a2a44; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>十郷米缶 お申し込み</h2>
        <p>以下のフォームに必要事項をご入力の上、<br>「確認画面へ進む」ボタンを押してください。</p>

        <?php if (!empty($_SESSION['errors'])): ?>
            <div class="error-message">
                <ul>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <form action="confirm.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>お名前（フルネーム） <span class="required">必須</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label>メールアドレス <span class="required">必須</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label>電話番号 <span class="required">必須</span></label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($data['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label>郵便番号 <span class="required">必須</span></label>
                <input type="text" name="zip" value="<?php echo htmlspecialchars($data['zip'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="例: 123-4567" required>
            </div>

            <div class="form-group">
                <label>都道府県 <span class="required">必須</span></label>
                <select name="pref" required>
                    <option value="">選択してください</option>
                    <?php foreach ($prefs as $pref): ?>
                        <?php $selected = (($data['pref'] ?? '') === $pref) ? 'selected' : ''; ?>
                        <option value="<?php echo $pref; ?>" <?php echo $selected; ?>><?php echo $pref; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>市区町村・番地 <span class="required">必須</span></label>
                <input type="text" name="address_line1" value="<?php echo htmlspecialchars($data['address_line1'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label>建物名・部屋番号など</label>
                <input type="text" name="address_line2" value="<?php echo htmlspecialchars($data['address_line2'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>購入セット <span class="required">必須</span></label>
                <p style="font-size: 0.85rem; color: #e50012; margin-bottom: 10px; font-weight: bold;">※北海道・沖縄県への配送は、追加送料として別途+800円を頂戴いたします。</p>
                <div class="item-select-group">
                    <label class="item-select-label">
                        <input type="radio" name="item_set" value="6" <?php echo (($data['item_set'] ?? '') === '6' || !isset($data['item_set'])) ? 'checked' : ''; ?>>
                        <div class="item-card-content">
                            <span class="radio-mark"></span>
                            <div class="item-info">
                                <span class="item-name">6個セット <small>お試しや手土産に最適</small></span>
                                <span class="item-price">3,480<span>円 (送料込)</span></span>
                            </div>
                        </div>
                    </label>
                    <label class="item-select-label">
                        <input type="radio" name="item_set" value="12" <?php echo (($data['item_set'] ?? '') === '12') ? 'checked' : ''; ?>>
                        <div class="item-card-content">
                            <span class="radio-mark"></span>
                            <div class="item-info">
                                <span class="item-name">12個セット <small>ご家庭での日常使いに</small></span>
                                <span class="item-price">5,980<span>円 (送料込)</span></span>
                            </div>
                        </div>
                    </label>
                    <label class="item-select-label">
                        <input type="radio" name="item_set" value="24" <?php echo (($data['item_set'] ?? '') === '24') ? 'checked' : ''; ?>>
                        <div class="item-card-content">
                            <span class="radio-mark"></span>
                            <div class="item-info">
                                <span class="item-name">24個セット <small>一番お得なまとめ買い</small></span>
                                <span class="item-price">10,980<span>円 (送料込)</span></span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>備考・ご要望</label>
                <textarea name="remarks" rows="4"><?php echo htmlspecialchars($data['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <button type="submit" class="submit-btn">確認画面へ進む</button>
        </form>
    </div>
</body>
</html>
