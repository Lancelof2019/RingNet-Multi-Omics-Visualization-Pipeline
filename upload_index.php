<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="UTF-8">
  <title>Upload 6 CSV Files</title>
  <style>
:root{
    --label-w: 280px;
    --gap: 12px;
  }

 body{
   font-family: "Segoe UI", Arial, Helvetica, sans-serif;
   margin: 2rem;

   background-image: none;     /* ← 关键：去掉水印 */
   background-color: #f5f6f8;  /* 可选：更像科研工具 */

   position: relative;
 }

 body::before{
  content: none;
 }

  h2 {
    color: #333;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }

  label.file-label{
    display:flex;
    align-items:center;
    gap:var(--gap);
    margin:10px 0;
    color:#444;
    background:rgba(255,255,255,0.8);
    padding:8px 12px;
    border-radius:10px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
  }
  .file-input-placeholder{
   width: 255px;
   flex: 0 0 255px;
  }

   /* tutorial 行：外观与 file-label 一致 */
  .tutorial-row{
    display:flex;
    align-items:center;
    gap:var(--gap);
    margin:10px 0;
    color:#444;
    background:rgba(255,255,255,0.8);
    padding:8px 12px;
    border-radius:10px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
  }

  .tutorial-row .title{
    flex:0 0 var(--label-w);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    font-weight:500;
  }

  .tutorial-row .desc{
    flex: 1 1 auto;
    font-size: 0.92rem;
    color:#555;
    line-height: 1.3;
  }

  label.file-label span{
    flex:0 0 var(--label-w);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    font-weight:500;
  }

  .required span::after{
    content:" *";
    color:#d00;
    margin-left:4px;
  }

  .err{ color:#d00 }
  .ok{ color:#070 }
  .spinner{
    display:inline-block; width:1em; height:1em;
    border:2px solid #ccc; border-top-color:#333;
    border-radius:50%; animation:spin .8s linear infinite;
  }
  @keyframes spin{ to{ transform:rotate(360deg) } }

  /* 两个表单的按钮共用一套样式 */
  #uploadForm > button[type="submit"],
  #jsonForm   > button[type="submit"]{
    margin-top:10px;
    padding:8px 20px;
    background:#4285f4;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
    transition:background 0.2s;
  }
  #uploadForm > button[type="submit"]:hover,
  #jsonForm   > button[type="submit"]:hover{
    background:#2f6fe0;
  }

  /* Template_download 链接样式 */
  .template-link{
    font-size: 0.88em;
    color: #0066cc;
    text-decoration: none;
    background: rgba(0,102,204,0.08);
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid rgba(0,102,204,0.2);
    transition: all 0.2s;
  }
  .hint{
   margin: 0 0 10px 0;
   color:#555;
   line-height: 1.35;
  }

 .hint code{
   font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
               "Liberation Mono", "Courier New", monospace;
   font-size: 0.95em;
   background: rgba(0,0,0,0.06);
   border: 1px solid rgba(0,0,0,0.08);
   padding: 2px 6px;
   border-radius: 6px;
  }
  .template-link:hover{
    background: rgba(0,102,204,0.18);
    color: #004080;
    text-decoration: underline;
  }

  .main-title {
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    font-weight: 700;
    font-size: 2rem;
    color: #111;
    text-align: center;
    margin-bottom: 1rem;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }

  /* 输出区域换成 div 的样式 */
  #output {
    margin-top: 10px;
    white-space: pre-wrap;
    font-family: "Segoe UI", Arial, sans-serif;
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
  }

  /* 结果链接区域 */
  .result-links{
    margin-top:6px;
    text-align:left;
  }
  .result-links a{
    display:inline-block;
    margin-right:10px;
    padding:4px 10px;
    border-radius:6px;
    border:1px solid rgba(0,0,0,0.15);
    background:rgba(255,255,255,0.8);
    font-size:0.9em;
    text-decoration:none;
    color:#0066cc;
  }
  .result-links a:hover{
    background:rgba(0,102,204,0.08);
    color:#004080;
  }
  /* 顶部品牌栏：左 RingNet，右 Tampere */
  .header-bar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom: 1rem;
  }

  /* 左侧 RingNet */
  .brand-left{
    display:flex;
    align-items:center;
  }

  .ringnet-logo{
    height: 140px;
    width: auto;
    display:block;
  }

  /* 右侧 Tampere */
  .brand-right{
    display:flex;
    align-items:center;
    justify-content:flex-end;
  }

  .tampere-logo{
    height: 100px;      /* 比左侧更小，符合“背书”层级 */
    width: auto;
    display:block;
    opacity: 0.8;     /* 轻一点，不抢主体 */
  }


 .title-svg{
  width: 25%;
  max-width: 170px;
  height: auto;
  display: block;
  }
  .tutorial-row{
  display: flex;
  align-items: center;
 }

