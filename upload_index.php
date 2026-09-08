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

  background-image: none;     /* remove watermark */
  background-color: #f5f6f8;  /* research-tool-like background */

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

/* Tutorial row: same visual style as file rows */
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

.tutorial-row .template-link{
  color:#7b1fa2;
  background:rgba(123,31,162,0.08);
  border:1px solid rgba(123,31,162,0.22);
}

.tutorial-row .template-link:hover{
  color:#4a0f6b;
  background:rgba(123,31,162,0.16);
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

/* Compact upload rows: file input on the left, no per-row example links */
label.file-label input[type="file"]{
  flex: 0 0 230px;
  max-width: 230px;
}

.example-links{
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  gap:10px;
  flex:1 1 auto;
}

.example-links .template-link{
  font-weight:500;
}

.file-section-note{
  margin: 2px 0 10px 0;
  color:#666;
  font-size:0.92rem;
}

.err{ color:#d00 }
.ok{ color:#070 }

.spinner{
  display:inline-block;
  width:1em;
  height:1em;
  border:2px solid #ccc;
  border-top-color:#333;
  border-radius:50%;
  animation:spin .8s linear infinite;
}

@keyframes spin{
  to{ transform:rotate(360deg) }
}

/* Shared style for both form buttons */
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

/* Template/download link style */
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

/* Output area */
#output {
  margin-top: 10px;
  white-space: pre-wrap;
  font-family: "Segoe UI", Arial, sans-serif;
  background: transparent;
  border: none;
  box-shadow: none;
  padding: 0;
}

/* Result links */
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

/* Top brand bar: RingNet left, Tampere right */
.header-bar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom: 1rem;
}

/* Left RingNet */
.brand-left{
  display:flex;
  align-items:center;
}

.ringnet-logo{
  height: 140px;
  width: auto;
  display:block;
}

/* Right Tampere */
.brand-right{
  display:flex;
  align-items:center;
  justify-content:flex-end;
}

.tampere-logo{
  height: 100px;
  width: auto;
  display:block;
  opacity: 0.8;
}

.title-svg{
  width: 25%;
  max-width: 170px;
  height: auto;
  display: block;
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

.fake-file-input{
  display: inline-block;
  width: 220px;
}

#analysis-access-card{
  display: inline-flex;
  flex-direction: column;

  align-items: center;
  justify-content: center;

  padding: 6px 14px;
  margin: 0 0 14px 0;

  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.12);
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

/* Top label */
#analysis-access-card .access-label{
  font-size: 0.62rem;
  letter-spacing: 0.08em;
  color: #777;
  text-transform: uppercase;
  line-height: 1;
  margin-bottom: 2px;
  text-align: center;
}

/* Center number */
#analysis-access-card .access-value{
  font-size: 1.00rem;
  font-weight: 700;
  color: #111;
  line-height: 1;
  text-align: center;
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

<!-- ===== Analysis Access Counter ===== -->
<div id="analysis-access-card">
  <div class="access-label">Datasets Visualized</div>
  <div class="access-value" id="analysisAccess">loading…</div>
</div>

