<?php
declare(strict_types=1);
require __DIR__.'/core.php'; security_headers();header('Cache-Control: no-store'); start_session();
$config=require __DIR__.'/config.php';$error='';$done=false;
$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
$installed=store(fn($d)=>$d['admin']!==null);
$_SESSION['setup_csrf']??=bin2hex(random_bytes(24));
if($_SERVER['REQUEST_METHOD']==='POST' && !$installed){
 try {
  if(!secure_connection() && !$local)fail('Önce Hostinger SSL sertifikanızı etkinleştirin ve HTTPS üzerinden açın.');
  if(strlen($config['setup_key']??'')<24)fail('Dosya Yöneticisi ile config.php dosyasındaki setup_key alanına yalnızca sizin bildiğiniz en az 24 karakterlik bir kurulum anahtarı yazın.');
  if(!hash_equals($_SESSION['setup_csrf'],$_POST['csrf']??''))fail('Sayfayı yenileyip yeniden deneyin.',403);
  if(!extension_loaded('gd') || !extension_loaded('mbstring'))fail('PHP ayarlarından GD ve mbstring eklentilerini etkinleştirin.');
  $user=text($_POST['username']??null,100);$pw=password_text($_POST['password']??null);if(mb_strlen($pw)<12)fail('Şifre en az 12 karakter olmalı.');if($pw!==($_POST['confirm']??''))fail('Şifreler eşleşmiyor.');
  $result=store(function(array &$d)use($config,$user,$pw){
   if($d['admin'])fail('Kurulum zaten tamamlanmış.',409);
   $ip='setup-'.hash('sha256',$_SERVER['REMOTE_ADDR']??'');$now=time();$a=$d['attempts'][$ip]??['since'=>$now,'count'=>0];if($a['since']<$now-900)$a=['since'=>$now,'count'=>0];
   if($a['count']>=12)return 'Çok fazla deneme. 15 dakika sonra tekrar deneyin.';
   $a['count']++;$d['attempts'][$ip]=$a;
   if(!is_string($_POST['key']??null) || !hash_equals(hash('sha256',$config['setup_key']),hash('sha256',$_POST['key'])))return 'Kurulum anahtarı hatalı. Hostingde config.php dosyasına yazdığınız anahtarı kullanın.';
   $d['admin']=['username'=>$user,'password'=>password_hash($pw,PASSWORD_DEFAULT),'revision'=>bin2hex(random_bytes(16))];$d['attempts']=[];return '';
  },true);
  if($result!=='')fail($result);$installed=true;$done=true;session_regenerate_id(true);
 }catch(Throwable $ex){$error=$ex instanceof RuntimeException?$ex->getMessage():'Kurulum tamamlanamadı. PHP hata kayıtlarını kontrol edin.';error_log('Novax setup: '.$ex->getMessage());}
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Novax | İlk kurulum</title><link rel="stylesheet" href="/style.css"></head><body><main class="setup-card"><p class="eyebrow">NOVAX · İLK KURULUM</p><h1>Sitenizi açalım.</h1>
<?php if($installed): ?><p class="success">Kurulum tamamlandı. Yönetici hesabınız hazır.</p><p>Bu ekran yeni hesap oluşturamaz. İşletme bilgilerinizi ve fotoğraflarınızı yönetim panelinden düzenleyebilirsiniz.</p><a class="button" href="/admin.php">Yönetim paneline git ↗</a><p><a href="/">Siteyi görüntüle</a></p>
<?php else: ?><p>Hosting Dosya Yöneticisi ile <strong>config.php</strong> dosyasındaki boş <strong>setup_key</strong> alanına en az 24 karakterlik özel bir anahtar yazın. Buraya aynı anahtarı girin. Ardından yalnızca sizin bileceğiniz bir yönetici şifresi oluşturun.</p><?php if(!secure_connection()&&!$local): ?><p class="error">Kuruluma başlamadan önce hosting panelinden SSL’yi etkinleştirin ve bu sayfayı https:// ile açın.</p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['setup_csrf']) ?>"><label>Kurulum anahtarı<input name="key" required autocomplete="off" maxlength="100"></label><label>Kullanıcı adı<input name="username" required value="admin" maxlength="100" autocomplete="username"></label><label>Şifre (en az 12 karakter)<input name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label><label>Şifreyi tekrar yazın<input name="confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label><p class="error" role="alert"><?= e($error) ?></p><button class="button" type="submit">Hesabı oluştur ve kurulumu bitir ↗</button></form>
<?php endif; ?></main></body></html>
