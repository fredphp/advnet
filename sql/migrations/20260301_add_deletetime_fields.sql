-- =====================================================
-- Migration: Add deletetime field for soft delete support
-- Date: 2026-03-01
-- Description: This migration adds deletetime field to tables
--              that use SoftDelete trait in their models
-- =====================================================

-- Check if advn_video table exists and add deletetime field
-- This fixes the error: Unknown column 'advn_video.deletetime' in 'where clause'

-- Add deletetime field to advn_video table (if table exists)
-- Run this SQL on your database server

-- For advn_video table:
ALTER TABLE `advn_video` ADD COLUMN `deletetime` bigint(30) DEFAULT NULL COMMENT '删除时间' AFTER `updatetime`;
ALTER TABLE `advn_video` ADD INDEX `idx_deletetime` (`deletetime`);

-- Note: If you have other tables using SoftDelete trait, add similar fields:
-- ALTER TABLE `your_table_name` ADD COLUMN `deletetime` bigint(30) DEFAULT NULL COMMENT '删除时间';
-- ALTER TABLE `your_table_name` ADD INDEX `idx_deletetime` (`deletetime`);

-- =====================================================
-- To execute this migration, run:
-- mysql -u username -p database_name < sql/migrations/20260301_add_deletetime_fields.sql
-- Or execute the ALTER statements directly in your MySQL client
-- =====================================================
