<?php
declare(strict_types=1);
ini_set('display_errors','0');
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
const STORE_PREFIX = "<?php exit; ?>\n";
const STORAGE = __DIR__ . '/storage';
function security_headers(): void {
 header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin');
 header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' blob: data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
 header('X-Frame-Options: DENY');
}
function secure_connection(): bool { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == '443'; }
function start_session(): void {
 if (session_status() === PHP_SESSION_ACTIVE) return;
 ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
 session_name('novax_session'); session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>secure_connection(),'httponly'=>true,'samesite'=>'Strict']); session_start();
}
function fail(string $message, int $status=400): never { throw new RuntimeException($message,$status); }
function default_data(): array {
 return ['admin'=>null,'attempts'=>[],'settings'=>[
 'brand'=>'Novax Çatı Sistemleri','phone'=>'05060921806','whatsapp'=>'905060921806','area'=>'Hizmet bölgesi ve keşif için bize ulaşın.','address'=>'','hours'=>'Çalışma saatleri için bize ulaşın.',
 'mapUrl'=>'https://www.google.com/maps/search/?api=1&query=Novax+%C3%87at%C4%B1+Sistemleri+39.7467374,32.5710355',
 'about'=>'Her çatının ihtiyacı farklıdır. Novax Çatı Sistemleri olarak mevcut çatınızı, beklentilerinizi ve kullanılacak malzemeleri birlikte değerlendiriyoruz. İşin kapsamını konuşalım; yapınıza uygun çözümü birlikte belirleyelim.',
 'heroTitle'=>'Çatınız sağlam. İçiniz rahat.','heroText'=>'Yeni bir çatı, yenilenen bir yaşam alanı. Çatı sistemleri için ihtiyacınızı birlikte değerlendirelim, yapınıza uygun çözümü planlayalım.',
 'seoTitle'=>'Novax Çatı Sistemleri | Çatı çözümleri','seoDescription'=>'Novax Çatı Sistemleri: çatı yapımı, yenileme, onarım ve yalıtım. İhtiyaçlarınızı birlikte değerlendirelim.',
 'logoText'=>'NOVAX','logoSubtitle'=>'ÇATI SİSTEMLERİ','topline'=>'Yapınıza değer katan çatı çözümleri.','footerText'=>'Sağlam çatılar. Huzurlu yaşamlar.',
 'servicesTitle'=>'Her çatıya, doğru çözüm.','projectsTitle'=>'Birlikte hayal edelim. Detayları konuşalım.','aboutTitle'=>'Çatınıza özen, yaşamınıza değer.','contactTitle'=>'Çatınız için konuşmaya değer.','contactText'=>'Aklınızdaki soruları, çatı fotoğraflarınızı ya da projenizi paylaşın. Birlikte değerlendirelim.',
 'heroImage'=>'/assets/roof.svg','logoImage'=>''],
 'services'=>array_map(fn($i)=>['id'=>'s'.($i+1),'title'=>['Çatı yapımı & yenileme','Çatı onarımı','Isı & su yalıtımı','Oluk & yağmur sistemleri'][$i], 'description'=>['Yeni çatı uygulaması ve mevcut çatının yenilenmesi hakkında bilgi alın. Yapınıza uygun kapsam keşif sırasında netleştirilir.','Hasarlı kaplama, bağlantı ve detayların değerlendirilmesi için bizimle iletişime geçin.','Çatınızın yalıtım ihtiyacını ve uygulanabilecek malzeme seçeneklerini birlikte değerlendirelim.','Yağmur suyunun yönlendirilmesi, oluk ve iniş detayları için çözüm seçeneklerini konuşalım.'][$i],'available'=>true],range(0,3)),
 'projects'=>array_map(fn($i)=>['id'=>'p'.$i,'title'=>['Kiremit çatının zamansız çizgisi','Modern yapılara net bir dokunuş','Her detayda bütünlük'][$i-1],'category'=>['Çatı sistemleri','Metal çatı','Çatı detayları'][$i-1],'description'=>'Çatı görünümünü anlatan temsili çalışma. Tamamlanmış müşteri projesi değildir.','image'=>'/assets/project-'.$i.'.svg','demo'=>true],range(1,3))];
}
// The separate lock file survives atomic replacement of the protected data file.
function store(callable $fn, bool $write=false): mixed {
 $lock=fopen(STORAGE.'/lock.php','c+'); if (!$lock || !flock($lock,$write?LOCK_EX:LOCK_SH)) fail('Veri klasörü yazılabilir değil. Hosting dosya izinlerini kontrol edin.',503);
 try {
  $file=STORAGE.'/data.php';
  $data=is_file($file)?json_decode(substr((string)file_get_contents($file),strlen(STORE_PREFIX)),true,512,JSON_THROW_ON_ERROR):default_data();
  $result=$fn($data);
  if ($write) { $tmp=STORAGE.'/'.bin2hex(random_bytes(12)).'.php'; $bytes=STORE_PREFIX.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); if(file_put_contents($tmp,$bytes)!==strlen($bytes)) { @unlink($tmp); fail('Kayıt yapılamadı; disk alanını kontrol edin.',503); } chmod($tmp,0600); if(!rename($tmp,$file)) { @unlink($tmp); fail('Kayıt yapılamadı.',503); } foreach($GLOBALS['image_deletions']??[] as $oldPath)@unlink(__DIR__.$oldPath);$GLOBALS['image_deletions']=[]; }
  return $result;
 } finally { flock($lock,LOCK_UN); fclose($lock); }
}
function public_content(array $data): array { return ['settings'=>$data['settings'],'services'=>$data['services'],'projects'=>$data['projects']]; }
function text(mixed $v,int $max=120,bool $required=true): string {
 if(!is_string($v)) { if($required)fail('Gerekli alanları doldurun.'); return ''; }
 $v=trim($v); if(mb_strlen($v)>$max || ($required && $v===''))fail('Alan boş veya izin verilen uzunluğu aşıyor.'); return $v;
}
function require_secure_admin(): void {
 if(!secure_connection() && !in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true))fail('Yönetim için HTTPS kullanın. Hosting SSL sertifikasını etkinleştirin.',403);
}
function password_text(mixed $v): string {
 if(!is_string($v) || $v==='' || strlen($v)>72)fail('Şifre en fazla 72 bayt olabilir.');return $v;
}
function authenticated(array $data,bool $mutation=false): void {
 require_secure_admin();start_session(); $admin=$data['admin'];
 if(!$admin || !isset($_SESSION['admin']) || ($_SESSION['revision']??null)!==$admin['revision'] || ($_SESSION['expires']??0)<time())fail('Oturum açmanız gerekiyor.',401);
 if($mutation && !hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??''))fail('Güvenlik doğrulaması başarısız. Sayfayı yenileyin.',403);
}
function validate_settings(array $input,array $old): array {
 $limits=['brand'=>100,'phone'=>24,'whatsapp'=>24,'area'=>250,'address'=>500,'hours'=>250,'mapUrl'=>1000,'about'=>5000,'heroTitle'=>100,'heroText'=>500,'seoTitle'=>160,'seoDescription'=>320,'logoText'=>24,'logoSubtitle'=>60,'topline'=>200,'footerText'=>200,'servicesTitle'=>160,'projectsTitle'=>160,'aboutTitle'=>160,'contactTitle'=>160,'contactText'=>500];
 $out=$old; foreach($limits as $key=>$max) $out[$key]=text($input[$key]??($old[$key]??''),$max,in_array($key,['brand','phone','whatsapp','heroTitle','heroText','seoTitle','logoText'],true));
 foreach(['phone','whatsapp'] as $k)if(!preg_match('/^\+?[\d\s()-]{10,24}$/D',$out[$k]))fail('Geçerli telefon ve WhatsApp numarası girin.');
 if($out['mapUrl']!==''){ $u=parse_url($out['mapUrl']); if(!$u || ($u['scheme']??'')!=='https' || !in_array(strtolower($u['host']??''),['google.com','www.google.com','maps.google.com','maps.app.goo.gl'],true) || isset($u['user']) || isset($u['pass']))fail('HTTPS Google Haritalar bağlantısı kullanın.'); }
 return $out;
}
function upload_image(): string {
 $f=$_FILES['image']??null; if(!$f || $f['error']!==UPLOAD_ERR_OK || $f['size']>8*1024*1024)fail('JPG, PNG veya WebP fotoğraf seçin (en fazla 8 MB).');
 if(!extension_loaded('gd'))fail('Hosting PHP ayarlarından GD eklentisini etkinleştirin.',503);
 $info=@getimagesize($f['tmp_name']);
 if(!$info || !in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true) || $info[0]*$info[1]>20000000)fail('Geçerli JPG, PNG veya WebP yükleyin (en fazla 20 megapiksel).');
 $raw=@file_get_contents($f['tmp_name']); $img=@imagecreatefromstring($raw); if(!$img)fail('Fotoğraf okunamadı.');
 $scale=min(1,1800/$info[0],1800/$info[1]); $w=max(1,(int)round($info[0]*$scale));$h=max(1,(int)round($info[1]*$scale));
 $out=imagecreatetruecolor($w,$h); imagealphablending($out,false); imagesavealpha($out,true); imagecopyresampled($out,$img,0,0,0,0,$w,$h,$info[0],$info[1]);
 $name=bin2hex(random_bytes(16)).'.png'; $path=__DIR__.'/uploads/'.$name;
 if(!imagepng($out,$path,6))fail('Fotoğraf kaydedilemedi. uploads klasörünün izinlerini kontrol edin.',503);
 imagedestroy($img);imagedestroy($out); return '/uploads/'.$name;
}
function remove_image(string $path): void { if(preg_match('~^/uploads/[a-f0-9]{32}\.png$~D',$path))$GLOBALS['image_deletions'][]=$path; }
function e(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
