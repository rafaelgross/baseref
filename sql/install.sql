-- =============================================================
-- BaseRef: Complete Database Installation Schema
-- Supports PRISMA 2020 Systematic Literature Reviews
-- Compatible with MySQL 8.0+ and MariaDB 10.4+ (tested on MariaDB 10.11)
-- =============================================================

CREATE DATABASE IF NOT EXISTS prisma_slr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prisma_slr;

-- -------------------------------------------------------------
-- 1. Users table (Google OAuth 2.0 accounts)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    picture TEXT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    geo_country CHAR(2) NULL,
    geo_country_name VARCHAR(80) NULL,
    geo_region VARCHAR(100) NULL,
    geo_city VARCHAR(100) NULL,
    geo_updated_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_google_id (google_id),
    UNIQUE KEY uk_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. AI suggestion usage log (rate-limiting)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_day (user_id, used_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Projects table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL DEFAULT NULL COMMENT 'Project owner; NULL for legacy projects',
    title VARCHAR(500) NOT NULL,
    description TEXT,
    objective TEXT,
    inclusion_criteria TEXT,
    exclusion_criteria TEXT,
    search_period_start YEAR,
    search_period_end YEAR,
    screening_mode ENUM('single', 'dual') NOT NULL DEFAULT 'single',
    status ENUM('active', 'completed', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Project Members (Multi-reviewer collaboration)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('reviewer', 'support', 'owner') NOT NULL DEFAULT 'reviewer',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_member (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. Search sources (imported files: Scopus, WoS, PubMed, etc.)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS search_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'e.g. Scopus, Web of Science, PubMed',
    source_type ENUM('scopus', 'wos', 'pubmed', 'embase', 'other') DEFAULT 'other',
    search_string TEXT,
    search_date DATE,
    file_name VARCHAR(255),
    total_imported INT DEFAULT 0,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. Normalized authors
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(500) NOT NULL,
    normalized_name VARCHAR(500),
    orcid VARCHAR(50),
    UNIQUE KEY uk_normalized (normalized_name(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. Bibliographic articles / records
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    source_id INT NULL,
    source_type ENUM('scopus', 'wos', 'pubmed', 'embase', 'other') DEFAULT 'other',
    source_key VARCHAR(255) COMMENT 'Original key / BibTeX citekey',

    -- Core metadata
    title TEXT NOT NULL,
    abstract TEXT,
    year INT,
    document_type VARCHAR(100),
    language VARCHAR(20) DEFAULT 'English',

    -- Publication venue
    journal VARCHAR(500),
    volume VARCHAR(50),
    issue VARCHAR(50),
    pages VARCHAR(100),
    article_number VARCHAR(100),
    issn VARCHAR(30),
    eissn VARCHAR(30),
    isbn VARCHAR(200),

    -- Identifiers
    doi VARCHAR(255),
    url TEXT,
    eid VARCHAR(150) COMMENT 'Scopus EID',
    wos_id VARCHAR(150) COMMENT 'WoS UT (Unique Tag)',
    pubmed_id VARCHAR(50),

    -- Publisher & Citations
    publisher VARCHAR(500),
    cited_by INT DEFAULT 0,
    open_access BOOLEAN DEFAULT FALSE,

    -- Screening status in workflow
    status ENUM('imported', 'duplicate', 'screened', 'eligible', 'included', 'excluded') DEFAULT 'imported',
    is_duplicate BOOLEAN DEFAULT FALSE,
    duplicate_of_id INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (source_id) REFERENCES search_sources(id) ON DELETE SET NULL,
    FOREIGN KEY (duplicate_of_id) REFERENCES articles(id) ON DELETE SET NULL,
    INDEX idx_project_status (project_id, status),
    INDEX idx_doi (doi(100)),
    INDEX idx_year (year),
    FULLTEXT INDEX ft_title_abstract (title, abstract)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. Article <-> Author relationship
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS article_authors (
    article_id INT NOT NULL,
    author_id INT NOT NULL,
    author_order INT NOT NULL DEFAULT 1,
    is_corresponding BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (article_id, author_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. Keywords and article-keyword association
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    keyword_type ENUM('author', 'index', 'mesh', 'plus') DEFAULT 'author',
    UNIQUE KEY uk_keyword_type (keyword, keyword_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_keywords (
    article_id INT NOT NULL,
    keyword_id INT NOT NULL,
    PRIMARY KEY (article_id, keyword_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. Affiliations and article-affiliation association
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS affiliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution VARCHAR(500) NOT NULL,
    country VARCHAR(100),
    city VARCHAR(100),
    country_iso CHAR(2),
    INDEX idx_institution (institution(255)),
    INDEX idx_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_affiliations (
    article_id INT NOT NULL,
    affiliation_id INT NOT NULL,
    PRIMARY KEY (article_id, affiliation_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliation_id) REFERENCES affiliations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. Duplicates detection and resolution
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS duplicates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    article_id_1 INT NOT NULL,
    article_id_2 INT NOT NULL,
    similarity_score DECIMAL(5, 4) COMMENT '0.0000 to 1.0000',
    match_method ENUM('exact_doi', 'normalized_title', 'levenshtein', 'title_year') NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id_1) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id_2) REFERENCES articles(id) ON DELETE CASCADE,
    UNIQUE KEY uk_articles_dup (article_id_1, article_id_2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 12. Exclusion reasons (customizable per project)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exclusion_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase ENUM('screening', 'eligibility') NOT NULL,
    reason VARCHAR(500) NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    not_retrieved TINYINT(1) NULL DEFAULT NULL COMMENT 'PRISMA 2020: 1 = count as Reports not retrieved; NULL = auto-detect by text',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 13. Screening decisions (single and multi-reviewer)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS screening_decisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    reviewer_id INT NULL DEFAULT NULL,
    is_consensus TINYINT(1) NOT NULL DEFAULT 0,
    phase ENUM('screening', 'eligibility') NOT NULL,
    decision ENUM('include', 'exclude', 'uncertain') NOT NULL,
    reason_id INT NULL DEFAULT NULL,
    notes TEXT,
    decided_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rating TINYINT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_article_phase_rev (article_id, phase, reviewer_id, is_consensus),
    KEY idx_reviewer (reviewer_id),
    KEY idx_reason (reason_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (reason_id) REFERENCES exclusion_reasons(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 14. PRISMA 2020 Checklist (27 items)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prisma_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    item_number INT NOT NULL,
    section VARCHAR(100),
    item_text TEXT,
    response TEXT,
    comment TEXT,
    page_reference VARCHAR(100),
    completed BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_project_item (project_id, item_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 15. Quality and Risk-of-Bias instruments (CASP / JBI / Custom)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quality_instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quality_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrument_id INT NOT NULL,
    question TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (instrument_id) REFERENCES quality_instruments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quality_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    question_id INT NOT NULL,
    answer ENUM('yes', 'no', 'unclear', 'na') NOT NULL,
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_article_question (article_id, question_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quality_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 16. PICO Data Extraction
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pico_extractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL UNIQUE,
    population TEXT,
    intervention TEXT,
    comparison TEXT,
    outcome TEXT,
    context TEXT COMMENT 'PICOC: optional context',
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 17. System Feedback
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,
    page_context VARCHAR(100) NOT NULL DEFAULT 'dashboard',
    rating TINYINT UNSIGNED NOT NULL,
    suggestion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_user (user_id),
    INDEX idx_feedback_project (project_id),
    INDEX idx_feedback_created (created_at),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Views
-- =============================================================

-- View: PRISMA flow counts per project
CREATE OR REPLACE VIEW vw_prisma_counts AS
SELECT
    p.id AS project_id,
    p.title AS project_title,

    -- Identification: total imported
    (SELECT COUNT(*) FROM articles a WHERE a.project_id = p.id) AS total_identified,

    -- By source
    (SELECT COUNT(*) FROM articles a
     INNER JOIN search_sources ss ON a.source_id = ss.id
     WHERE a.project_id = p.id AND ss.source_type = 'scopus') AS from_scopus,

    (SELECT COUNT(*) FROM articles a
     INNER JOIN search_sources ss ON a.source_id = ss.id
     WHERE a.project_id = p.id AND ss.source_type = 'wos') AS from_wos,

    -- Duplicates removed
    (SELECT COUNT(DISTINCT article_id_2) FROM duplicates d
     WHERE d.project_id = p.id AND d.status = 'confirmed') AS duplicates_removed,

    -- Screened (title/abstract)
    (SELECT COUNT(*) FROM articles a WHERE a.project_id = p.id
     AND a.status IN ('screened', 'eligible', 'included', 'excluded')
     AND a.is_duplicate = FALSE) AS screened,

    -- Excluded in screening
    (SELECT COUNT(DISTINCT sd.article_id) FROM screening_decisions sd
     INNER JOIN articles a ON sd.article_id = a.id
     WHERE a.project_id = p.id AND sd.phase = 'screening'
     AND sd.decision = 'exclude' AND a.status = 'excluded') AS excluded_screening,

    -- Full-text assessed
    (SELECT COUNT(DISTINCT sd.article_id) FROM screening_decisions sd
     INNER JOIN articles a ON sd.article_id = a.id
     WHERE a.project_id = p.id AND sd.phase = 'eligibility') AS assessed_eligibility,

    -- Excluded in eligibility
    (SELECT COUNT(DISTINCT sd.article_id) FROM screening_decisions sd
     INNER JOIN articles a ON sd.article_id = a.id
     WHERE a.project_id = p.id AND sd.phase = 'eligibility'
     AND sd.decision = 'exclude' AND a.status = 'excluded') AS excluded_eligibility,

    -- Final included
    (SELECT COUNT(*) FROM articles a WHERE a.project_id = p.id
     AND a.status = 'included') AS included

FROM projects p;

-- View: General project stats
CREATE OR REPLACE VIEW vw_project_stats AS
SELECT
    p.id AS project_id,
    COUNT(DISTINCT ss.id) AS total_sources,
    COUNT(DISTINCT a.id) AS total_articles,
    COUNT(DISTINCT CASE WHEN a.is_duplicate = TRUE THEN a.id END) AS total_duplicates,
    COUNT(DISTINCT CASE WHEN a.status = 'screened' AND a.is_duplicate = FALSE THEN a.id END) AS total_screened,
    COUNT(DISTINCT CASE WHEN a.status = 'included' THEN a.id END) AS total_included,
    COUNT(DISTINCT CASE WHEN a.status = 'excluded' THEN a.id END) AS total_excluded
FROM projects p
LEFT JOIN search_sources ss ON ss.project_id = p.id
LEFT JOIN articles a ON a.project_id = p.id
GROUP BY p.id;
