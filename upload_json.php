<?php
// /var/www/html/cmt_figures/upload_json.php
header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_FILES['session_json']) || $_FILES['session_json']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No JSON file uploaded or upload error.');
    }

    // 读入文件内容，简单校验是不是合法 JSON
    $tmpPath = $_FILES['session_json']['tmp_name'];
    $raw     = file_get_contents($tmpPath);
    if ($raw === false) {
        throw new RuntimeException('Failed to read uploaded file.');
    }

    $decoded = json_decode($raw, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Uploaded file is not valid JSON: ' . json_last_error_msg());
    }

    // uploads 根目录
    $uploadRoot = __DIR__ . '/uploads';
    if (!is_dir($uploadRoot)) {
        if (!mkdir($uploadRoot, 0775, true)) {
            throw new RuntimeException('Failed to create uploads directory.');
        }
    }

    // 生成新的 session id （和 run_r_script 那套保持风格）
    $sid = md5(uniqid('', true));

    $sessionDir = $uploadRoot . '/' . $sid;
    if (!mkdir($sessionDir, 0775, true)) {
        throw new RuntimeException('Failed to create session directory: ' . $sid);
    }

    // 统一存成 community_map_top100.json
    $targetPath = $sessionDir . '/community_map_top100.json';
    if (file_put_contents($targetPath, $raw) === false) {
        throw new RuntimeException('Failed to save JSON file into session directory.');
    }

    // 根据你的现有路径规则返回 viewer 链接
    // 假设 viewer.html / nodir.html 和 upload_index.php 在同一目录（/cmt_figures）
    $baseUrl = dirname($_SERVER['SCRIPT_NAME']);       // 比如 /cmt_figures
    if ($baseUrl === DIRECTORY_SEPARATOR) {
        $baseUrl = '';
    }

    $viewerUrl   = $baseUrl . '/viewer.html?sid=' . urlencode($sid);
    $viewerNoDir = $baseUrl . '/nodir_test.html?sid='  . urlencode($sid);

    echo json_encode([
        'success'        => true,
        'sid'            => $sid,
        'path'           => 'uploads/' . $sid . '/community_map_top100.json',
        'viewerUrl'      => $viewerUrl,
        'viewerUrlNoDir' => $viewerNoDir,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

