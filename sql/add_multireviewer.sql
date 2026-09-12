-- =============================================================
-- PRISMA-SLR: Triagem multirrevisor com kappa de Cohen
-- Execute após schema.sql + add_users_table.sql
-- =============================================================

USE prisma_slr;

-- Membros do projeto (revisores convidados além do dono)
CREATE TABLE IF NOT EXISTS project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('reviewer','owner') DEFAULT 'reviewer',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_member (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modo de triagem do projeto: single (1 revisor) ou dual (dupla-cega)
ALTER TABLE projects
  ADD COLUMN screening_mode ENUM('single','dual') NOT NULL DEFAULT 'single';

-- Decisões passam a ser por revisor; is_consensus marca a resolução de conflito
ALTER TABLE screening_decisions
  ADD COLUMN reviewer_id INT NULL DEFAULT NULL AFTER article_id,
  ADD COLUMN is_consensus TINYINT(1) NOT NULL DEFAULT 0 AFTER reviewer_id,
  ADD INDEX idx_reviewer (reviewer_id);

ALTER TABLE screening_decisions
  DROP INDEX uk_article_phase,
  ADD UNIQUE KEY uk_article_phase_rev (article_id, phase, reviewer_id, is_consensus);
