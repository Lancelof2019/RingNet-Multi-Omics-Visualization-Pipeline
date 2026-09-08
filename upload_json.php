<?php
// RingNet JSON / Snapshot uploader
// Drop-in replacement for: /var/www/html/cmt_figures/upload_json.php
// Supports:
//   1) canonical RingNet community array: [{comm,nodes,edges}, ...]
//   2) plain graph JSON: {nodes,edges} / {nodes,links}
//   3) object map of communities
//   4) RingNet Save JSON containing data:{nodes,edges}
//   5) RingNet snapshot JSON bundle
//   6) RingNet snapshot ZIP containing community_map_top100.json + snapshot_state.json
// Keeps source -> target direction unchanged.

header('Content-Type: application/json; charset=utf-8');

function ensure_dir($dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Failed to create directory: ' . $dir);
    }
}

function decode_json_or_fail($raw, $label = 'JSON') {
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
    $decoded = json_decode($raw, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException($label . ' is not valid JSON: ' . json_last_error_msg());
    }
    return $decoded;
}

function is_list_array_compat($arr) {
    if (!is_array($arr)) return false;
    $i = 0;
    foreach ($arr as $k => $_) {
        if ($k !== $i++) return false;
    }
    return true;
}

function first_present($arr, $keys, $default = null) {
    foreach ($keys as $k) {
        if (is_array($arr) && array_key_exists($k, $arr) && $arr[$k] !== null && $arr[$k] !== '') {
            return $arr[$k];
        }
    }
    return $default;
}

function endpoint_id($v) {
    if (is_array($v)) {
        foreach (['id','gene','name','label'] as $k) {
            if (array_key_exists($k, $v)) return endpoint_id($v[$k]);
        }
        if (isset($v[0])) return endpoint_id($v[0]);
        return '';
    }
    if ($v === null) return '';
    return (string)$v;
}

function normalize_node($node, $index) {
    if (!is_array($node)) {
        $id = (string)$node;
        if ($id === '') throw new RuntimeException("Node #{$index} is empty.");
        return ['id' => $id, 'name' => $id];
    }

    // Cytoscape style: {data:{id:...}}
    if (isset($node['data']) && is_array($node['data'])) {
        $node = array_merge($node, $node['data']);
        unset($node['data']);
    }

    $id = first_present($node, ['id','name','gene','key','label']);
    if ($id === null || $id === '') {
        throw new RuntimeException("Node #{$index} has no usable id/name/gene/key/label field.");
    }

    $node['id'] = (string)$id;
    if (!isset($node['name']) || $node['name'] === '') $node['name'] = (string)$id;
    return $node;
}

function normalize_edge($edge, $index) {
    if (!is_array($edge)) throw new RuntimeException("Edge #{$index} is not an object.");

    // Cytoscape style: {data:{source:...,target:...}}
    if (isset($edge['data']) && is_array($edge['data'])) {
        $edge = array_merge($edge, $edge['data']);
        unset($edge['data']);
    }

    $source = endpoint_id(first_present($edge, ['source','from','u','src']));
    $target = endpoint_id(first_present($edge, ['target','to','v','dst']));

    if ($source === '' || $target === '') {
        throw new RuntimeException("Edge #{$index} must contain source/target (or from/to, u/v, src/dst).");
    }

    // Never swap or merge these: direction is source -> target.
    $edge['source'] = $source;
    $edge['target'] = $target;

    if (!array_key_exists('w_raw', $edge)) {
        $candidate = first_present($edge, ['weight','pearson','pearson_r','corr','correlation','r']);
        if ($candidate !== null && $candidate !== '' && is_numeric($candidate)) {
            $edge['w_raw'] = (float)$candidate;
        }
    }

    if (!isset($edge['id']) || $edge['id'] === '') {
        $edge['id'] = 'e-' . $source . '-' . $target . '-' . $index;
    } else {
        $edge['id'] = (string)$edge['id'];
    }

    return $edge;
}

function looks_like_graph($obj) {
    if (!is_array($obj)) return false;
    $hasNodes = isset($obj['nodes']) || isset($obj['vertices']);
    $hasEdges = isset($obj['edges']) || isset($obj['links']);
    return $hasNodes && $hasEdges;
}

