<?php
header('Content-Type: application/json');

$dir = __DIR__ . '/uploads/';

function uuid() { return bin2hex(random_bytes(16)); }
if (!is_dir($dir) && !mkdir($dir,0755,true)) {
  echo json_encode(['success'=>false,'error'=>"mkdir $dir failed"]); exit;
}

/* Define field set */
$required = ['graph_edges','graph_nodes','megList'];   
//$optional = ['gene_expression','methylation','snv','cnv'];
$optional = ['gene_expression','methylation','snv','cnv','stage'];
/* ---------- Validate required files ---------- */
foreach ($required as $key) {
  if (empty($_FILES[$key]) || $_FILES[$key]['error']!==UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'error'=>"$key file missing or error"]); exit;
  }
}

/* ---------- At least one optional file required ---------- */
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

$allKeys = array_merge($required,$optional);
$paths   = [];

foreach ($allKeys as $key) {
  if (empty($_FILES[$key]) || $_FILES[$key]['error']!==UPLOAD_ERR_OK) continue;

  #$name = basename($_FILES[$key]['name']);
// To avoid filename conflicts, you can append a date or UUID here
  #$target = $dir . $name;
  
  $orig = basename($_FILES[$key]['name']);

  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
  if ($name === '') $name = $key . '.csv';

  $target = $sessionUploadDir . '/' . $name;  //  Save files into the session directory

  if (!move_uploaded_file($_FILES[$key]['tmp_name'],$target)) {
    echo json_encode(['success'=>false,'error'=>"move $name failed"]); exit;
  }
  $paths[] ='uploads/' . $sid . '/' . $name;
}

echo json_encode(['success'=>true,'sid'=>$sid,'paths'=>$paths]);

