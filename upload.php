<?php
header('Content-Type: application/json');

$dir = __DIR__ . '/uploads/';

function uuid() { return bin2hex(random_bytes(16)); }
if (!is_dir($dir) && !mkdir($dir,0755,true)) {
  echo json_encode(['success'=>false,'error'=>"mkdir $dir failed"]); exit;
}

/* 定义字段集 */
$required = ['graph_edges','graph_nodes','megList'];   // <-- 这里改了
//$optional = ['gene_expression','methylation','snv','cnv'];
$optional = ['gene_expression','methylation','snv','cnv','stage'];
/* ---------- 校验必选 ---------- */
foreach ($required as $key) {
  if (empty($_FILES[$key]) || $_FILES[$key]['error']!==UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'error'=>"$key file missing or error"]); exit;
  }
}

/* ---------- 至少一个可选 ---------- */
$opt_ok = false;
foreach ($optional as $key) {
  if (!empty($_FILES[$key]) && $_FILES[$key]['error']===UPLOAD_ERR_OK) {
    $opt_ok = true; break;
  }
}
if (!$opt_ok) {
  echo json_encode(['success'=>false,'error'=>'At least one optional file must be provided']); exit;
}
$sid = uuid();
#$sessionUploadDir = $dir . '/' . $sid;
$sessionUploadDir = $dir . $sid;
if (!mkdir($sessionUploadDir,0755,true)) {
  echo json_encode(['success'=>false,'error'=>"mkdir $sessionUploadDir failed"]); exit;
}
/* ---------- 统一搬文件 ---------- */
$allKeys = array_merge($required,$optional);
$paths   = [];

foreach ($allKeys as $key) {
  if (empty($_FILES[$key]) || $_FILES[$key]['error']!==UPLOAD_ERR_OK) continue;

  #$name = basename($_FILES[$key]['name']);
  // 如需避免重名，可在此加日期/UUID
  #$target = $dir . $name;
  
  $orig = basename($_FILES[$key]['name']);

  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
  if ($name === '') $name = $key . '.csv';

  $target = $sessionUploadDir . '/' . $name;  // ★ 存到会话目录

  if (!move_uploaded_file($_FILES[$key]['tmp_name'],$target)) {
    echo json_encode(['success'=>false,'error'=>"move $name failed"]); exit;
  }
  $paths[] ='uploads/' . $sid . '/' . $name;
}

echo json_encode(['success'=>true,'sid'=>$sid,'paths'=>$paths]);