function normalize_graph($graph, $fallbackComm = 1) {
    if (!is_array($graph)) throw new RuntimeException('Graph entry is not a JSON object.');

    $nodes = $graph['nodes'] ?? ($graph['vertices'] ?? null);
    $edges = $graph['edges'] ?? ($graph['links'] ?? null);
    if (!is_array($nodes) || !is_array($edges)) {
        throw new RuntimeException('Each graph must contain arrays named nodes + edges (or vertices + links).');
    }

    $normalizedNodes = [];
    $nodeIds = [];
    foreach ($nodes as $i => $node) {
        $n = normalize_node($node, $i);
        if (isset($nodeIds[$n['id']])) throw new RuntimeException('Duplicate node id: ' . $n['id']);
        $nodeIds[$n['id']] = true;
        $normalizedNodes[] = $n;
    }

    $degree = array_fill_keys(array_keys($nodeIds), 0);
    $normalizedEdges = [];
    foreach ($edges as $i => $edge) {
        $e = normalize_edge($edge, $i);
        if (!isset($nodeIds[$e['source']])) {
            throw new RuntimeException("Edge #{$i} source '{$e['source']}' does not exist in nodes.");
        }
        if (!isset($nodeIds[$e['target']])) {
            throw new RuntimeException("Edge #{$i} target '{$e['target']}' does not exist in nodes.");
        }
        $degree[$e['source']]++;
        if ($e['target'] !== $e['source']) $degree[$e['target']]++;
        $normalizedEdges[] = $e;
    }

    foreach ($normalizedNodes as &$n) {
        if (!isset($n['degree']) || !is_numeric($n['degree'])) {
            $n['degree'] = $degree[$n['id']] ?? 0;
        }
    }
    unset($n);

    $out = $graph;
    unset($out['vertices'], $out['links']);
    $out['comm'] = (string)first_present($graph, ['comm','community','community_id','network','network_id'], $fallbackComm);
    $out['nodes'] = $normalizedNodes;
    $out['edges'] = $normalizedEdges;
    return $out;
}

function normalize_uploaded_json($decoded, &$detectedFormat, &$snapshotState) {
    $snapshotState = null;

    // Snapshot JSON bundle: {community_map_top100:[...], snapshot_state:{...}}
    if (is_array($decoded) && array_key_exists('community_map_top100', $decoded)) {
        $detectedFormat = 'ringnet_snapshot_bundle';
        if (isset($decoded['snapshot_state']) && is_array($decoded['snapshot_state'])) {
            $snapshotState = $decoded['snapshot_state'];
        } elseif (isset($decoded['state']) && is_array($decoded['state'])) {
            $snapshotState = $decoded['state'];
        }
        $innerFormat = 'unknown';
        $innerSnapshot = null;
        $graphs = normalize_uploaded_json($decoded['community_map_top100'], $innerFormat, $innerSnapshot);
        if ($snapshotState === null && is_array($innerSnapshot)) $snapshotState = $innerSnapshot;
        return $graphs;
    }

    // Old RingNet Save JSON session: { ..., data:{nodes,edges} }
    if (is_array($decoded) && isset($decoded['data']) && looks_like_graph($decoded['data'])) {
        $detectedFormat = 'ringnet_saved_session';
        $comm = first_present($decoded, ['community'], first_present($decoded['data'], ['comm'], 1));
        $graph = normalize_graph($decoded['data'], $comm);
        $snapshotState = [
            'schema' => 'ringnet.viewer.uploaded-session.v1',
            'ui' => $decoded,
            'viewer' => ['currentCommunity' => (string)$graph['comm']],
        ];
        if (isset($decoded['colors'])) $snapshotState['colors'] = $decoded['colors'];
        if (isset($decoded['omicsMetric'])) $snapshotState['omicsMetric'] = $decoded['omicsMetric'];
        if (isset($decoded['focusedNodeId'])) $snapshotState['focusedNodeId'] = $decoded['focusedNodeId'];
        return [$graph];
    }

    // Plain graph: {nodes,edges} or {nodes,links}
    if (looks_like_graph($decoded)) {
        $detectedFormat = 'single_graph';
        return [normalize_graph($decoded, 1)];
    }

    // Canonical list: [{comm,nodes,edges}, ...]
    if (is_list_array_compat($decoded)) {
        if (count($decoded) === 0) throw new RuntimeException('JSON array is empty.');
        $out = [];
        foreach ($decoded as $i => $item) {
            if (!looks_like_graph($item)) throw new RuntimeException("Array item #{$i} is not a graph with nodes + edges.");
            $out[] = normalize_graph($item, $i + 1);
        }
        $detectedFormat = 'community_array';
        return $out;
    }

    // Object map: {"1":{nodes,edges}, "2":{nodes,edges}}
    if (is_array($decoded)) {
        $out = [];
        foreach ($decoded as $key => $item) {
            if (!looks_like_graph($item)) { $out = []; break; }
            $out[] = normalize_graph($item, (string)$key);
        }
        if (!empty($out)) {
            $detectedFormat = 'community_object_map';
            return $out;
        }
    }

    throw new RuntimeException(
        'Valid JSON, but unsupported graph structure. Expected [{comm,nodes,edges}], {nodes,edges}, {nodes,links}, ' .
        'RingNet Save JSON with data:{nodes,edges}, or a RingNet snapshot bundle.'
    );
}

