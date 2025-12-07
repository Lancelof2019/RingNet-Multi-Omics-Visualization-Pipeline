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
    /* 背景图设置 ↓↓↓ */
    background-image: url('pictures_logo/logo-en-purple-small.png'); /* ← 改成你上传的图片文件名 */
    background-size: 60%;       /* ✅ 显示完整图片 */
    background-repeat: no-repeat;   /* 不重复 */
    background-position: 60% 50%; /* 贴底显示 */
    background-attachment: fixed;
    position: relative;
}

  /* 半透明白色遮罩，让文字更清晰 */
  body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,0.6); /* 透明度可调，0.5 更透亮 */
    z-index: -1;
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

 /*pre{
    background:#f9f9f9;
    padding:10px;
    white-space:pre-wrap;
    border-radius:8px;
    box-shadow:inset 0 0 3px rgba(0,0,0,0.1);
  }*/
   /* 移除 Upload Files 按钮下方灰线 */
  pre#output {
     margin-top: 0;
     background: transparent;   /* 背景设为透明 */
     border: none;              /* 去掉默认边框 */
     box-shadow: none;          /* 去掉灰色内阴影 */
     padding: 0;                /* 去掉内边距 */
   }
  .err{ color:#d00 }
  .ok{ color:#070 }
  .spinner{
    display:inline-block; width:1em; height:1em;
    border:2px solid #ccc; border-top-color:#333;
    border-radius:50%; animation:spin .8s linear infinite;
  }
  @keyframes spin{ to{ transform:rotate(360deg) } }

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

  /* 🎨 Template_download 链接样式 */
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
  .template-link:hover{
    background: rgba(0,102,204,0.18);
    color: #004080;
    text-decoration: underline;
  }
 .main-title {
   font-family: "Segoe UI", Arial, Helvetica, sans-serif;
   font-weight: 700;         /* 黑体加粗 */
   font-size: 2rem;          /* 比下面的 h2 稍大（默认 1.2–1.4rem） */
   color: #111;              /* 深黑色 */
   text-align: center;         /* 左对齐，也可改为 center 居中 */
   margin-bottom: 1rem;    /* 与下方文字间距 */
   text-shadow: 0 1px 2px rgba(0,0,0,0.1); /* 微弱阴影，增强立体感 */
  }
  </style>
</head>
<body>
<h1 class="main-title">RingNet visualization</h1>
<h2>Upload CSV Files</h2>

<form id="uploadForm" enctype="multipart/form-data" method="POST" action="upload.php">
  <!-- 两个必选 -->
  <label class="file-label required"><span>Graph Edges (.csv)</span>
    <input type="file" name="graph_edges" accept=".csv" required>
    <a href="examples/example_graph_edges.csv" download class="template-link">Template_download</a>
    <a href="examples/singlecell_edges.csv" download class="template-link" style="margin-left:10px;">or Single_Cell_Template</a>
  </label>
  <label class="file-label required"><span>Graph Nodes (.csv)</span>
    <input type="file" name="graph_nodes" accept=".csv" required>
  <a href="examples/example_graph_nodes.csv" download class="template-link">Template_download</a>
  <a href="examples/singlecell_nodes.csv" download class="template-link" style="margin-left:10px;">or Single_Cell_Template</a>
  </label>
  <label class="file-label required"><span>Node Group (.csv)</span>
    <input type="file" name="megList" accept=".csv" required>
  <a href="examples/example_megList.csv" download class="template-link">Template_download</a>
  <a href="examples/singlecell_megList.csv" download class="template-link" style="margin-left:10px;">or Single_Cell_Template</a>
  </label>
  <!-- 四选一 -->
  <label class="file-label"><span>Data1 (continuous real value)</span><input type="file" name="gene_expression" accept=".csv,.tsv"><a href="examples/example_gene_expression.csv" download class="template-link">Template_download</a> <a href="examples/singlecell_expression_matrix.csv" download class="template-link" style="margin-left:10px;">or Single_Cell_Template</a></label>
  <label class="file-label"><span>Data2 (continuous real value)</span><input type="file" name="methylation" accept=".csv,.tsv"><a href="examples/example_methylation.csv" download class="template-link">Template_download</a><a href="examples/singlecell_expression_matrix.csv" download class="template-link" style="margin-left:10px;">or Single_Cell_Template</a></label>
  <label class="file-label"><span>Data3 (integer value)</span><input type="file" name="snv" accept=".csv,.tsv"><a href="examples/example_cnv.csv" download class="template-link">Template_download</a></label>
  <label class="file-label"><span>Data4 (integer value)</span><input type="file" name="cnv" accept=".csv,.tsv"><a href="examples/example_snv.csv" download class="template-link">Template_download</a></label>
  <label class="file-label"><span>Sample Group</span>
  <input type="file" name="stage" accept=".csv,.tsv">
  <a href="examples/example_stage.csv" download class="template-link">Template_download</a>
  </label>
  <button type="submit">Upload&nbsp;Files</button>
</form>

<div id="runContainer" style="display:none;margin-top:20px">
  <h3>Run R Script</h3>
  <button id="runBtn">Run&nbsp;R&nbsp;Script</button>
</div>
<!-- 🔽 新增：直接上传 JSON session 的入口 -->
<h2 style="margin-top:2rem;">Or upload an existing JSON session</h2>
<form id="jsonForm" enctype="multipart/form-data" method="POST" action="upload_json.php">
  <label class="file-label required">
    <span>Session JSON (community_map_top100.json)</span>
    <input type="file" name="session_json" accept=".json" required>
  </label>
  <button type="submit">Upload JSON Session</button>
</form>
<!-- 新增结束 -->
<pre id="output"></pre>

<script>
const form   = document.getElementById('uploadForm');
const output = document.getElementById('output');
const runBox = document.getElementById('runContainer');
const runBtn = document.getElementById('runBtn');
let uploadedPaths = [];
let currentSid = null; // ★
const reqThree = ['graph_edges','graph_nodes','megList'];
//const optFour = ['gene_expression','methylation','snv','cnv'];
const optFive = ['gene_expression','methylation','snv','cnv','stage'];
/* 文件名回显 */
form.querySelectorAll('input[type=file]').forEach(inp=>{
  inp.addEventListener('change',()=>{
    const span = inp.closest('label').querySelector('span');
    span.textContent = inp.files.length ? inp.files[0].name : inp.name.replace('_',' ');
  });
});

form.addEventListener('submit',e=>{
  e.preventDefault();
  runBox.style.display='none';
  uploadedPaths = [];
  currentSid = null; // ★
  output.textContent = '';

  const btn = form.querySelector('button');
  btn.disabled = true;

  /* 校验 */
 for (const k of reqThree) {
  if (!form.elements[k] || !form.elements[k].files.length) {
    return showErr(`${k} required`, btn);
  }
 }
 /* if (!optFour.some(k=>form.elements[k].files.length)){
    showErr('One of gene expression / methylation / snv / cnv required', btn);
    return;
  }
  */
 if (!optFive.some(k=>form.elements[k].files.length)){
   showErr('One of gene expression / methylation / snv / cnv / stage is required', btn);
    return;
  }
  fetch('upload.php',{method:'POST',body:new FormData(form)})

  .then(async r => {
    const text = await r.text();       // 先读原文
    try { return JSON.parse(text); }   // 尝试解析 JSON
    catch {
      throw new Error(`Server did not return JSON.\n--- Raw response ---\n${text}`);
    }
  })
    //.then(r=>r.json())
    .then(js=>{
      btn.disabled = false;
      if(js.success){
        uploadedPaths = js.paths;
        currentSid = js.sid; // ★
        //output.innerHTML = `<span class="ok">Uploaded:</span>\n${js.paths.join('\n')}`;
        output.innerHTML =`<span class="ok">Uploaded:</span>\n` + Object.values(js.paths).join('\n');
        runBox.style.display = 'block';
      }else{ showErr(js.error, btn); }
    })
    .catch(err=>{ btn.disabled = false; showErr(err, btn); });
});

function showErr(msg, btn){
  if(btn) btn.disabled = false;
  output.innerHTML = `<span class="err">${msg}</span>`;
}

/* 运行 R 脚本 */
runBtn.addEventListener('click',()=>{
  if(!uploadedPaths.length||!currentSid) return;
//  if (!currentSid) {
  //    output.innerHTML += "\n\nError: SID missing, upload may have failed.";
    //  return;
 // }
  runBtn.disabled = true;
  runBtn.innerHTML = 'Running <span class="spinner"></span>';

  //const qs = uploadedPaths.map(p=>'files[]='+encodeURIComponent(p)).join('&');
  //fetch('run_r_script.php?sid=' + encodeURIComponent(currentSid))
  fetch('/cmt_figures/run_r_script.php?sid=' + encodeURIComponent(currentSid))
    .then(async r => {
    const raw = await r.text();               // 先拿原文
    if (!r.ok) throw new Error(`HTTP ${r.status}: ${raw.slice(0,500)}`);
    try { return JSON.parse(raw); }           // 再尝试解析
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
                //output.innerHTML += `\n\n<a href="${js.viewerUrl}" target="_blank" rel="noopener">Open Result Viewer</a>`;
            const noDirUrl = js.viewerUrlNoDir||(js.viewerUrl + js.viewerUrl.includes('?') ? '&' : '?') + 'mode=undirected';

            // 同时给出两个入口：有向 / 无向
            //
            //
            const linksHtml = `<br><div style="text-align:left;"><a href="${js.viewerUrl}" target="_blank" rel="noopener">Open Result Viewer</a>&nbsp;|&nbsp; <a href="${noDirUrl}" target="_blank" rel="noopener">Open Result Viewer (no direction)</a> </div>`;
            //output.insertAdjacentHTML('beforeend', linksHtml);
            output.insertAdjacentHTML('beforeend', linksHtml.replace(/^\s+/gm, ''));
        }
        // 如需在同标签直接跳转，也可以：
        // window.location.href = js.viewerUrl;
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

/* ================== 新增：JSON session 上传逻辑 ================== */
const jsonForm = document.getElementById('jsonForm');

if (jsonForm) {
  jsonForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const btn = jsonForm.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Uploading...';

    output.textContent = '';        // 共用同一个输出区域
    runBox.style.display = 'none';  // JSON 流程不需要跑 R

    const fd = new FormData(jsonForm);

    fetch('upload_json.php', {
      method: 'POST',
      body: fd          // ★ 注意这里是 fd，不是 fdz
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

        // 如果想让后续 runBtn 也可以用这个 sid，可以顺便记录
        currentSid = js.sid || null;

        output.innerHTML =
          `<span class="ok">JSON session uploaded.</span>\n` +
          (js.path ? js.path + '\n' : '');

        if (js.viewerUrl) {
          const viewerUrl = js.viewerUrl;
          const noDirUrl  = js.viewerUrlNoDir || js.viewerUrlNoDirAlt;

          // 如果后端提供 nodir.html 的链接，就直接用；
          // 否则退回 viewer.html?mode=undirected
          const noDirFinal = noDirUrl ||
            (viewerUrl + (viewerUrl.includes('?') ? '&' : '?') + 'mode=undirected');

          const linksHtml =
            `<br><div style="text-align:left;">
               <a href="${viewerUrl}" target="_blank" rel="noopener">
                 Open Result Viewer
               </a>
               &nbsp;|&nbsp;
               <a href="${noDirFinal}" target="_blank" rel="noopener">
                 Open Result Viewer (no direction)
               </a>
             </div>`.replace(/^\s+/gm, '');

          output.insertAdjacentHTML('beforeend', linksHtml);
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.textContent = 'Upload JSON Session';
        showErr(err, null);
      });
}
</script>
</body>
</html>
