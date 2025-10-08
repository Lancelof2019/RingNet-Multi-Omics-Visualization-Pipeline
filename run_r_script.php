<?php
// 新版：返回 JSON，便于前端直接拿到 viewerUrl
header('Content-Type: application/json; charset=utf-8');

$baseDir = realpath(__DIR__ . '/uploads');
if (!$baseDir) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'uploads directory missing']); exit;
}

// 1) 获取 sid
$sid = $_GET['sid'] ?? '';
if (!$sid || !preg_match('/^[A-Za-z0-9_-]+$/', $sid)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'invalid or missing sid']); exit;
}

// 会话目录
$sessionDir = $baseDir . '/' . $sid;
if (!is_dir($sessionDir)) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>"can not find directory of session : $sid"]); exit;
}

// 2) 扫描文件
$files = array_diff(scandir($sessionDir), ['.','..']);
if (!$files) {
  echo json_encode(['success'=>false,'error'=>"session $sid no files under the path"]); exit;
}

// 3) 关键字匹配规则（3 必选 + 4 选 1）
$patterns = [
  // 必选
  'edges' => ['edge','edges','graph_edges'],
  'nodes' => ['node','nodes','vertex','vertices','graph_nodes'],
  'memb'  => ['meglist','memb','membership','community','meg_list'],

  // 可选（至少一个）
  'expr'  => ['expr','expression','exp','gene_expression'],   // ← 加了 exp
  'meth'  => ['meth','methyl','methylation','mty'],           // ← 加了 mty
  'snv'   => ['snv'],
  'cnv'   => ['cnv'],
  'stage' => ['stage','stg']
];

// 扫描并按规则分类
$found = [];
foreach ($files as $f) {
  $low = strtolower($f);
  foreach ($patterns as $key => $keywords) {
    foreach ($keywords as $kw) {
      if (strpos($low, $kw) !== false) {
        // 只取第一匹配到的该类文件
        if (!isset($found[$key])) $found[$key] = $sessionDir . '/' . $f;
        break 2;
      }
    }
  }
}

// 检查 3 个必选
$required = ['edges','nodes','memb'];
$missingRequired = array_diff($required, array_keys($found));
if ($missingRequired) {
  echo json_encode(['success'=>false,'error'=>'missing file: '.implode(', ', $missingRequired)]); exit;
}

// 检查 4 选 1 至少有一个
$optional = ['expr','meth','snv','cnv','stage'];
$hasOptional = false;
foreach ($optional as $k) { if (isset($found[$k])) { $hasOptional = true; break; } }
if (!$hasOptional) {
  echo json_encode(['success'=>false,'error'=>'at least one item: expr / meth / snv / cnv / stage']); exit;
}

// 4) 组装 R 命令（注意：你的 script.R 目前会把 4 个都当必填）
// ★ 如果你还没按“可选”改 R 脚本，这里不要传空字符串，会导致 R 报错。
//   下面先“强制要求 4 个都传”，以兼容你当前的 script.R。
//   如果你已经把 R 改好了（允许缺失），把缺的设为 '' 再传即可。

$needAllFourForCurrentR = false; // ← 现在按“4选1”来跑
if ($needAllFourForCurrentR) {
  foreach ($optional as $k) {
    if (!isset($found[$k])) {
      echo json_encode(['success'=>false,'error'=>"provide $k file for R script"]); exit;
    }
  }
}

$cmd = escapeshellcmd('Rscript') . ' ' . escapeshellarg(__DIR__ . '/script.R');

// 固定顺序 7 个输入
foreach (['edges','nodes','memb','expr','meth','snv','cnv','stage'] as $argKey) {
  $cmd .= ' ' . escapeshellarg($found[$argKey] ?? '');
}

// 输出 JSON 路径（第 8 个参数）
$outJson = $sessionDir . '/community_map_top100.json';
$cmd .= ' ' . escapeshellarg($outJson);

// 5) 执行
$log = shell_exec($cmd . ' 2>&1');

// 6) 只做“存在/非空”校验（不再解析大 JSON）
if (!is_file($outJson) || filesize($outJson) === 0) {
  echo json_encode([
    'success'=>false,
    'error'=>'R did not create JSON or null，please check the logs',
    'r_log_head'=>mb_substr($log,0,2000)
  ]); exit;
}

// 7) 组装绝对 viewer URL（/cmt_figures 与你的部署一致）
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'vm3692.kaj.pouta.csc.fi';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // 一般是 /cmt_figures
$viewerUrl = $scheme.'://'.$host.$base.'/viewer.html?sid='.rawurlencode($sid);

// ★ 无向：要么先用测试页 nodir_test.html，要么直接走 viewer.html?mode=undirected
// 方案1（测试页）：
$viewerUrlNoDir = $scheme.'://'.$host.$base.'/nodir_viewer.html?sid='.rawurlencode($sid);

// 方案2（直接用同一 viewer，靠 mode 切换）：
// $viewerUrlNoDir = $scheme.'://'.$host.$base.'/viewer.html?sid='.rawurlencode($sid).'&mode=undirected';



// 成功
echo json_encode([
  'success'        => true,
  'viewerUrl'      => $viewerUrl,
  'viewerUrlNoDir' => $viewerUrlNoDir,  // ← 别忘了返回它
  'outJson'        => str_replace(__DIR__.'/', '', $outJson),
  'r_log_head'     => mb_substr($log, 0, 1000)
], JSON_UNESCAPED_UNICODE);
exit;
