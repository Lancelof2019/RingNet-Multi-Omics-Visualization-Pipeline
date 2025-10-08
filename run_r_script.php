<?php
// New version: return JSON to allow the front end to directly access viewerUrl
header('Content-Type: application/json; charset=utf-8');

$baseDir = realpath(__DIR__ . '/uploads');
if (!$baseDir) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'uploads directory missing']); exit;
}

// obtain sid
$sid = $_GET['sid'] ?? '';
if (!$sid || !preg_match('/^[A-Za-z0-9_-]+$/', $sid)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'invalid or missing sid']); exit;
}

// session directory
$sessionDir = $baseDir . '/' . $sid;
if (!is_dir($sessionDir)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>"can not find directory of session : $sid"]); exit;
}

// scan the files
$files = array_diff(scandir($sessionDir), ['.','..']);
if (!$files) {
  echo json_encode(['success'=>false,'error'=>"session $sid no files under the path"]); exit;
}

// key words matching
$patterns = [
  // mandantory
  'edges' => ['edge','edges','graph_edges'],
  'nodes' => ['node','nodes','vertex','vertices','graph_nodes'],
  'memb'  => ['meglist','memb','membership','community','meg_list'],

  // optional at least one option
  'expr'  => ['expr','expression','exp','gene_expression'],   
  'meth'  => ['meth','methyl','methylation','mty'],           
  'snv'   => ['snv'],
  'cnv'   => ['cnv'],
  'stage' => ['stage','stg']
];

// Scan and categorize uploaded files according to predefined rules.
$found = [];
foreach ($files as $f) {
  $low = strtolower($f);
  foreach ($patterns as $key => $keywords) {
    foreach ($keywords as $kw) {
      if (strpos($low, $kw) !== false) {
        // Only take the first matched file of each category.
        if (!isset($found[$key])) $found[$key] = $sessionDir . '/' . $f;
        break 2;
      }
    }
  }
}

// validate three mandantory option
$required = ['edges','nodes','memb'];
$missingRequired = array_diff($required, array_keys($found));
if ($missingRequired) {
  echo json_encode(['success'=>false,'error'=>'missing file: '.implode(', ', $missingRequired)]); exit;
}

// Check the “4 optional files” group — at least one must be provided.
$optional = ['expr','meth','snv','cnv','stage'];
$hasOptional = false;
foreach ($optional as $k) { if (isset($found[$k])) { $hasOptional = true; break; } }
if (!$hasOptional) {
  echo json_encode(['success'=>false,'error'=>'at least one item: expr / meth / snv / cnv / stage']); exit;
}

// 4) Assemble the R command
//  Note: your current script.R treats all 4 files as mandatory.
//  If your R script has NOT been modified to handle optional inputs,
//  do NOT pass empty strings here — it will cause an R runtime error.
//  For now, all 4 files are required to maintain compatibility.
//  Once the R script is updated (to allow missing files),
//  you can pass '' (empty string) for any missing ones.

$needAllFourForCurrentR = false; //currently run with “4 optional files, at least one required”
if ($needAllFourForCurrentR) {
  foreach ($optional as $k) {
    if (!isset($found[$k])) {
      echo json_encode(['success'=>false,'error'=>"provide $k file for R script"]); exit;
    }
  }
}

$cmd = escapeshellcmd('Rscript') . ' ' . escapeshellarg(__DIR__ . '/script.R');

// Fixed input order: 7 arguments total
foreach (['edges','nodes','memb','expr','meth','snv','cnv','stage'] as $argKey) {
  $cmd .= ' ' . escapeshellarg($found[$argKey] ?? '');
}

// Output JSON path = the 8th argument
$outJson = $sessionDir . '/community_map_top100.json';
$cmd .= ' ' . escapeshellarg($outJson);

// 5) Execute the R command
$log = shell_exec($cmd . ' 2>&1');

// 6) Perform only existence / non-empty validation
if (!is_file($outJson) || filesize($outJson) === 0) {
  echo json_encode([
    'success'=>false,
    'error'=>'R did not create JSON or null，please check the logs',
    'r_log_head'=>mb_substr($log,0,2000)
  ]); exit;
}

// 7) Construct absolute viewer URLs (consistent with your /cmt_figures deployment)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'vm3692.kaj.pouta.csc.fi';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // 一般是 /cmt_figures
$viewerUrl = $scheme.'://'.$host.$base.'/viewer.html?sid='.rawurlencode($sid);

//Undirected mode:
//    Either use the test page (nodir_test.html)
//    or call viewer.html?mode=undirected directly.
// Option 1: use test page：
$viewerUrlNoDir = $scheme.'://'.$host.$base.'/nodir_viewer.html?sid='.rawurlencode($sid);

// Option 2: use same viewer page, switched by “mode” parameter
// $viewerUrlNoDir = $scheme.'://'.$host.$base.'/viewer.html?sid='.rawurlencode($sid).'&mode=undirected';




echo json_encode([
  'success'        => true,
  'viewerUrl'      => $viewerUrl,
  'viewerUrlNoDir' => $viewerUrlNoDir,  
  'outJson'        => str_replace(__DIR__.'/', '', $outJson),
  'r_log_head'     => mb_substr($log, 0, 1000)
], JSON_UNESCAPED_UNICODE);
exit;
