#!/usr/bin/env Rscript
## ------------------------------------------------------------------
## community_map_csv_only.R —— 使用 CSV（edges/nodes/megList）替代 igraph/communities
##   其余流程尽量保持原始结构（样本/基因对齐、induced_subgraph、并行、Top-N 等）。
##
## 用法：
##   Rscript community_map_csv_only.R \
##     <graph_edges.csv> <graph_nodes.csv> <megList_membership.csv> \
##     <expr.csv> <meth.csv> <snv.rds> <cnv.rds> [out.json]
##
## 要求：
##   - graph_edges.csv: 必含列 from,to（或 source,target），可选 weight
##   - graph_nodes.csv: 必含列 name（节点ID=基因名），其余列为可选属性
##   - megList_membership.csv: 必含列 gene, community（gene 与 nodes$name 对齐）
##   - expr.csv / meth.csv: 行=样本，列=基因；首列为行名
##   - snv.rds: 常见为（基因×样本）或（样本×基因），本脚本会 t() 成（样本×基因）
##   - cnv.rds: （样本×基因）
##   - out.json 默认 "uploads/community_map_top100.json"
## ------------------------------------------------------------------

suppressPackageStartupMessages({
  library(parallel)
  library(igraph)
  library(graphlayouts)
  library(jsonlite)
  library(tictoc)
})

TOP_N <- 100  # 每社区保留表达最高的 TOP_N 节点
KEEP_DIRECTED <- TRUE  # === NEW: 保留边方向（true=有向；false=无向） ===
## ---------- 0) 参数 ----------
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

## ---------- 1) 读取图（CSV）与社区（megList） ----------
edges_df <- read.csv(args["edges"], check.names = FALSE)
nodes_df <- read.csv(args["nodes"], check.names = FALSE)

# 支持 source/target 自动映射为 from/to
if (!all(c("from","to") %in% names(edges_df))) {
  if (all(c("source","target") %in% names(edges_df))) {
    names(edges_df)[match(c("source","target"), names(edges_df))] <- c("from","to")
  } else stop("edges CSV list：from, to（or source, target）")
}
if (!("name" %in% names(nodes_df))) stop("nodes CSV cols：name（nodeID/gene name）")
if (!("weight" %in% names(edges_df))) edges_df$weight <- 1

# 读 megList 社区信息
a_memb <- read.csv(args["memb"], check.names = FALSE)
if (!all(c("gene","community") %in% names(a_memb))) stop("megList CSV cols：gene, community")
mem_vec <- setNames(as.integer(a_memb$community), a_memb$gene)
rm(a_memb)

# 先重建整图（保持与原始结构一致，后续仍然用 induced_subgraph）
#graph_comp <- graph_from_data_frame(d = edges_df, vertices = nodes_df, directed = FALSE)
#if (is.null(E(graph_comp)$weight)) E(graph_comp)$weight <- 1


graph_comp <- graph_from_data_frame(
  d = edges_df,
  vertices = nodes_df,
  directed = KEEP_DIRECTED   # <-- 原来是 FALSE；现在改为使用开关，默认 TRUE
)
if (is.null(E(graph_comp)$weight)) E(graph_comp)$weight <- 1
if (KEEP_DIRECTED) {
  V(graph_comp)$degree_all <- degree(graph_comp, mode = "all")
  V(graph_comp)$degree_in  <- degree(graph_comp, mode = "in")
  V(graph_comp)$degree_out <- degree(graph_comp, mode = "out")
} else {
  V(graph_comp)$degree_all <- degree(graph_comp, mode = "all")
}
# 构造一个 communities 风格对象（只需 membership）
melanet_spg <- structure(list(
  membership = mem_vec,
  algorithm  = "csv",
  modularity = NA_real_,
  vcount     = vcount(graph_comp)
), class = "communities")

