-- =============================================================
-- PRISMA-SLR: Avaliação de qualidade/risco de viés (CASP/JBI)
--             + Formulários de extração PICO
-- =============================================================

USE prisma_slr;

-- Instrumento de avaliação escolhido pelo projeto (CASP, JBI ou personalizado)
CREATE TABLE IF NOT EXISTS quality_instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quality_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrument_id INT NOT NULL,
    question TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (instrument_id) REFERENCES quality_instruments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quality_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    question_id INT NOT NULL,
    answer ENUM('yes','no','unclear','na') NOT NULL,
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_article_question (article_id, question_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quality_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extração de dados no framework PICO (um registro por artigo incluído)
CREATE TABLE IF NOT EXISTS pico_extractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL UNIQUE,
    population TEXT,
    intervention TEXT,
    comparison TEXT,
    outcome TEXT,
    context TEXT COMMENT 'PICOC: contexto (opcional)',
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