function zip_read_candidate($zipPath, $candidates) {
    // Preferred method: PHP ZipArchive.
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Failed to open uploaded snapshot ZIP.');
        }
        foreach ($candidates as $candidate) {
            $idx = $zip->locateName($candidate, ZipArchive::FL_NODIR);
            if ($idx !== false) {
                $entryName = $zip->getNameIndex($idx);
                $raw = $zip->getFromIndex($idx);
                $zip->close();
                return ['name' => $entryName, 'raw' => $raw];
            }
        }
        $zip->close();
        return null;
    }

    // Fallback for servers where php-zip is not enabled but the unzip command exists.
    // Nothing is extracted to disk; only matching entries are read with `unzip -p`.
    if (function_exists('shell_exec')) {
        $listCmd = 'unzip -Z1 ' . escapeshellarg($zipPath) . ' 2>/dev/null';
        $listing = shell_exec($listCmd);
        if (is_string($listing) && trim($listing) !== '') {
            $entries = preg_split('/\r?\n/', trim($listing));
            foreach ($candidates as $candidate) {
                foreach ($entries as $entry) {
                    if (basename($entry) === $candidate) {
                        $readCmd = 'unzip -p ' . escapeshellarg($zipPath) . ' ' . escapeshellarg($entry) . ' 2>/dev/null';
                        $raw = shell_exec($readCmd);
                        if ($raw === null) {
                            throw new RuntimeException('Failed to read ' . $candidate . ' from snapshot ZIP.');
                        }
                        return ['name' => $entry, 'raw' => $raw];
                    }
                }
            }
            return null;
        }
    }

    throw new RuntimeException('Cannot read snapshot ZIP: enable PHP ZipArchive (php-zip) or the server unzip command.');
}

function graph_mode_from_state($state) {
    if (!is_array($state)) return 'unknown';
    $mode = strtolower((string)(
        $state['graphMode']
        ?? $state['viewerKind']
        ?? ($state['viewer']['graphMode'] ?? '')
        ?? ($state['viewer']['preferredViewer'] ?? '')
    ));
    if (strpos($mode, 'nodir') !== false || strpos($mode, 'undir') !== false) return 'undirected';
    if (strpos($mode, 'direct') !== false && strpos($mode, 'undir') === false) return 'directed';
    return 'unknown';
}