.tutorial-title{
  font-weight: 600;
  margin-right: 6px;
 }

.tutorial-hint{
  font-size: 12px;
  color: #888;
  margin-right: 12px;
  white-space: nowrap;
 }

.file-input-placeholder{
  flex: 1;
 }
.fake-file-input{
  display: inline-block;
  width: 220px;   /* 和 input[type=file] 实际宽度接近 */
 }
  </style>
</head>
<body>
<header class="header-bar">
  <div class="brand-left">
    <img src="./picture_logo/ringnet2.svg" alt="RingNet" class="ringnet-logo">
  </div>

  <div class="brand-right">
    <img src="./pictures_logo/logo-en-purple-small.png" alt="Tampere University" class="tampere-logo">
  </div>
</header>

<h2>Upload CSV Files</h2>
<p class="hint">
  The <code>General template</code> can be used when the data are <code>gene - gene interaction</code> or <code>patient - patient similarity</code> network.  Please use the <code>Single Cell template</code> for single cell data.
</p>

<form id="uploadForm" enctype="multipart/form-data" method="POST" action="/cmt_figures/upload.php">
  <!-- 两个必选 -->
  <label class="file-label">
   <span>
    Tutorial
    <small class="tutorial-hint">
      Please follow the column name of template
    </small>
   </span>
   <span class="fake-file-input"></span>
   <a href="docs/gene_tutorial.docx"
     download
     class="template-link">
     Gene tutorial
   </a>
    <a href="docs/patient_tutorial.docx"
     download
     class="template-link">
     Patient tutorial
   </a>

   <a href="docs/singlecell_tutorial.docx"
     download
     class="template-link"
     style="margin-left:15px;">
     Single cell tutorial
   </a>
  </label>
  <label class="file-label required"><span>Graph Edges (.csv)</span>
    <input type="file" name="graph_edges" accept=".csv" required>
    <a href="examples/general_graph_edges.csv" download class="template-link">General network template</a>
    <a href="examples/example_graph_edges.csv" download class="template-link">Gene network template</a>
    <a href="examples/patients_example_edges.csv" download class="template-link">Patient network template</a>
    <a href="examples/singlecell_edges.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
  </label>
  <label class="file-label required"><span>Graph Nodes (.csv)</span>
    <input type="file" name="graph_nodes" accept=".csv" required>
    <a href="examples/general_graph_nodes.csv" download class="template-link">General network template</a>
    <a href="examples/example_graph_nodes.csv" download class="template-link">Gene network template</a>
    <a href="examples/patients_example_nodes.csv" download class="template-link">Patient network template</a>
    <a href="examples/singlecell_nodes.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
  </label>
  <label class="file-label required">
  <span>Node Group (.csv)</span>
  <input type="file" name="megList" accept=".csv">
  <a href="examples/general_megList.csv" download class="template-link">General network template</a>
  <a href="examples/example_megList.csv" download class="template-link">Gene network template</a>
  <a href="examples/patients_example_megList.csv" download class="template-link">Patient network template</a>
  <a href="examples/singlecell_megList.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
 </label>
