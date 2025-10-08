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

    body{font-family:Arial,Helvetica,sans-serif;margin:2rem}

    /* Each row: left column = label text, right column = file input control */
    label.file-label{
      display:flex;
      align-items:center;
      gap:var(--gap);
      margin:8px 0;
      color:#666;
    }

    /* Fix the width of the left column to prevent misalignment due to label length */
    label.file-label span{
      flex:0 0 var(--label-w);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    /* Align file input controls in the right column */
    label.file-label input[type=file]{ margin-left:0; }

    .required span::after{ content:" *"; color:#d00; margin-left:4px; }

    pre{ background:#f5f5f5; padding:10px; white-space:pre-wrap; }
    .err{ color:#d00 } .ok{ color:#070 }

    .spinner{
      display:inline-block; width:1em; height:1em;
      border:2px solid #ccc; border-top-color:#333;
      border-radius:50%; animation:spin .8s linear infinite
    }
    @keyframes spin{ to{ transform:rotate(360deg) } }

    /* Align the “Upload Files” button with the right column */
    #uploadForm > button[type="submit"]{
      margin-left:0;
    }
  </style>
</head>
<body>
<h2>Upload CSV Files</h2>

<form id="uploadForm" enctype="multipart/form-data" method="POST" action="upload.php">
  /* Two mandatory files */
  <label class="file-label required"><span>Graph Edges (.csv)</span>
    <input type="file" name="graph_edges" accept=".csv" required>
  </label>
  <label class="file-label required"><span>Graph Nodes (.csv)</span>
    <input type="file" name="graph_nodes" accept=".csv" required>
  </label>
  <label class="file-label required"><span>Node Group (.csv)</span>
    <input type="file" name="megList" accept=".csv" required>
  </label>
  /* Four optional files */
  <label class="file-label"><span>Data1 (continuous real value)</span><input type="file" name="gene_expression" accept=".csv,.tsv"></label>
  <label class="file-label"><span>Data2 (continuous real value)</span><input type="file" name="methylation" accept=".csv,.tsv"></label>
  <label class="file-label"><span>Data3 (integer value)</span><input type="file" name="snv" accept=".csv,.tsv"></label>
  <label class="file-label"><span>Data4 (integer value)</span><input type="file" name="cnv" accept=".csv,.tsv"></label>
  <label class="file-label"><span>Sample Group</span>
  <input type="file" name="stage" accept=".csv,.tsv">
  </label>
  <button type="submit">Upload&nbsp;Files</button>
</form>

<div id="runContainer" style="display:none;margin-top:20px">
  <h3>Run R Script</h3>
  <button id="runBtn">Run&nbsp;R&nbsp;Script</button>
</div>

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
/* Display uploaded file name */
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
  fetch('upload.php',{method:'POST',body:new FormData(form)})

  .then(async r => {
    const text = await r.text();       // Read the original text from response 
    try { return JSON.parse(text); }   // Try to parse JSON
    catch {
      throw new Error(`Server did not return JSON.\n--- Raw response ---\n${text}`);
    }
  })
    //.then(r=>r.json())
    .then(js=>{
      btn.disabled = false;
      if(js.success){
	uploadedPaths = js.paths;
	currentSid = js.sid; 
        output.innerHTML = `<span class="ok">Uploaded:</span>\n${js.paths.join('\n')}`;
        runBox.style.display = 'block';
      }else{ showErr(js.error, btn); }
    })
    .catch(err=>{ btn.disabled = false; showErr(err, btn); });
});

function showErr(msg, btn){
  if(btn) btn.disabled = false;
  output.innerHTML = `<span class="err">${msg}</span>`;
}

/* Run R script */
runBtn.addEventListener('click',()=>{
  if(!uploadedPaths.length||!currentSid) return;
  runBtn.disabled = true;
  runBtn.innerHTML = 'Running <span class="spinner"></span>';

  //const qs = uploadedPaths.map(p=>'files[]='+encodeURIComponent(p)).join('&');
  fetch('run_r_script.php?sid=' + encodeURIComponent(currentSid))
    .then(async r => {
    const raw = await r.text();              
    if (!r.ok) throw new Error(`HTTP ${r.status}: ${raw.slice(0,500)}`);
    try { return JSON.parse(raw); }           // Attempt to parse again
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

	   /* Provide two visualization entry points: Directed / Undirected */

	    const linksHtml = `<br><div style="text-align:left;"><a href="${js.viewerUrl}" target="_blank" rel="noopener">Open Result Viewer</a>&nbsp;|&nbsp; <a href="${noDirUrl}" target="_blank" rel="noopener">Open Result Viewer (no direction)</a> </div>`;
	    //output.insertAdjacentHTML('beforeend', linksHtml);
	    output.insertAdjacentHTML('beforeend', linksHtml.replace(/^\s+/gm, ''));
        }
        /* Alternatively, enable direct switching within the same tab */
    
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
</script>
</body>
</html>

