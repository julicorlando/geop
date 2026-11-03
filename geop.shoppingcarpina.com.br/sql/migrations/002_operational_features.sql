-- GEOP 002: estruturas operacionais e 18 módulos
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, codigo_patrimonial VARCHAR(80) NOT NULL,
 qr_token_hash CHAR(64) NOT NULL, categoria_id INT NULL, marca VARCHAR(100), modelo VARCHAR(100),
 numero_serie VARCHAR(120), local_id INT NULL, unidade VARCHAR(100), fornecedor_id BIGINT NULL,
 garantia_ate DATE NULL, adquirido_em DATE NULL, estado_conservacao VARCHAR(40), latitude DECIMAL(10,7),
 longitude DECIMAL(10,7), dados_externos JSON NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_ativo_codigo(codigo_patrimonial), UNIQUE KEY uq_ativo_qr(qr_token_hash), KEY idx_ativo_local(local_id), KEY idx_ativo_categoria(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativo_arquivos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(20) NOT NULL,
 caminho VARCHAR(255) NOT NULL, mime_type VARCHAR(100), sha256 CHAR(64), criado_por INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_arquivo_ativo(ativo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checklist_modelos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(160) NOT NULL, tipo_servico VARCHAR(60),
 categoria_id INT NULL, local_id INT NULL, equipe VARCHAR(100), tipo_manutencao VARCHAR(60),
 ativo TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS checklist_itens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, modelo_id BIGINT UNSIGNED NOT NULL, rotulo VARCHAR(255) NOT NULL,
 tipo ENUM('SIM_NAO','TEXTO','NUMERO','FOTO','ASSINATURA','SELECAO','DATA','MEDIDOR') NOT NULL,
 opcoes JSON NULL, obrigatorio TINYINT(1) NOT NULL DEFAULT 0, ordem SMALLINT NOT NULL DEFAULT 0, KEY idx_item_modelo(modelo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS checklist_respostas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, item_id BIGINT UNSIGNED NOT NULL,
 usuario_id INT NULL, valor_texto TEXT NULL, valor_numero DECIMAL(18,4) NULL, arquivo_id BIGINT NULL,
 respondido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_check_resposta(ordem_servico_id,item_id), KEY idx_check_usuario(usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aprovacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(40) NOT NULL, entidade VARCHAR(60) NOT NULL,
 entidade_id BIGINT NOT NULL, solicitante_id INT NOT NULL, aprovador_id INT NULL,
 status ENUM('PENDENTE','APROVADA','REPROVADA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
 observacao TEXT NULL, decidido_em DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_aprovacao_fila(status,tipo,created_at), KEY idx_aprovacao_entidade(entidade,entidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS aprovacao_historico (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, aprovacao_id BIGINT UNSIGNED NOT NULL, usuario_id INT NULL,
 status_anterior VARCHAR(20), status_novo VARCHAR(20) NOT NULL, observacao TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_aprov_hist(aprovacao_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conhecimento_artigos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, titulo VARCHAR(200) NOT NULL, categoria_id INT NULL, conteudo MEDIUMTEXT NOT NULL,
 links JSON NULL, versao VARCHAR(30) NOT NULL DEFAULT '1.0', responsavel_id INT NULL, revisar_em DATE NULL,
 status ENUM('RASCUNHO','PUBLICADO','INATIVO') NOT NULL DEFAULT 'RASCUNHO',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FULLTEXT KEY ft_artigo(titulo,conteudo), KEY idx_artigo_categoria(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipe_disponibilidade (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
 tipo ENUM('JORNADA','ESCALA','FERIAS','FOLGA','AUSENCIA','AFASTAMENTO','PLANTAO') NOT NULL,
 inicio DATETIME NOT NULL, fim DATETIME NOT NULL, observacao VARCHAR(255), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_disp_usuario(usuario_id,inicio,fim), CHECK(fim>inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS usuario_especialidades (
 usuario_id INT NOT NULL, especialidade VARCHAR(100) NOT NULL, nivel TINYINT UNSIGNED DEFAULT 1,
 PRIMARY KEY(usuario_id,especialidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS passagens_turno (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, entrega_id INT NOT NULL, recebe_id INT NOT NULL,
 pendencias TEXT, ocorrencias_criticas TEXT, equipamentos_indisponiveis TEXT, servicos_andamento TEXT,
 itens_entregues TEXT, observacoes TEXT, lida_em DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_turno_recebe(recebe_id,lida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terceiros (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo_pessoa ENUM('PF','PJ') NOT NULL, documento VARCHAR(20) NOT NULL,
 nome VARCHAR(180) NOT NULL, contatos JSON, contrato VARCHAR(120), vigencia_inicio DATE, vigencia_fim DATE,
 especialidade VARCHAR(160), situacao VARCHAR(30) NOT NULL DEFAULT 'ATIVO', avaliacao DECIMAL(3,2),
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_terceiro_documento(documento), KEY idx_terceiro_vigencia(vigencia_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS terceiro_documentos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, terceiro_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(80), caminho VARCHAR(255),
 vence_em DATE, sha256 CHAR(64), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_doc_vence(vence_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conclusao_assinaturas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, nome VARCHAR(180) NOT NULL,
 papel VARCHAR(60) NOT NULL, arquivo VARCHAR(255), aceite TINYINT(1) NOT NULL, justificativa TEXT,
 ip VARCHAR(45), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_assinatura_os(ordem_servico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recorrencias_os (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, semelhante_id INT NOT NULL,
 score DECIMAL(5,4) NOT NULL, criterios JSON NOT NULL, detectada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_recorrencia(ordem_servico_id,semelhante_id), KEY idx_rec_score(score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispositivos_iot (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identificador VARCHAR(100) NOT NULL, tipo VARCHAR(80) NOT NULL,
 local_id INT NULL, status VARCHAR(30) NOT NULL DEFAULT 'NOVO', ultima_comunicacao DATETIME NULL,
 bateria DECIMAL(5,2), limite_alerta JSON, api_key_hash CHAR(64) NOT NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_iot_identificador(identificador), UNIQUE KEY uq_iot_api(api_key_hash), KEY idx_iot_status(status,ultima_comunicacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS iot_leituras (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, dispositivo_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(80) NOT NULL,
 valor_numero DECIMAL(20,6), valor_texto VARCHAR(255), payload JSON, recebida_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_leitura_disp(dispositivo_id,recebida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativos_rede (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo ENUM('CAMERA','NVR','DVR','SWITCH','ACCESS_POINT','LINK','SERVIDOR') NOT NULL,
 nome VARCHAR(160) NOT NULL, endereco_ip VARCHAR(45), local_id INT NULL, criticidade VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
 falhas_para_alerta SMALLINT UNSIGNED NOT NULL DEFAULT 3, falhas_consecutivas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 status VARCHAR(30) NOT NULL DEFAULT 'DESCONHECIDO', ultima_verificacao DATETIME NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_rede_ip(endereco_ip), KEY idx_rede_monitor(status,ultima_verificacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ativo_rede_checks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_rede_id BIGINT UNSIGNED NOT NULL, sucesso TINYINT(1) NOT NULL,
 latencia_ms INT NULL, detalhe VARCHAR(255), verificado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_check_ativo(ativo_rede_id,verificado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, tipo VARCHAR(60) NOT NULL,
 titulo VARCHAR(180) NOT NULL, mensagem TEXT, url VARCHAR(255), lida_em DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_notificacao_usuario(usuario_id,lida_em,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_execucoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, comando VARCHAR(80) NOT NULL, status VARCHAR(20) NOT NULL,
 iniciado_em DATETIME NOT NULL, finalizado_em DATETIME NULL, resumo JSON NULL, erro TEXT NULL,
 KEY idx_cron_comando(comando,iniciado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL geop_add_column('ordens_servico','ativo_id','BIGINT UNSIGNED NULL');
CALL geop_add_column('ordens_servico','token_publico_hash','CHAR(64) NULL');
CALL geop_add_column('ordens_servico','latitude','DECIMAL(10,7) NULL');
CALL geop_add_column('ordens_servico','longitude','DECIMAL(10,7) NULL');
CALL geop_add_column('categorias','exige_foto_antes','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('categorias','exige_foto_durante','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('categorias','exige_foto_depois','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('materiais_os','produto_id','INT NULL');
CALL geop_add_column('movimentacoes_estoque','justificativa','TEXT NULL');
CALL geop_add_column('movimentacoes_estoque','aprovacao_id','BIGINT UNSIGNED NULL');

INSERT INTO schema_migrations (version,applied_at) VALUES ('002_operational_features',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;