<!-- 四选一 -->
  <label class="file-label"><span>Data1 (continuous real value)</span>
    <input type="file" name="gene_expression" accept=".csv,.tsv">
    <a href="examples/general_gene_expression.csv" download class="template-link">General network template</a>
    <a href="examples/example_gene_expression.csv" download class="template-link">Gene network template</a>
    <a href="examples/patients_example_expression.csv" download class="template-link">Patient network template</a>
    <a href="examples/singlecell_expression_matrix.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
  </label>
  <label class="file-label"><span>Data2 (continuous real value)</span>
    <input type="file" name="methylation" accept=".csv,.tsv">
    <a href="examples/general_methylation.csv" download class="template-link">General network template</a>
    <a href="examples/example_methylation.csv" download class="template-link">Gene network template</a>
    <a href="examples/patients_example_methylation.csv" download class="template-link">Patient network template</a>
    <a href="examples/singlecell_expression_matrix.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
  </label>
  <label class="file-label"><span>Data3 (integer value)</span>
    <input type="file" name="snv" accept=".csv,.tsv">
    <a href="examples/general_cnv.csv" download class="template-link">General network template</a>
    <a href="examples/example_cnv.csv" download class="template-link">Gene network template</a>
    <a href="examples/patients_example_cnv.csv" download class="template-link">Patient network template</a>
    <a href="examples/singlecell_cnv.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
  </label>
  <label class="file-label"><span>Data4 (integer value)</span>
    <input type="file" name="cnv" accept=".csv,.tsv">
    <a href="examples/general_snv.csv" download class="template-link">General network template</a>
    <a href="examples/example_snv.csv" download class="template-link">Gene network template</a>
    <a href="examples/patients_example_snv.csv" download class="template-link">Patient network template</a>
    <a href="examples/singlecell_snv.csv" download class="template-link" style="margin-left:10px;">Single cell network template</a>
  </label>
  <label class="file-label"><span>Sample Group</span>
    <input type="file" name="stage" accept=".csv,.tsv">
    <a href="examples/example_stage.csv" download class="template-link">General network template</a>
    <a href="examples/example_stage.csv" download class="template-link">Gene network template</a>
  </label>
  <button type="submit">Upload&nbsp;Files</button>
</form>

<div id="runContainer" style="display:none;margin-top:20px">
  <h3>Run R Script</h3>
  <button id="runBtn">Run&nbsp;R&nbsp;Script</button>
</div>

<h2 style="margin-top:2rem;">Or upload an existing JSON session</h2>
<form id="jsonForm" enctype="multipart/form-data" method="POST" action="/cmt_figures/upload_json.php">
  <label class="file-label required">
    <span>Session JSON (community_map_top100.json)</span>
    <input type="file" name="session_json" accept=".json" required>
    <a href="examples/community_map_top100.json"
       download
       class="template-link">General Json</a>
    <a href="examples/gene.json"
       download
       class="template-link">Gene Json</a>
    <a href="examples/patient.json"
       download
       class="template-link">Patient Json</a>
    <a href="examples/singlecell.json"
       download
       class="template-link">Single cell Json</a>
  </label>
  <button type="submit">Upload&nbsp;JSON&nbsp;Session</button>
</form>

<div id="output"></div>

<script>
const form   = document.getElementById('uploadForm');
const output = document.getElementById('output');
const runBox = document.getElementById('runContainer');
const runBtn = document.getElementById('runBtn');

let uploadedPaths = [];
let currentSid = null;
const reqThree = ['graph_edges','graph_nodes'];
const optFive  = ['gene_expression','methylation','snv','cnv','stage'];

/* 文件名回显 */
form.querySelectorAll('input[type=file]').forEach(inp=>{
  inp.addEventListener('change',()=>{
    const span = inp.closest('label').querySelector('span');
    span.textContent = inp.files.length ? inp.files[0].name : inp.name.replace('_',' ');
  });
});

