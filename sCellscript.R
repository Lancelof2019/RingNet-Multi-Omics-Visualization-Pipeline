
#devtools::install_github("sqjin/CellChat", lib="~/R_libs")
library(CellChat, lib.loc="~/R_libs")
library(patchwork)
library(igraph)
library(dplyr)
library(ggplot2)
library(tidyverse)
library(jsonlite)
library(ggraph)
library(tidygraph)
library(RColorBrewer)
library(ggrepel)
library(scales)
library(jsonlite)
library(RColorBrewer)
library(colorspace)
setwd("/users/zhanglia/single_cell_network/test_cell/")
load("../data_humanSkin_CellChat.rda")

suppressPackageStartupMessages({
  library(parallel)
  library(igraph)
  library(graphlayouts)
  library(jsonlite)
  library(tictoc)
})

TOP_N <- 100
KEEP_DIRECTED <- TRUE

args <- commandArgs(trailingOnly = TRUE)
#if (!(length(args) %in% c(9,10))) {
if (length(args) != 9) {
 stop(paste0(
  "error and uasge:\n",
    "Rscript community_map_csv_only.R \\\n",
    "<edges.csv> <nodes.csv> <megList.csv> <expr.csv> <meth.csv> <snv.csv> <cnv.csv> <stage.csv> [out.json]\n"
           ))
}
names(args)[1:8] <- c("edges","nodes","memb","expr","meth","snv","cnv","stage")

out_json <- if (length(args) == 9) args[9] else stop("Need output path (session specific)")

## ---------- 1) Read graph (CSV) and community (megList) ----------
edges_df <- read.csv(args["edges"], check.names = FALSE)
nodes_df_tmp <- read.csv(args["nodes"], check.names = FALSE)#for gene node list it is name

if ("cellgroup" %in% names(nodes_df_tmp)) {
  nodes_df <- nodes_df_tmp["cellgroup"]
} else if ("name" %in% names(nodes_df_tmp)) {
  nodes_df <- data.frame(cellgroup = nodes_df_tmp$name, stringsAsFactors = FALSE)
} else {
  stop("❌ nodes CSV ")
}

# Support automatic mapping of columns source/target → from/to
if (!all(c("from","to") %in% names(edges_df))) {
  if (all(c("source","target") %in% names(edges_df))) {
    names(edges_df)[match(c("source","target"), names(edges_df))] <- c("from","to")
  } else stop("edges CSV list：from, to（or source, target）")
}

if (!("cellgroup" %in% names(nodes_df))) {
  if ("name" %in% names(nodes_df)) {
    nodes_df$cellgroup <- nodes_df$name
    message("⚠️ 'cellgroup' column not found — using 'name' instead.")
  } else {
    stop("❌ nodes CSV must contain either 'cellgroup' or 'name' column.")
  }
}

if (!("weight" %in% names(edges_df))) edges_df$weight <- NA


# --- Read community information from megList ---
a_memb <- read.csv(args["memb"], check.names = FALSE)


names(a_memb) <- tolower(trimws(names(a_memb)))


if ("cellgroup" %in% names(a_memb)) {
  a_memb <- a_memb[, c("cellgroup", "community")]
} else if ("name" %in% names(a_memb)) {
  
  a_memb <- a_memb[, c("name", "community")]
 
} else {
  stop("a_memb error")
}



key_col <- if ("cellgroup" %in% names(a_memb)) "cellgroup" else
  if ("name" %in% names(a_memb))      "name" else
    stop("a_memb must contain either 'cellgroup' or 'name'")


a_memb <- a_memb[!duplicated(a_memb[[key_col]]), ]
mem_vec <- setNames(as.integer(a_memb$community), a_memb[[key_col]])

rm(a_memb)
#########################
# Reconstruct the entire graph (consistent with the original structure;subsequent steps still use induced_subgraph)
#graph_comp <- graph_from_data_frame(d = edges_df, vertices = nodes_df, directed = FALSE)
#if (is.null(E(graph_comp)$weight)) E(graph_comp)$weight <- 1

nodes_df<-unique(nodes_df)
nodes_df <- nodes_df %>%
  rename(name = cellgroup)


edges_df <- edges_df %>%
  select(from, to, everything())

