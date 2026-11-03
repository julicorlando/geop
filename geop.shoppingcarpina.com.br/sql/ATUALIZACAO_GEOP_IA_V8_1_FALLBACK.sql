-- GEOP V8.1 — GEOP IA CHAT
-- Fallback manual de banco.
-- Preferência: use public/atualizar_geop_ia_v8_1.php.

CREATE TABLE IF NOT EXISTS ia_chat_conversas (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 usuario_id INT NOT NULL,
 titulo VARCHAR(190) NOT NULL DEFAULT 'Nova conversa',
 contexto_tipo VARCHAR(40) NULL,
 contexto_id BIGINT NULL,
 arquivada TINYINT(1) NOT NULL DEFAULT 0,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 KEY idx_ia_chat_conv_usuario(usuario_id,arquivada,atualizado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ia_chat_mensagens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 conversa_id BIGINT UNSIGNED NOT NULL,
 usuario_id INT NULL,
 papel ENUM('USER','ASSISTANT','SYSTEM') NOT NULL,
 conteudo LONGTEXT NOT NULL,
 fontes_json LONGTEXT NULL,
 modelo VARCHAR(100) NULL,
 input_tokens INT UNSIGNED NULL,
 output_tokens INT UNSIGNED NULL,
 latencia_ms INT UNSIGNED NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 KEY idx_ia_chat_msg_conversa(conversa_id,id),
 KEY idx_ia_chat_msg_usuario(usuario_id,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ia_chat_execucoes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 conversa_id BIGINT UNSIGNED NOT NULL,
 mensagem_usuario_id BIGINT UNSIGNED NULL,
 usuario_id INT NOT NULL,
 ferramenta VARCHAR(100) NOT NULL,
 argumentos_json LONGTEXT NULL,
 resultado_resumo VARCHAR(500) NULL,
 linhas_retornadas INT UNSIGNED NOT NULL DEFAULT 0,
 duracao_ms INT UNSIGNED NULL,
 status ENUM('OK','ERRO','BLOQUEADA') NOT NULL DEFAULT 'OK',
 erro VARCHAR(500) NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 KEY idx_ia_exec_conversa(conversa_id,criado_em),
 KEY idx_ia_exec_usuario(usuario_id,criado_em),
 KEY idx_ia_exec_ferramenta(ferramenta,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO sistema_configuracoes(chave,valor,tipo,descricao) VALUES
('ai_chat_enabled','0','BOOL','Ativar o GEOP IA Chat após configurar a chave do provedor.'),
('ai_chat_model','gpt-5.6','STRING','Modelo usado pelo GEOP IA.'),
('ai_chat_max_tool_calls','8','INT','Máximo de chamadas de ferramentas por pergunta.'),
('ai_chat_max_rows','50','INT','Máximo de registros retornados por ferramenta.'),
('ai_chat_history_messages','12','INT','Quantidade de mensagens anteriores enviadas como contexto.'),
('ai_chat_daily_limit','100','INT','Limite diário de perguntas por usuário.');