form.addEventListener('submit',async (e)=>{
  e.preventDefault();
  runBox.style.display='none';
  uploadedPaths = [];
  currentSid = null;
  output.textContent = '';

  const btn = form.querySelector('button');
  btn.disabled = true;

  /* 校验 */
  for (const k of reqThree) {
    if (!form.elements[k] || !form.elements[k].files.length) {
      return showErr(`${k} required`, btn);
    }
  }
  if (!optFive.some(k=>form.elements[k].files.length)){
    showErr('One of gene expression / methylation / snv / cnv / stage is required', btn);
    return;
  }
  const fd = new FormData(form);
  const hasMegList = form.elements['megList'] && form.elements['megList'].files.length > 0;
  if (!hasMegList) {
  try {
    const nodesFile = form.elements['graph_nodes'].files[0];
    const megBlob = await buildDefaultMegListFromNodes(nodesFile);
    fd.append('megList', megBlob, 'megList_default_community1.csv');

    // optional: show a small note
    output.textContent = "ℹ Node Group not provided — generated a default megList (community=1).\n";
  } catch (err) {
    showErr(`Failed to build default megList: ${err}`, btn);
    return;
  }
}

   fetch('/cmt_figures/upload.php', {
         method: 'POST',
         body:fd
    })
    .then(async r => {
      const text = await r.text();
      try { return JSON.parse(text); }
      catch {
        throw new Error(`Server did not return JSON.\n--- Raw response ---\n${text}`);
      }
    })
    .then(js=>{
      btn.disabled = false;
      if(js.success){
        uploadedPaths = js.paths;
        currentSid = js.sid;
        output.innerHTML = `<span class="ok">Uploaded:</span>\n` + Object.values(js.paths).join('\n');
        runBox.style.display = 'block';
      }else{
        showErr(js.error, btn);
      }
    })
    .catch(err=>{
      btn.disabled = false;
      showErr(err, btn);
    });
});

function showErr(msg, btn){
  if(btn) btn.disabled = false;
  output.innerHTML = `<span class="err">${msg}</span>`;
}
async function buildDefaultMegListFromNodes(nodesFile) {
  const text = await nodesFile.text();
  const lines = text.split(/\r?\n/).filter(l => l.trim().length > 0);
  if (lines.length < 2) throw new Error('graph_nodes CSV is empty or missing data rows');

  const header = splitCsvLine(lines[0]).map(h => h.trim().toLowerCase());
  const idxCellgroup = header.indexOf('cellgroup');
  const idxName = header.indexOf('name');

  let keyIdx = idxCellgroup;
  let keyColName = 'cellgroup';
  if (keyIdx === -1) { keyIdx = idxName; keyColName = 'name'; }
  if (keyIdx === -1)
    throw new Error("graph_nodes must contain 'cellgroup' or 'name' column");

  const out = [`${keyColName},community`];

  for (let i = 1; i < lines.length; i++) {
    const cols = splitCsvLine(lines[i]);
    if (cols.length <= keyIdx) continue;
    const key = (cols[keyIdx] || '').trim();
    if (!key) continue;
    out.push(`${escapeCsv(key)},1`);
  }

  if (out.length === 1)
    throw new Error('No valid node names found in graph_nodes CSV');

  return new Blob([out.join('\n') + '\n'], { type: 'text/csv' });
}

function splitCsvLine(line) {
  const result = [];
  let cur = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const ch = line[i];
    if (ch === '"') {
      if (inQuotes && line[i + 1] === '"') { cur += '"'; i++; }
      else { inQuotes = !inQuotes; }
    } else if (ch === ',' && !inQuotes) {
      result.push(cur);
      cur = '';
    } else {
      cur += ch;
    }
  }
  result.push(cur);
  return result;
}