#edges_df <- edges_df %>%
 # mutate(interact_tag = paste0(from_gene, "@", to_gene))

graph_comp <- graph_from_data_frame(
  d = edges_df,
  vertices = nodes_df,
  directed = KEEP_DIRECTED   ## <-- Previously set to FALSE; now replaced with a toggle (default = TRUE)
)
if (is.null(E(graph_comp)$weight)) E(graph_comp)$weight <- 1
if (KEEP_DIRECTED) {
  V(graph_comp)$degree_all <- degree(graph_comp, mode = "all")
  V(graph_comp)$degree_in  <- degree(graph_comp, mode = "in")
  V(graph_comp)$degree_out <- degree(graph_comp, mode = "out")
} else {
  V(graph_comp)$degree_all <- degree(graph_comp, mode = "all")
}

melanet_spg <- structure(list(
  membership = mem_vec,
  algorithm  = "csv",
  modularity = NA_real_,
  vcount     = vcount(graph_comp)
), class = "communities")

message("🎯 Drawing colorful multi-edge network with curvature...")

tg <- as_tbl_graph(graph_comp)

dup_edges <- which_multiple(graph_comp)
if (any(dup_edges)) {
  message(sprintf("✅ Found %d multi-edges in graph.", sum(dup_edges)))
} else {
  message("ℹ No duplicated (multi) edges detected.")
}
##---------- 2) Read omics matrices ----------
read_or_empty <- function(path) {
  if (is.null(path) || path %in% c("", "NA", "NULL", "null", "-") || !file.exists(path)) {
    data.frame()
  } else {
    read.csv(path, row.names = 1, check.names = FALSE)
  }
}

read_stage_vector <- function(path) {
  if (is.null(path) || path %in% c("", "NA", "NULL", "null", "-") || !file.exists(path)) {
    return(NULL)
  }
  df <- tryCatch(read.csv(path, check.names = FALSE), error = function(e) NULL)
  if (is.null(df) || ncol(df) < 2) return(NULL)
  
  # Detect columns: prioritize name matching; otherwise take the first two columns
  sample_col <- which(grepl("sample|id|name", tolower(names(df))))[1]
  index_col  <- which(grepl("index|stage|class|group", tolower(names(df))))[1]
  if (is.na(sample_col) || is.na(index_col)) {
    sample_col <- 1; index_col <- 2
  }
  s <- as.character(df[[sample_col]])
  idx <- suppressWarnings(as.integer(df[[index_col]]))
  names(idx) <- s
  idx
}

e_raw <- read_or_empty(args["expr"])
e_raw_test<-t(e_raw)
e_raw_tmp<-e_raw
e_raw<-NULL
e_raw<-e_raw_test

m_raw <- read_or_empty(args["meth"])
snv_m <- read_or_empty(args["snv"])
cnv_m <- read_or_empty(args["cnv"])
stage_v <- read_stage_vector(args["stage"]) 
# At least one omics file must be non-empty
#if (ncol(e_raw)==0 && ncol(m_raw)==0 && ncol(snv_m)==0 && ncol(cnv_m)==0) {
#  stop("At least one of expr/meth/snv/cnv must be provided.")
#}
if (ncol(e_raw)==0 && ncol(m_raw)==0 && ncol(snv_m)==0 && ncol(cnv_m)==0 && is.null(stage_v)) {
  stop("At least one of expr/meth/snv/cnv/stage must be provided.")
}



