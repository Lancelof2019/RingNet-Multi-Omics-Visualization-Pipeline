## 🧬 Multi-Omics Visualization Pipeline

A lightweight web-based pipeline for **multi-omics network analysis and visualization**.  
This system allows users to upload CSV datasets, run integrated R scripts for data processing, and visualize community-level biological networks through an interactive D3.js front-end.


<img width="2254" height="752" alt="image" src="https://github.com/user-attachments/assets/e4f37bc5-f228-4b24-8e27-b8486d12cf5b" />



---

## Features

-  Upload and manage multiple omics data files:
  - Expression (`expression.csv`)
  - Methylation (`methylation.csv`)
  - SNV (`snv.csv`)
  - CNV (`cnv.csv`)
  - Stage / Clinical data (`stage.csv`)
  - Edges, Node, and megList network definitions
-  Automated R backend for community detection and JSON generation  
-  Interactive D3.js front-end with filtering, color adjustment, and export  
-  Support for both **directed** and **undirected** network modes  
-  Export results as SVG or JSON files  

---
## System Overview

1.Upload Index Page (`upload_index.php`):
The user uploads multiple CSV files, including edges, nodes, megList, expression, methylation, SNV, CNV, and stage.

2.File Upload Processing (`upload.php`):
The web platform receives the uploaded files, generates a unique session ID, creates a corresponding folder for this session, and saves all CSV files into it.
The script then returns a JSON object containing the file paths and session information.

3.Data Analysis via R (`run_r_script.php` & `script.R`):
The PHP script calls the R script to process the uploaded CSV files.
The R analysis generates a community_map_top100.json file and returns a front-end link for subsequent data visualization.

4.Front-End Visualization (`viewer.html` / `nodir_viewer.html`):
The visualization pages load the generated JSON file and use D3.js to render the multi-omics network.
The interface supports various functionalities including filtering, color adjustment, and exporting visual results.

## Files introduction of the system
| **Module** | **Input** | **Output** | **Core Function** |
|-------------|------------|-------------|--------------------|
| upload.html | CSV files | FormData | Front-end entry for file uploading |
| upload.php | FormData | JSON ({sid, paths}) | Creates a session directory and saves uploaded files |
| run_r_script.php | Session ID (sid) | JSON ({viewerUrl, log}) | Executes R analysis and returns result file paths |
| script.R | CSV file paths | JSON file | Performs data analysis and generates the network structure |
| viewer.html | JSON file | SVG / D3 visualization | Visualizes the network and supports export operations |
| nodir_viewer.html | JSON file | SVG / D3 visualization | Visualizes the network without node directions and supports export operations |

## System Workflow
```
   ┌─────────────────────────────────┐
   │  User opens upload.html         │
   │  Selects and uploads CSV files  │
   └─────────────┬───────────────────┘
                 │
    [FormData POST → /upload.php]
                 │
                 ▼
   ┌────────────────────────────────────────────┐
   │ upload.php                                 │
   │ Validates files → Creates session ID (sid) │
   │ → Saves files to uploads/sid/*.csv         │
   │ → Returns {success, sid, paths[]}          │
   └────────────────┬───────────────────────────┘
                    │
       [Front-end shows "Run R Script" button]
                    │
                    ▼
   ┌─────────────────────────────────────────────┐
   │ run_r_script.php                            │
   │ Receives sid → Scans uploads/sid/ directory │
   │ → Builds R command → Executes Rscript       │
   │ → Generates community_map_top100.json       │
   │ → Returns viewer URLs                       │
   └──────────────────┬──────────────────────────┘
                      │
                      ▼
      ┌───────────────────────────────────┐
      │ script.R                          │
      │ Reads multi-omics data            │
      │ Builds igraph community structure │
      │ Computes node features and layout │
      │ Outputs JSON network map          │
      └───────────┬───────────────────────┘
                  │
                  ▼
   ┌────────────────────────────────────────────────────────┐
   │ viewer.html or nodir_ viewer.html                      │
   │ Fetches uploads/sid/community_map                      │
   │ Visualizes with D3.js — filtering, coloring, exporting │
   └────────────────────────────────────────────────────────┘

```
<img width="4638" height="1167" alt="image" src="https://github.com/user-attachments/assets/f5489845-5803-44e8-9680-0da0cd3b6a45" />