function escapeCsv(s) {
  if (/[,"\r\n]/.test(s)) return `"${String(s).replace(/"/g, '""')}"`;
  return String(s);
}

/* 运行 R 脚本 */
runBtn.addEventListener('click',()=>{
   if (!currentSid) {
    output.textContent = 'Error: No session id found. Please upload CSV files first.';
    return;
  }

  runBtn.disabled = true;
  runBtn.innerHTML = 'Running <span class="spinner"></span>';
  output.textContent = 'R script is running on the server...\nThis may take some time.\n';
  fetch('/cmt_figures/run_r_script.php?sid=' + encodeURIComponent(currentSid))
    .then(async r => {
      const raw = await r.text();
      if (!r.ok) throw new Error(`HTTP ${r.status}: ${raw.slice(0,500)}`);
      try { return JSON.parse(raw); }
      catch (e) {
        console.error('RAW RESPONSE >>>\n' + raw);
        throw new Error('Non-JSON response. See console for raw text.');
      }
    })
    .then(js => {
      runBtn.disabled = false;
      runBtn.textContent = 'Run R Script';

      if (js.success) {
        if (js.r_log_head) {
          output.textContent += `\n\n[R Output]\n${js.r_log_head}`;
        }
        if (js.viewerUrl) {
          const noDirUrl = js.viewerUrlNoDir || (js.viewerUrl + (js.viewerUrl.includes('?') ? '&' : '?') + 'mode=undirected');
          const linksHtml = `
            <div class="result-links">
              <a href="${js.viewerUrl}" target="_blank" rel="noopener">Open directed network</a>
              <a href="${noDirUrl}" target="_blank" rel="noopener">Open undirected network</a>
            </div>
          `.replace(/^\s+/gm, '');
          output.insertAdjacentHTML('beforeend', linksHtml);
        }
      } else {
        output.textContent += `\n\n[R] FAILED: ${js.error || 'unknown error'}\n`;
        if (js.r_log_head) output.textContent += js.r_log_head;
      }
    })
    .catch(err=>{
      runBtn.disabled = false;
      runBtn.textContent = 'Run R Script';
      output.textContent += `\n\nError: ${err}`;
    });
});

/* JSON session 上传逻辑 */
const jsonForm = document.getElementById('jsonForm');
if (jsonForm) {
  jsonForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const btn = jsonForm.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Uploading...';

    output.textContent = '';
    runBox.style.display = 'none';

    const fd = new FormData(jsonForm);

    fetch('/cmt_figures/upload_json.php', {
      method: 'POST',
      body: fd
    })
      .then(async r => {
        const raw = await r.text();
        try { return JSON.parse(raw); }
        catch {
          throw new Error(`Server did not return JSON.\n--- Raw response ---\n${raw}`);
        }
      })
      .then(js => {
        btn.disabled = false;
        btn.textContent = 'Upload JSON Session';

        if (!js.success) {
          showErr(js.error || 'JSON upload failed', null);
          return;
        }

        currentSid = js.sid || null;

        output.innerHTML =
          `<span class="ok">JSON session uploaded.</span>\n` +
          (js.path ? js.path + '\n' : '');

        if (js.viewerUrl) {
          const viewerUrl = js.viewerUrl;
          const noDirUrl  = js.viewerUrlNoDir || js.viewerUrlNoDirAlt;
          const noDirFinal = noDirUrl ||
            (viewerUrl + (viewerUrl.includes('?') ? '&' : '?') + 'mode=undirected');

          const linksHtml = `
            <div class="result-links">
              <a href="${viewerUrl}" target="_blank" rel="noopener">Open directed network</a>
              <a href="${noDirFinal}" target="_blank" rel="noopener">Open undirected network</a>
            </div>
          `.replace(/^\s+/gm, '');
          output.insertAdjacentHTML('beforeend', linksHtml);
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.textContent = 'Upload JSON Session';
        showErr(err, null);
      });
  });
}
</script>
</body>
</html>
