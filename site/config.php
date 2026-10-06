<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
// Yüklemeden önce boş tırnakların arasına kendinize özel en az 24 karakterlik kurulum anahtarı yazın.
// Bu anahtarı GitHub'a yüklemeyin. Yalnızca hostingdeki kopyada değiştirin.
return ['setup_key'=>''];
