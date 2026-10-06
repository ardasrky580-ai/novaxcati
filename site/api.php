<?php
declare(strict_types=1);
require __DIR__.'/core.php'; security_headers(); header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try {
 $path=$_GET['path']??''; if(!is_string($path))fail('İşlem bulunamadı.',404);
 if($path!=='content')start_session();
 $method=$_SERVER['REQUEST_METHOD']; if($method==='POST' && ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']??'')==='PUT')$method='PUT';
 $mutation=!in_array($method,['GET','HEAD'],true); $body=[];
 if($mutation && str_contains($_SERVER['CONTENT_TYPE']??'','application/json')){ if((int)($_SERVER['CONTENT_LENGTH']??0)>40000)fail('İstek çok büyük.',413); $raw=file_get_contents('php://input');$body=$raw===''?[]:json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($body))fail('Geçersiz istek.'); }else $body=$_POST;
 $response=store(function(array &$data) use($path,$method,$body,$mutation){
  if($path==='content' && $method==='GET')return public_content($data);
  if($path==='login' && $method==='POST'){
   require_secure_admin();
   if(!$data['admin'])fail('Önce /setup.php adresinden kurulumu tamamlayın.',503);
   $ip=hash('sha256',$_SERVER['REMOTE_ADDR']??''); $now=time();
   $data['attempts']=array_filter($data['attempts'],fn($a)=>$a['since']>$now-900); $attempt=$data['attempts'][$ip]??['since'=>$now,'count'=>0];
   if($attempt['count']>=12)return ['_error'=>'Çok fazla giriş denemesi. 15 dakika sonra tekrar deneyin.','_status'=>429];
   $attempt['count']++; $data['attempts'][$ip]=$attempt;
   if(!is_string($body['username']??null) || !is_string($body['password']??null) || strlen($body['password'])>72 || !password_verify($body['password'],$data['admin']['password']) || $body['username']!==$data['admin']['username'])return ['_error'=>'Kullanıcı adı veya şifre hatalı.','_status'=>401];
   unset($data['attempts'][$ip]);start_session();session_regenerate_id(true);$_SESSION=['admin'=>true,'revision'=>$data['admin']['revision'],'expires'=>$now+28800,'csrf'=>bin2hex(random_bytes(24))];return ['csrf'=>$_SESSION['csrf']];
  }
  if(!str_starts_with($path,'admin/'))fail('İşlem bulunamadı.',404);
  authenticated($data,$mutation);
  if($path==='admin/session' && $method==='GET')return ['csrf'=>$_SESSION['csrf'],'username'=>$data['admin']['username']];
  if($path==='admin/logout' && $method==='POST'){$_SESSION=[];session_destroy();return ['ok'=>true];}
  if($path==='admin/settings' && $method==='PUT'){ $data['settings']=validate_settings($body,$data['settings']);return ['ok'=>true]; }
  if($path==='admin/password' && $method==='PUT'){
   $pw=password_text($body['password']??null);if(mb_strlen($pw)<12)fail('Yeni şifre en az 12 karakter olmalı.');if(!password_verify(password_text($body['current']??null),$data['admin']['password']))fail('Mevcut şifre yanlış.');
   $data['admin']['password']=password_hash($pw,PASSWORD_DEFAULT);$data['admin']['revision']=bin2hex(random_bytes(16));$_SESSION=[];session_destroy();return ['ok'=>true];
  }
  if(preg_match('~^admin/media/(hero|logo)$~D',$path,$m) && $method==='POST'){
   $key=$m[1]==='hero'?'heroImage':'logoImage';$old=$data['settings'][$key];$image=upload_image();$data['settings'][$key]=$image; remove_image($old);return ['ok'=>true,'image'=>$image];
  }
  if(preg_match('~^admin/(projects|services)(?:/([a-zA-Z0-9-]{1,64}))?$~D',$path,$m)){
   $type=$m[1];$id=$m[2]??null;$index=null;foreach($data[$type] as $i=>$item)if($item['id']===$id)$index=$i;
   if($id && $index===null)fail('Kayıt bulunamadı.',404);
   if($id && $method==='DELETE'){ if($type==='projects')remove_image($data[$type][$index]['image']);array_splice($data[$type],$index,1);return ['ok'=>true]; }
   if(($id && $method==='PUT') || (!$id && $method==='POST')){
    if(!$id && count($data[$type])>=($type==='projects'?200:30))fail('Kayıt sınırına ulaşıldı.');
    $item=['id'=>$id??bin2hex(random_bytes(16)),'title'=>text($body['title']??null),'description'=>text($body['description']??null,$type==='projects'?5000:2000)];
    if($type==='projects'){
     $item['category']=text($body['category']??null,80); $old=$index!==null?$data[$type][$index]:null;
     $hasUpload=isset($_FILES['image']) && $_FILES['image']['error']!==UPLOAD_ERR_NO_FILE;
     $item['image']=$hasUpload?upload_image():($old['image']??'');if(!$item['image'])fail('Bir proje fotoğrafı seçin.');$item['demo']=$hasUpload?false:($old['demo']??false);
     if($old && $old['image']!==$item['image'])remove_image($old['image']);
    }else $item['available']=($body['available']??true)!==false;
    if($index!==null)$data[$type][$index]=$item;else $data[$type][]=$item;http_response_code($id?200:201);return ['id'=>$item['id']];
   }
  }
  fail('İşlem bulunamadı.',404);
 },$mutation);
 if(isset($response['_error'])){http_response_code($response['_status']);$response=['error'=>$response['_error']];}
 echo json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $ex){$code=$ex instanceof RuntimeException && $ex->getCode()>=400 && $ex->getCode()<=599?$ex->getCode():400;http_response_code($code);$message=$ex instanceof RuntimeException?$ex->getMessage():'İşlem tamamlanamadı. Hosting hata kayıtlarını kontrol edin.';error_log('Novax API: '.$ex->getMessage());echo json_encode(['error'=>$message],JSON_UNESCAPED_UNICODE);}
