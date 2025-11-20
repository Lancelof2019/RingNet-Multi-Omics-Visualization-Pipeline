
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


library(parallel)
library(igraph)
library(graphlayouts)
library(jsonlite)
library(tictoc)


setwd("/users/zhanglia/single_cell_network/test_cell/")
cwd <- getwd()
expr<-read.csv(paste0(cwd,"/data/", "example_gene_expression.csv"))
myth<-read.csv(paste0(cwd,"/data/", "example_methylation.csv"))
snv<-read.csv(paste0(cwd,"/data/", "example_snv.csv"))
cnv<-read.csv(paste0(cwd,"/data/", "example_cnv.csv"))



samples <- Reduce(intersect, list(rownames(expr), rownames(myth), rownames(snv), rownames(cnv)))
expr_overlap <- expr[samples, , drop = FALSE]
myth_overlap <- myth[samples, , drop = FALSE]
snv_overlap  <- snv[samples,  , drop = FALSE]
cnv_overlap  <- cnv[samples,  , drop = FALSE]

clean_bad_cols <- function(df) {
  df <- as.data.frame(df)
  
  # 只看数值列
  num_idx <- sapply(df, is.numeric)
  X <- as.matrix(df[, num_idx, drop = FALSE])
  
  # 1. 含 NA / NaN 的列
  cols_with_na <- colSums(is.na(X) | is.nan(X)) > 0
  
  # 2. 全是 0 的列
  cols_all_zero <- colSums(X != 0, na.rm = TRUE) == 0
  
  # 3. 方差为 0 的列
  cols_sd_zero <- apply(X, 2, sd, na.rm = TRUE) == 0
  
  # 4. 需要删除的列
  cols_bad <- cols_with_na | cols_all_zero | cols_sd_zero
  bad_names <- colnames(X)[cols_bad]
  
  cat("需要删除的列数:", length(bad_names), "\n")
  if (length(bad_names) > 0) {
    cat("前几个要删的列名:\n")
    print(head(bad_names))
  }
  

  df_clean <- df[, !(names(df) %in% bad_names), drop = FALSE]
  
  return(df_clean)
}

expr_clean <- clean_bad_cols(expr_overlap)
myth_clean<-clean_bad_cols(myth_overlap)
snv_clean<-clean_bad_cols(snv_overlap)
cnv_clean<-clean_bad_cols(cnv_overlap)


scale_omics <- function(df) {
  df <- as.data.frame(df)
  rn <- rownames(df)
  num_idx <- sapply(df, is.numeric)
  mat_num <- as.matrix(df[, num_idx, drop = FALSE])
  df[, num_idx] <- scale(mat_num)
  rownames(df) <- rn
  return(df)
}


expr_s <- scale_omics(expr_clean)
myth_s <- scale_omics(myth_clean)
snv_s  <- scale_omics(snv_clean)
cnv_s  <- scale_omics(cnv_clean)


linear_kernel <- function(X, as_df = TRUE) {
  X <- as.matrix(X)
  K <- tcrossprod(X)
  
  rn <- rownames(X)
  if (!is.null(rn)) {
    rownames(K) <- rn
    colnames(K) <- rn
  }
  
  if (as_df) K <- as.data.frame(K)
  return(K)
}


#Frobenius 
K_expr <- linear_kernel(expr_s)
K_myth<-linear_kernel(myth_s)
K_snv<-linear_kernel(snv_s)
K_cnv<-linear_kernel(cnv_s)

normalize_kernel_fro <- function(K) {
  K <- as.matrix(K)
  K / sqrt(sum(K^2))
}



K_expr_norm <- normalize_kernel_fro(K_expr)
K_myth_norm<-normalize_kernel_fro(K_myth)
K_snv_norm<-normalize_kernel_fro(K_snv)
K_cnv_norm<-normalize_kernel_fro(K_cnv)


#write.csv(expr_s,"scale_expr_s.csv")

###########################

########################

write.csv(K_expr_norm,"K_expr.csv")

write.csv(K_myth_norm,"K_myth.csv")

write.csv(K_snv_norm,"K_snv.csv")

write.csv(K_cnv_norm,"K_cnvr.csv")


w_expr <- 0.3
w_myth <- 0.3
w_snv  <- 0.2
w_cnv  <- 0.2

K_all <- w_expr * K_expr_norm +
  w_myth * K_myth_norm +
  w_snv  * K_snv_norm  +
  w_cnv  * K_cnv_norm

K_mat <- as.matrix(K_all)

rn <- rownames(K_mat)   # 样本名

n <- nrow(K_mat)

# 所有 (i, j) 组合：1..n × 1..n
idx <- expand.grid(
  i = 1:n,
  j = 1:n
)

edges_df <- data.frame(
  from   = rn[idx$i],                       
  to     = rn[idx$j],                       
  weight = K_mat[cbind(idx$i, idx$j)]       
)

write.csv(
  edges_df,
  file = paste0(cwd, "/data/", "patients_graph.csv"),
  row.names = FALSE   
)