function write_canonical_graphs($sessionDir, $communities) {
    if (!is_array($communities) || count($communities) === 0) {
        throw new RuntimeException('No graph/network was found in the uploaded file.');
    }
    $json = json_encode($communities, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    if ($json === false) throw new RuntimeException('Failed to encode normalized graph JSON: ' . json_last_error_msg());
    $path = $sessionDir . '/community_map_top100.json';
    if (file_put_contents($path, $json) === false) throw new RuntimeException('Failed to save community_map_top100.json.');
}

try {
    if (empty($_FILES['session_json']) || $_FILES['session_json']['error'] !== UPLOAD_ERR_OK) {
        $code = $_FILES['session_json']['error'] ?? 'missing';
        throw new RuntimeException('No JSON/snapshot ZIP file uploaded or upload error. PHP upload code: ' . $code);
    }

    $tmpPath  = $_FILES['session_json']['tmp_name'];
    $origName = $_FILES['session_json']['name'] ?? 'uploaded_file';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['json','zip'], true)) {
        throw new RuntimeException('Unsupported file type. Please upload .json or .zip.');
    }

    $uploadRoot = __DIR__ . '/uploads';
    ensure_dir($uploadRoot);

    $sid = md5(uniqid('', true));
    $sessionDir = $uploadRoot . '/' . $sid;
    ensure_dir($sessionDir);

    $isSnapshot = false;
    $graphMode = 'unknown';
    $detectedFormat = 'unknown';
    $snapshotState = null;
    $communities = null;

    if ($ext === 'zip') {
        $communityFile = zip_read_candidate($tmpPath, ['community_map_top100.json','community_map.json','graph_data.json']);
        if ($communityFile === null || $communityFile['raw'] === false) {
            throw new RuntimeException('Snapshot ZIP must contain community_map_top100.json.');
        }

        $communityDecoded = decode_json_or_fail($communityFile['raw'], 'community_map_top100.json');
        $innerSnapshot = null;
        $communities = normalize_uploaded_json($communityDecoded, $detectedFormat, $innerSnapshot);
        write_canonical_graphs($sessionDir, $communities);

        $stateFile = zip_read_candidate($tmpPath, ['snapshot_state.json','session_state.json','state.json']);
        if ($stateFile !== null && $stateFile['raw'] !== false) {
            $snapshotState = decode_json_or_fail($stateFile['raw'], 'snapshot_state.json');
            $graphMode = graph_mode_from_state($snapshotState);
            $stateJson = json_encode(
                $snapshotState,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
            );
            if ($stateJson === false || file_put_contents($sessionDir . '/snapshot_state.json', $stateJson) === false) {
                throw new RuntimeException('Failed to save snapshot_state.json.');
            }
            $isSnapshot = true;
        }

        $detectedFormat = 'snapshot_zip/' . $detectedFormat;
    } else {
        $raw = file_get_contents($tmpPath);
        if ($raw === false) throw new RuntimeException('Failed to read uploaded JSON file.');

        // Keep original JSON for debugging; viewer does not read it.
        file_put_contents($sessionDir . '/original_uploaded.json', $raw);

        $decoded = decode_json_or_fail($raw, 'Uploaded file');
        $communities = normalize_uploaded_json($decoded, $detectedFormat, $snapshotState);
        write_canonical_graphs($sessionDir, $communities);

        if (is_array($snapshotState)) {
            $graphMode = graph_mode_from_state($snapshotState);
            $snapshotJson = json_encode($snapshotState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            if ($snapshotJson !== false && file_put_contents($sessionDir . '/snapshot_state.json', $snapshotJson) !== false) {
                $isSnapshot = true;
            }
        }
    }

    $networkCount = is_array($communities) ? count($communities) : 0;
    $nodeCount = 0;
    $edgeCount = 0;
    foreach (($communities ?? []) as $g) {
        if (isset($g['nodes']) && is_array($g['nodes'])) $nodeCount += count($g['nodes']);
        if (isset($g['edges']) && is_array($g['edges'])) $edgeCount += count($g['edges']);
    }

    $baseUrl = dirname($_SERVER['SCRIPT_NAME']);
    if ($baseUrl === DIRECTORY_SEPARATOR || $baseUrl === '.') $baseUrl = '';

    $snapshotQuery = $isSnapshot ? '&snapshot=1' : '';
    $directedUrl   = $baseUrl . '/viewer.html?sid=' . urlencode($sid) . $snapshotQuery;
    $undirectedUrl = $baseUrl . '/nodir_test.html?sid=' . urlencode($sid) . $snapshotQuery;

    // Primary link follows snapshot graph mode, but both links are always returned.
    if ($graphMode === 'undirected') {
        $viewerUrl = $undirectedUrl;
    } else {
        $viewerUrl = $directedUrl;
    }

    echo json_encode([
        'success'           => true,
        'sid'               => $sid,
        'isSnapshot'        => $isSnapshot,
        'graphMode'         => $graphMode,
        'detectedFormat'    => $detectedFormat,
        'networkCount'      => $networkCount,
        'nodeCount'         => $nodeCount,
        'edgeCount'         => $edgeCount,
        'directed'          => true,
        'path'              => 'uploads/' . $sid . '/community_map_top100.json',
        'snapshotPath'      => $isSnapshot ? 'uploads/' . $sid . '/snapshot_state.json' : null,
        'viewerUrl'         => $viewerUrl,
        'viewerUrlDirected' => $directedUrl,
        'viewerUrlNoDir'    => $undirectedUrl,
        'message'           => 'RingNet upload accepted. JSON was normalized without removing source->target direction.'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