## ---------- 3) Synchronize samples ----------
#samples <- Reduce(intersect, list(rownames(e_raw), rownames(m_raw), rownames(snv_m), rownames(cnv_m)))
#if (!length(samples)) stop("please check the common sample name")
rn_list <- list()
if (nrow(e_raw)  > 0) rn_list[[length(rn_list)+1]] <- rownames(e_raw)
if (nrow(m_raw)  > 0) rn_list[[length(rn_list)+1]] <- rownames(m_raw)
if (nrow(snv_m) > 0) rn_list[[length(rn_list)+1]] <- rownames(snv_m)
if (nrow(cnv_m) > 0) rn_list[[length(rn_list)+1]] <- rownames(cnv_m)
if (!is.null(stage_v)) rn_list[[length(rn_list)+1]] <- names(stage_v)   
if (!length(rn_list)) stop("no sample info provided at all")
samples <- Reduce(intersect, rn_list)
if (!length(samples)) stop("please check the common sample name (no overlap among provided matrices)")
stage_idx <- NULL
if (!is.null(stage_v)) {
  stage_idx <- unname(stage_v[samples])  #  NA could exist
  ord <- order(stage_idx, na.last = TRUE)  # Sort indices in ascending order; place NA values at the end
  samples <- samples[ord]
  
  # Reorder matrices according to the new sample order
  if (nrow(e_raw)  > 0) e_raw  <- e_raw[samples,,drop=FALSE]
  if (nrow(m_raw)  > 0) m_raw  <- m_raw[samples,,drop=FALSE]
  if (nrow(snv_m) > 0) snv_m  <- snv_m[samples,,drop=FALSE]
  if (nrow(cnv_m) > 0) cnv_m  <- cnv_m[samples,,drop=FALSE]
  
  # Reorder matrices again to align with sample order
  stage_idx <- unname(stage_v[samples])
} else {
  #If no stage file is provided, fill with all-NA placeholder ）
  stage_idx <- rep(NA_integer_, length(samples))
}


if (nrow(e_raw)  > 0) e_raw <- e_raw[samples,,drop=FALSE]
if (nrow(m_raw)  > 0) m_raw <- m_raw[samples,,drop=FALSE]
if (nrow(snv_m) > 0) snv_m <- snv_m[samples,,drop=FALSE]
if (nrow(cnv_m) > 0) cnv_m <- cnv_m[samples,,drop=FALSE]

## ---------- 4) Select TOP_N genes with highest expression (intersecting genes only) ----------

cn_list <- list()
if (ncol(e_raw)  > 0) cn_list[[length(cn_list)+1]] <- colnames(e_raw)
if (ncol(m_raw)  > 0) cn_list[[length(cn_list)+1]] <- colnames(m_raw)
if (ncol(snv_m) > 0) cn_list[[length(cn_list)+1]] <- colnames(snv_m)
if (ncol(cnv_m) > 0) cn_list[[length(cn_list)+1]] <- colnames(cnv_m)
common_genes <- Reduce(intersect, cn_list)
if (!length(common_genes)) stop("no common gene among provided matrices")

#e_raw  <- e_raw[, common_genes, drop = FALSE]
#m_raw  <- m_raw[, common_genes, drop = FALSE]
#snv_m  <- snv_m[, common_genes, drop = FALSE]
#cnv_m  <- cnv_m[, common_genes, drop = FALSE]
if (ncol(e_raw)  > 0) e_raw <- e_raw[, common_genes, drop = FALSE]
if (ncol(m_raw)  > 0) m_raw <- m_raw[, common_genes, drop = FALSE]
if (ncol(snv_m) > 0) snv_m <- snv_m[, common_genes, drop = FALSE]
if (ncol(cnv_m) > 0) cnv_m <- cnv_m[, common_genes, drop = FALSE]

# — Same as the original: trim the graph by common genes first, then reorder membership accordingly —
keep_vids <- which(V(graph_comp)$name %in% common_genes)
graph_comp <- induced_subgraph(graph_comp, vids = keep_vids)

keep_names <- V(graph_comp)$name
old_mem    <- melanet_spg$membership
new_mem    <- old_mem[keep_names]
names(new_mem) <- keep_names
melanet_spg$membership <- new_mem

## ---------- 5) Parallel computation of community_map_list (same structure as original) -----
if (!dir.exists(dirname(out_json))) dir.create(dirname(out_json), recursive = TRUE, showWarnings = FALSE)

tic("build community_map")
community_ids <- sort(unique(melanet_spg$membership))  # Keep compatibility with original layout (NA values not explicitly removed)
#cl <- makeCluster(max(1L, detectCores() - 1L))
cl <- suppressWarnings(makeCluster(max(1L, detectCores() - 1L)))
clusterEvalQ(cl, {library(igraph); library(graphlayouts)})
clusterExport(cl, varlist = c("graph_comp","melanet_spg","e_raw","m_raw","snv_m","cnv_m","TOP_N","samples","stage_idx","edges_df"), envir = environment())