## ---------- 2) 读取组学矩阵 ----------
#e_raw <- read.csv(args["expr"], row.names = 1, check.names = FALSE)
#m_raw <- read.csv(args["meth"], row.names = 1, check.names = FALSE)

#snv_m<-read.csv(args["snv"], row.names = 1, check.names = FALSE)
#cnv_m<-read.csv(args["cnv"], row.names = 1, check.names = FALSE) 
#snv_m <- t(readRDS(args["snv"]))
#cnv_m <- readRDS(args["cnv"])

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

  # 猜列：优先匹配名；否则取前两列
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
m_raw <- read_or_empty(args["meth"])
snv_m <- read_or_empty(args["snv"])
cnv_m <- read_or_empty(args["cnv"])
stage_v <- read_stage_vector(args["stage"]) 
# 至少有一个非空
#if (ncol(e_raw)==0 && ncol(m_raw)==0 && ncol(snv_m)==0 && ncol(cnv_m)==0) {
#  stop("At least one of expr/meth/snv/cnv must be provided.")
#}
if (ncol(e_raw)==0 && ncol(m_raw)==0 && ncol(snv_m)==0 && ncol(cnv_m)==0 && is.null(stage_v)) {
  stop("At least one of expr/meth/snv/cnv/stage must be provided.")
}



## ---------- 3) 同步样本 ----------
#samples <- Reduce(intersect, list(rownames(e_raw), rownames(m_raw), rownames(snv_m), rownames(cnv_m)))
#if (!length(samples)) stop("please check the common sample name")
rn_list <- list()
if (nrow(e_raw)  > 0) rn_list[[length(rn_list)+1]] <- rownames(e_raw)
if (nrow(m_raw)  > 0) rn_list[[length(rn_list)+1]] <- rownames(m_raw)
if (nrow(snv_m) > 0) rn_list[[length(rn_list)+1]] <- rownames(snv_m)
if (nrow(cnv_m) > 0) rn_list[[length(rn_list)+1]] <- rownames(cnv_m)
if (!is.null(stage_v)) rn_list[[length(rn_list)+1]] <- names(stage_v)   # ★ 关键新增
if (!length(rn_list)) stop("no sample info provided at all")
samples <- Reduce(intersect, rn_list)
if (!length(samples)) stop("please check the common sample name (no overlap among provided matrices)")
stage_idx <- NULL
if (!is.null(stage_v)) {
  stage_idx <- unname(stage_v[samples])  # 可能有 NA
  ord <- order(stage_idx, na.last = TRUE)  # index 升序；NA 在后
  samples <- samples[ord]

  # 按新顺序重排矩阵
  if (nrow(e_raw)  > 0) e_raw  <- e_raw[samples,,drop=FALSE]
  if (nrow(m_raw)  > 0) m_raw  <- m_raw[samples,,drop=FALSE]
  if (nrow(snv_m) > 0) snv_m  <- snv_m[samples,,drop=FALSE]
  if (nrow(cnv_m) > 0) cnv_m  <- cnv_m[samples,,drop=FALSE]

  # 与 samples 对齐后的 stage 向量
  stage_idx <- unname(stage_v[samples])
} else {
  # 没有 stage 文件时，用全 NA 的占位（便于前端透明显示）
  stage_idx <- rep(NA_integer_, length(samples))
}

#e_raw <- e_raw[samples,,drop=FALSE]
#m_raw <- m_raw[samples,,drop=FALSE]
#snv_m <- snv_m[samples,,drop=FALSE]
#cnv_m <- cnv_m[samples,,drop=FALSE]
if (nrow(e_raw)  > 0) e_raw <- e_raw[samples,,drop=FALSE]
if (nrow(m_raw)  > 0) m_raw <- m_raw[samples,,drop=FALSE]
if (nrow(snv_m) > 0) snv_m <- snv_m[samples,,drop=FALSE]
if (nrow(cnv_m) > 0) cnv_m <- cnv_m[samples,,drop=FALSE]

