<?php
header('Content-Type: application/json; charset=utf-8');

$root = __DIR__ . '/uploads';
if (!is_dir($root) && !mkdir($root, 0775, true)) {
    echo json_encode(['success' => false, 'error' => "mkdir $root failed"]);
    exit;
}

$required = ['graph_edges', 'graph_nodes', 'megList'];
$optional = ['gene_expression', 'methylation', 'snv', 'cnv', 'stage'];

foreach ($required as $key) {
    if (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => "$key file missing or error"]);
        exit;
}
}

$opt_ok = false;
foreach ($optional as $key) {
    if (!empty($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
        $opt_ok = true;
        break;
    }
}
if (!$opt_ok) {
    echo json_encode(['success' => false, 'error' => 'At least one optional file must be provided']);
    exit;
}

$sid        = md5(uniqid('', true));
$sessionDir = $root . '/' . $sid;

if (!mkdir($sessionDir, 0775, true)) {
    echo json_encode(['success' => false, 'error' => "mkdir session dir $sessionDir failed"]);
    exit;
}

$allKeys = array_merge($required, $optional);
$paths   = [];

foreach ($allKeys as $key) {
    if (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        continue;
    }

    $name   = basename($_FILES[$key]['name']);
    $target = $sessionDir . '/' . $name;

    if (!move_uploaded_file($_FILES[$key]['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'error' => "move $name failed"]);
        exit;
    }

    $paths[$key] = "uploads/$sid/$name";
}

echo json_encode([
    'success' => true,
    'sid'     => $sid,
    'paths'   => $paths
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
