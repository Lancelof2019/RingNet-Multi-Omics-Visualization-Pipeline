# cmt_figures_multi_single — System Overview

1.Upload Index Page (upload_index.php):
The user uploads multiple CSV files, including edges, nodes, megList, expression, methylation, SNV, CNV, and stage.

2.File Upload Processing (upload.php):
The web platform receives the uploaded files, generates a unique session ID, creates a corresponding folder for this session, and saves all CSV files into it.
The script then returns a JSON object containing the file paths and session information.

3.Data Analysis via R (run_r_script.php & script.R):
The PHP script calls the R script to process the uploaded CSV files.
The R analysis generates a community_map_top100.json file and returns a front-end link for subsequent data visualization.

4.Front-End Visualization (viewer.html / nodir_viewer.html):
The visualization pages load the generated JSON file and use D3.js to render the multi-omics network.
The interface supports various functionalities including filtering, color adjustment, and exporting visual results.