community_map_list <- parLapply(cl, community_ids, function(comm) {
  vids <- which(melanet_spg$membership == comm)
  subg <- induced_subgraph(graph_comp, vids = vids)
  
  # Use stress layout (same as original implementation)
  xy  <- tryCatch(layout_with_stress(subg) * 200, error = function(e) layout_nicely(subg))
  deg <- degree(subg,mode = "all")
  max_deg <- if (length(deg)) max(deg) else 0
  ##edge raw value
  ew    <- E(subg)$weight
  w_raw <- if (length(ew)) as.numeric(ew) else numeric(0)
  ##edge min-max noma;ize
  if (length(w_raw) && is.finite(min(w_raw, na.rm=TRUE)) && is.finite(max(w_raw, na.rm=TRUE)) &&
      min(w_raw, na.rm=TRUE) < max(w_raw, na.rm=TRUE)) {
    w_norm <- -1 + 2 * (w_raw - min(w_raw, na.rm=TRUE)) /
      (max(w_raw, na.rm=TRUE) - min(w_raw, na.rm=TRUE))
  } else {
    w_norm <- rep(0, length(w_raw))
  }
  #edge z-score
  sd_w <- suppressWarnings(sd(w_raw, na.rm=TRUE))
  if (length(w_raw) && is.finite(sd_w) && sd_w > 0) {
    w_z <- as.numeric(scale(w_raw))
  } else {
    w_z <- rep(0, length(w_raw))
  }
  
  build_edges <- function(sg, keep_ids = NULL) {
 
    ec <- ecount(sg)
    if (ec == 0L) return(list())
    
    out <- list()
  
    keep_nodes <- V(sg)$name
    

    rows <- which(edges_df$from %in% keep_nodes & edges_df$to %in% keep_nodes)
    if (length(rows) == 0) return(list())
    
    for (r in rows) {
     
      out[[length(out) + 1]] <- list(
        source = as.character(edges_df$from[r]),
        target = as.character(edges_df$to[r]),
        weight = if ("weight" %in% names(edges_df)) edges_df$weight[r] else NA_real_,
        w_raw  = if ("w_raw" %in% names(edges_df)) edges_df$w_raw[r] else edges_df$weight[r],
        w_norm = if ("w_norm" %in% names(edges_df)) edges_df$w_norm[r] else NA_real_,
        w_z    = if ("w_z" %in% names(edges_df)) edges_df$w_z[r] else NA_real_,
        interact_id = if ("interact" %in% names(edges_df)) as.character(edges_df$interact[r]) else 
          if ("interact_id" %in% names(edges_df)) as.character(edges_df$interact_id[r]) else NA_character_
      )
    }
    
  
    if (!is.null(keep_ids)) {
      out <- Filter(function(e) e$source %in% keep_ids && e$target %in% keep_ids, out)
    }
    
    out
  }
  
  
  ## Nodes
  nodes <- lapply(seq_len(vcount(subg)), function(i) {
    node_name <- V(subg)$name[i]
    gene <- sub("@.*", "", node_name)
    cellgroup <- ifelse(grepl("@", node_name),
                        sub(".*@", "", node_name),
                        NA)
    

    exp_norm <- if (ncol(e_raw) > 0 && gene %in% colnames(e_raw)) {              
      tmp <- e_raw[, gene]; rng <- range(tmp, na.rm = TRUE)
      if (is.finite(rng[1]) && is.finite(rng[2]) && rng[1] < rng[2]) -1 + 2*(tmp-rng[1])/(rng[2]-rng[1]) else rep(0, length(tmp))
    } else rep(NA_real_, length(samples))                                       
    
    mty_norm <- if (ncol(m_raw) > 0 && gene %in% colnames(m_raw)) {              
      tmp <- m_raw[, gene]; rng <- range(tmp, na.rm = TRUE)
      if (is.finite(rng[1]) && is.finite(rng[2]) && rng[1] < rng[2]) -1 + 2*(tmp-rng[1])/(rng[2]-rng[1]) else rep(0, length(tmp))
    } else rep(NA_real_, length(samples)) 
    
    snv_vals <- if (ncol(snv_m)>0 && gene %in% colnames(snv_m)) as.numeric(snv_m[, gene] > 0) else rep(NA_real_, length(samples))
    cnv_norm <- if (ncol(cnv_m)>0 && gene %in% colnames(cnv_m)) as.numeric(cnv_m[, gene]) else rep(NA_real_, length(samples))
    
    list(
      id        = node_name,   
      gene      = gene,        
      cellgroup = cellgroup,   
      #id        = gene,
      x         = xy[i,1],
      y         = xy[i,2],
      #degree    = deg[gene],
      degree    = deg[node_name],
      #exp_vals  = as.numeric(e_raw[, gene]),
      exp_vals  = if (ncol(e_raw)>0 && gene %in% colnames(e_raw)) as.numeric(e_raw[, gene]) else rep(NA_real_, length(samples)),
      mty_vals  = if (ncol(m_raw)>0 && gene %in% colnames(m_raw)) as.numeric(m_raw[, gene]) else rep(NA_real_, length(samples)),
      cnv_vals  = if (ncol(cnv_m)>0 && gene %in% colnames(cnv_m)) as.numeric(cnv_m[, gene]) else rep(NA_real_, length(samples)),
      
      exp_norm  = exp_norm,
      #exp_z     = if (gene %in% colnames(e_raw)) as.numeric(scale(e_raw[, gene])) else NA,
      #mty_vals  = as.numeric(m_raw[, gene]),
      exp_z  = if (ncol(e_raw) > 0 && gene %in% colnames(e_raw))  as.numeric(scale(e_raw[, gene])) else rep(NA_real_, length(samples)),  # 改5
      mty_norm  = mty_norm,
      #mty_z     = if (gene %in% colnames(m_raw)) as.numeric(scale(m_raw[, gene])) else NA,
      mty_z  = if (ncol(m_raw) > 0 && gene %in% colnames(m_raw))  as.numeric(scale(m_raw[, gene])) else rep(NA_real_, length(samples)),  # 改
      #cnv_vals  = as.numeric(cnv_m[, gene]),
      cnv_norm  = cnv_norm,
      #cnv_z     = if (gene %in% colnames(cnv_m)) as.numeric(scale(cnv_m[, gene])) else NA,
      cnv_z  = if (ncol(cnv_m) > 0 && gene %in% colnames(cnv_m))  as.numeric(scale(cnv_m[, gene])) else rep(NA_real_, length(samples)),  # 改7
      snv_vals  = snv_vals,
      stage_idx = as.integer(stage_idx)
    )
  })
  
  ## Edges
  
  ## ---------- Keep only Top-100 nodes within each community (same logic as original) ----------
  if (length(nodes) > TOP_N) {
    
    scores <- vapply(nodes, function(n) mean(n$exp_vals, na.rm = TRUE), numeric(1))
    ## Replace non-finite values with -Inf to prevent all-NA genes from entering Top100
    scores[!is.finite(scores)] <- -Inf
    
    ## === CHANGED: Defensive handling of the actual retained node count ===
    keep_n   <- min(length(scores), TOP_N)
    keep_idx <- order(scores, decreasing = TRUE)[seq_len(keep_n)]
    keep_ids <- vapply(nodes[keep_idx], `[[`, "", "id")
    nodes <- nodes[keep_idx] 
    edges <- build_edges(subg, keep_ids = keep_ids)
    max_deg <- max(vapply(nodes, `[[`, 0.0, "degree"))
  } else {
    # If the community size ≤ TOP_N, retain all edges (preserve original behavior)
    
    edges <- build_edges(subg)
  }
  
  list(comm = comm, max_deg = max_deg, nodes = nodes, edges = edges)
})

stopCluster(cl)
community_map_list <- Filter(Negate(is.null), community_map_list)
toc()
## ---------- 6) Write JSON output ----------
if (!length(community_map_list)) stop("null no community is output")

out_dir <- dirname(out_json)
if (!dir.exists(out_dir)) {
  dir.create(out_dir, recursive = TRUE, showWarnings = FALSE)
}

write_json(community_map_list, out_json, auto_unbox = TRUE, pretty = FALSE, na = "null")
cat(sprintf("✔ Done. Saved: %s\n", out_json))

#data <- fromJSON("cellchat_output.json")
#View(data)
