<?php
$GAS_URL = 'https://script.google.com/macros/s/AKfycbzGiDx6Nuqi5AoOBjbgeMxDC9mEv5hD5jVa7OwYvQDyC8vRjRLgop6bBddQ_PrOMoADjg/exec';
$post_data = ['name' => 'test_name', 'email' => 'test@test.com'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $GAS_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response: \n" . $response;
?>