## ---------- 4) 选表达最高 TOP_N 基因（先取公共基因） ----------
#common_genes <- Reduce(intersect, list(colnames(e_raw), colnames(m_raw), colnames(snv_m), colnames(cnv_m)))
#if (!length(common_genes)) stop("no common gene")
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

# —— 与原版一致：先用公共基因裁剪图，再重排 membership ——
keep_vids <- which(V(graph_comp)$name %in% common_genes)
graph_comp <- induced_subgraph(graph_comp, vids = keep_vids)

keep_names <- V(graph_comp)$name
old_mem    <- melanet_spg$membership
new_mem    <- old_mem[keep_names]
names(new_mem) <- keep_names
melanet_spg$membership <- new_mem

## ---------- 5) 并行计算 community_map_list（保持原结构） ----------
if (!dir.exists(dirname(out_json))) dir.create(dirname(out_json), recursive = TRUE, showWarnings = FALSE)

tic("build community_map")
community_ids <- sort(unique(melanet_spg$membership))  # 与原始结构一致（未显式去 NA）
cl <- makeCluster(max(1L, detectCores() - 1L))
clusterEvalQ(cl, {library(igraph); library(graphlayouts)})
clusterExport(cl, varlist = c("graph_comp","melanet_spg","e_raw","m_raw","snv_m","cnv_m","TOP_N","samples","stage_idx"), envir = environment())