<p class="hint">
  RingNet is a tool for visualizing multimodal data in networks. It supports the creation of networks with one or more data sets. In addition to the <code>general template</code>, we provide templates for visualizing <code>gene-gene interaction networks</code>, <code>patient similarity networks</code>, and <code>single-cell networks</code>. You can download complete example data ZIP files below and use them as references for preparing CSV files. If you already have a JSON file generated by RingNet, you can use it directly for visualization without rerunning the program.
  <br><br>
  To ensure a clean and readable network, the current limit on the number of nodes is 100. If your network exceeds this limit, the frontend will select the top 100 features (e.g., genes with the highest average expression levels) for visualization. Therefore, we recommend that users predefine their network with 100 nodes. Additionally, you can use the <code>07-Sample Group</code> to divide your large network into multiple subnetworks, which can be visualized separately in the frontend. In the top left of the user interface, you can select the subnetworks to visualize.
  For any bug or feedback, please contact us via RingNet's GitHub repository
  <a href="https://github.com/laixn/RingNet" target="_blank" rel="noopener">https://github.com/laixn/RingNet</a>.
  For more information about RingNet, you can find it in the BioRxiv preprint
  (<a href="https://doi.org/10.64898/2026.01.20.700593" target="_blank" rel="noopener">https://doi.org/10.64898/2026.01.20.700593</a>).
</p>

<h2>Upload CSV files</h2>

<form id="uploadForm" enctype="multipart/form-data" method="POST" action="/cmt_figures/upload.php">

  <!-- Tutorial links -->
  <label class="file-label tutorial-row">
    <span>Tutorial</span>
    <div class="example-links">
      <a href="https://github.com/Lancelof2019/ringnet_turtorial/tree/check/gene_network_turtorial"
         target="_blank"
         rel="noopener"
         class="template-link">
        Gene network tutorial
      </a>

      <a href="https://github.com/Lancelof2019/ringnet_turtorial/blob/check/patient_network_turtorial"
         target="_blank"
         rel="noopener"
         class="template-link">
        Patient network tutorial
      </a>

      <a href="https://github.com/Lancelof2019/ringnet_turtorial/tree/check/single_cell_network_turtorial"
         target="_blank"
         rel="noopener"
         class="template-link">
        Single cell network tutorial
      </a>
    </div>
  </label>

  <!-- Example data links -->
  <label class="file-label examples-row">
    <span>Examples data file</span>
    <div class="example-links">
      <a href="examples/gene_network_example.zip" download class="template-link">Gene network example data</a>
      <a href="examples/patient_network_example.zip" download class="template-link">Patient network example data</a>
      <a href="examples/single_cell_network_example.zip" download class="template-link">Single cell network example data</a>
    </div>
  </label>

  <p class="file-section-note">Upload your CSV files below. The example ZIP packages above contain complete case-specific files.</p>

  <label class="file-label required">
    <span>00-Graph Edges (.csv)</span>
    <input type="file" name="graph_edges" accept=".csv" required>
  </label>

  <label class="file-label required">
    <span>01-Graph Nodes (.csv)</span>
    <input type="file" name="graph_nodes" accept=".csv" required>
  </label>

  <label class="file-label required">
    <span>02-Node Group meg List(.csv)</span>
    <input type="file" name="megList" accept=".csv">
  </label>

  <!-- At least one of these data files is required -->
  <label class="file-label">
    <span>03-Data1 (continuous real value)</span>
    <input type="file" name="gene_expression" accept=".csv,.tsv">
  </label>

  <label class="file-label">
    <span>04-Data2 (continuous real value)</span>
    <input type="file" name="methylation" accept=".csv,.tsv">
  </label>

  <label class="file-label">
    <span>05-Data3 (integer value)</span>
    <input type="file" name="snv" accept=".csv,.tsv">
  </label>

  <label class="file-label">
    <span>06-Data4 (integer value)</span>
    <input type="file" name="cnv" accept=".csv,.tsv">
  </label>

  <label class="file-label">
    <span>07-Sample Group</span>
    <input type="file" name="stage" accept=".csv,.tsv">
  </label>

  <button type="submit">Upload&nbsp;Files</button>
</form>

<div id="runContainer" style="display:none;margin-top:20px">
  <h3>Run R Script</h3>
  <button id="runBtn">Run&nbsp;R&nbsp;Script</button>
</div>

<h2 style="margin-top:2rem;">Or upload a JSON / snapshot ZIP file</h2>

<form id="jsonForm" enctype="multipart/form-data" method="POST" action="/cmt_figures/upload_json.php">
  <label class="file-label required">
    <span>Session JSON / Directed or Undirected Snapshot ZIP</span>
    <input type="file" name="session_json" accept=".json,.zip,application/json,application/zip" required>
    <a href="examples/gene.json"
       download
       class="template-link" style="color:#ca0020;">Gene network json example</a>
    <a href="examples/patient.json"
       download
       class="template-link" style="color:#ca0020;">Patient network json example</a>
    <a href="examples/singlecell.json"
       download
       class="template-link" style="color:#ca0020;">Single cell network json example</a>
  </label>

  <button type="submit">Upload&nbsp;JSON&nbsp;/&nbsp;Snapshot</button>
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

/* Keep the left-side field labels fixed after file selection.
   The browser's file input already displays the selected file name,
   so we do not overwrite labels such as "00-Graph Edges (.csv)". */
form.querySelectorAll('input[type=file]').forEach(inp=>{
  inp.addEventListener('change',()=>{
    // Intentionally empty: preserve the original left-side label text.
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

  /* Validation */
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
    body: fd
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
  if (keyIdx === -1) {
    keyIdx = idxName;
    keyColName = 'name';
  }

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
      if (inQuotes && line[i + 1] === '"') {
        cur += '"';
        i++;
      } else {
        inQuotes = !inQuotes;
      }
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

/* Run R script */
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

/* JSON session upload logic */
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
      btn.textContent = 'Upload JSON / Snapshot';

      if (!js.success) {
        showErr(js.error || 'JSON upload failed', null);
        return;
      }

      currentSid = js.sid || null;

      const modeText = js.graphMode && js.graphMode !== 'unknown'
        ? ` (${js.graphMode} snapshot detected)`
        : '';

      output.innerHTML =
        `<span class="ok">JSON/snapshot session uploaded${modeText}.</span>\n` +
        (js.path ? js.path + '\n' : '') +
        (js.snapshotPath ? js.snapshotPath + '\n' : '');

      if (js.viewerUrl) {
        const primaryUrl = js.viewerUrl;
        const directedUrl = js.viewerUrlDirected || (primaryUrl.includes('viewer.html') ? primaryUrl : null);
        const noDirUrl  = js.viewerUrlNoDir || js.viewerUrlNoDirAlt;
        const noDirFinal = noDirUrl ||
          (primaryUrl + (primaryUrl.includes('?') ? '&' : '?') + 'mode=undirected');

        const primaryLabel = js.graphMode === 'undirected'
          ? 'Open saved undirected snapshot'
          : js.graphMode === 'directed'
            ? 'Open saved directed snapshot'
            : 'Open saved viewer';

        const linksHtml = `
          <div class="result-links">
            <a href="${primaryUrl}" target="_blank" rel="noopener">${primaryLabel}</a>
            ${directedUrl ? `<a href="${directedUrl}" target="_blank" rel="noopener">Open directed network</a>` : ''}
            <a href="${noDirFinal}" target="_blank" rel="noopener">Open undirected network</a>
          </div>
        `.replace(/^\s+/gm, '');
        output.insertAdjacentHTML('beforeend', linksHtml);
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.textContent = 'Upload JSON / Snapshot';
      showErr(err, null);
    });
  });
}

/* ===== Load Analysis Access Counter ===== */
(function loadAnalysisAccess(){
  const CSV_URL = "/cmt_figures/track/events.csv";
  const target  = document.getElementById("analysisAccess");

  if (!target) return;

  fetch(CSV_URL, { cache: "no-store" })
    .then(res => {
      if (!res.ok) throw new Error(res.status);
      return res.text();
    })
    .then(text => {
      const rows = text
        .trim()
        .split("\n")
        .map(line =>
          line.split(",").map(v => v.replace(/^"|"$/g, "").trim())
        );

      if (rows.length <= 1) {
        target.textContent = "0";
        return;
      }

      const header = rows[0];
      const data   = rows.slice(1);

      const eventIdx  = header.indexOf("event");
      const detailIdx = header.indexOf("detail");

      if (eventIdx === -1 || detailIdx === -1) {
        target.textContent = "–";
        return;
      }

      const count = data.filter(r =>
        r[eventIdx] === "viewer_open" &&
        r[detailIdx] === "first=1"
      ).length;

      target.textContent = count;
    })
    .catch(err => {
      console.error("Analysis access load failed:", err);
      target.textContent = "–";
    });
})();
</script>
</body>
</html>