community_map_list <- parLapply(cl, community_ids, function(comm) {
  vids <- which(melanet_spg$membership == comm)
  subg <- induced_subgraph(graph_comp, vids = vids)
  
  ## Stress 布局
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
  #include all information of the edges
  build_edges <- function(sg, keep_ids = NULL) {
  ec <- ecount(sg)
  if (ec == 0L) return(list())
  out <- vector("list", ec)
  for (j in seq_len(ec)) {
    e <- ends(sg, j)
    s <- V(sg)[e[1]]$name
    t <- V(sg)[e[2]]$name
    if (!is.null(keep_ids) && !(s %in% keep_ids && t %in% keep_ids)) next
    out[[j]] <- list(
      source = s,
      target = t,
      weight = w_raw[j],   # 保持兼容
      w_raw  = w_raw[j],
      w_norm = w_norm[j],
      w_z    = w_z[j]
    )
  }
  Filter(Negate(is.null), out)
} 
  ## 节点
  nodes <- lapply(seq_len(vcount(subg)), function(i) {
    gene <- V(subg)$name[i]
    
    #exp_norm <- if (gene %in% colnames(e_raw)) {
     # tmp <- e_raw[, gene]; rng <- range(tmp, na.rm = TRUE)
     # if (is.finite(rng[1]) && is.finite(rng[2]) && rng[1] < rng[2]) -1 + 2*(tmp-rng[1])/(rng[2]-rng[1]) else rep(0, length(tmp))
   # } else NA
   # mty_norm <- if (gene %in% colnames(m_raw)) {
   #   tmp <- m_raw[, gene]; rng <- range(tmp, na.rm = TRUE)
   #   if (is.finite(rng[1]) && is.finite(rng[2]) && rng[1] < rng[2]) -1 + 2*(tmp-rng[1])/(rng[2]-rng[1]) else rep(0, length(tmp))
   # } else NA
    #snv_vals <- if (gene %in% colnames(snv_m)) as.numeric(snv_m[, gene] > 0) else NA
    #cnv_norm <- if (gene %in% colnames(cnv_m)) as.numeric(cnv_m[, gene]) else NA
   exp_norm <- if (ncol(e_raw) > 0 && gene %in% colnames(e_raw)) {              # 改1：加 ncol(...) 检查
    tmp <- e_raw[, gene]; rng <- range(tmp, na.rm = TRUE)
    if (is.finite(rng[1]) && is.finite(rng[2]) && rng[1] < rng[2]) -1 + 2*(tmp-rng[1])/(rng[2]-rng[1]) else rep(0, length(tmp))
  } else rep(NA_real_, length(samples))                                        # 改2：标量 NA -> 向量 NA

   mty_norm <- if (ncol(m_raw) > 0 && gene %in% colnames(m_raw)) {              # 改3：加 ncol(...) 检查
    tmp <- m_raw[, gene]; rng <- range(tmp, na.rm = TRUE)
    if (is.finite(rng[1]) && is.finite(rng[2]) && rng[1] < rng[2]) -1 + 2*(tmp-rng[1])/(rng[2]-rng[1]) else rep(0, length(tmp))
  } else rep(NA_real_, length(samples)) 
  
     snv_vals <- if (ncol(snv_m)>0 && gene %in% colnames(snv_m)) as.numeric(snv_m[, gene] > 0) else rep(NA_real_, length(samples))
     cnv_norm <- if (ncol(cnv_m)>0 && gene %in% colnames(cnv_m)) as.numeric(cnv_m[, gene]) else rep(NA_real_, length(samples))

    list(
      id        = gene,
      x         = xy[i,1],
      y         = xy[i,2],
      degree    = deg[gene],
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
  
  ## 边
  #edges <- lapply(seq_len(ecount(subg)), function(j) {
   # e <- ends(subg, j)
   # list(source = V(subg)[e[1]]$name,
   #      target = V(subg)[e[2]]$name,
   #      weight = E(subg)$weight[j])
 # })
    #edges <- build_edges(subg)
  
  ## ---------- ③ 仅保留本社区 Top-100 节点（与原逻辑一致） ----------
  if (length(nodes) > TOP_N) {
    
    scores <- vapply(nodes, function(n) mean(n$exp_vals, na.rm = TRUE), numeric(1))
    ## === NEW: 非有限值设为 -Inf，避免全 NA 的基因进入 Top100 ===
    scores[!is.finite(scores)] <- -Inf

    ## === CHANGED: 防御性地计算实际保留数量 ===
    keep_n   <- min(length(scores), TOP_N)
    keep_idx <- order(scores, decreasing = TRUE)[seq_len(keep_n)]
    keep_ids <- vapply(nodes[keep_idx], `[[`, "", "id")

    nodes <- nodes[keep_idx] 
 #   edges_all <- lapply(seq_len(ecount(subg)), function(j) {
 #     e <- ends(subg, j)
 #     list(source = V(subg)[e[1]]$name,
 #          target = V(subg)[e[2]]$name,
 #          weight = E(subg)$weight[j])
 #   })


    #edges <- Filter(function(e) e$source %in% keep_ids && e$target %in% keep_ids, edges_all)
    edges <- build_edges(subg, keep_ids = keep_ids)
    
    max_deg <- max(vapply(nodes, `[[`, 0.0, "degree"))
  } else {
    ## 节点少于等于 TOP_N 时，保留全部边（保持原行为）
   # edges <- lapply(seq_len(ecount(subg)), function(j) {
     # e <- ends(subg, j)
     # list(source = V(subg)[e[1]]$name,
     #      target = V(subg)[e[2]]$name,
    #       weight = E(subg)$weight[j])
   # })
   edges <- build_edges(subg)
  }
  
  list(comm = comm, max_deg = max_deg, nodes = nodes, edges = edges)
})

stopCluster(cl)
community_map_list <- Filter(Negate(is.null), community_map_list)
toc()
## ---------- 6) 写出 JSON ----------
if (!length(community_map_list)) stop("null no community is output")

out_dir <- dirname(out_json)
if (!dir.exists(out_dir)) {
  dir.create(out_dir, recursive = TRUE, showWarnings = FALSE)
}

write_json(community_map_list, out_json, auto_unbox = TRUE, pretty = FALSE, na = "null")
cat(sprintf("✔ Done. Saved: %s\n", out_json))

