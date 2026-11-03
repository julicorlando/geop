<?php
declare(strict_types=1);

function conhecimento_pode_gerenciar(): bool
{
    $perfil = (string)(current_user()['perfil'] ?? '');
    return in_array($perfil, ['TI', 'GESTOR', 'GESTOR_SINDICO', 'COORDENADOR_OPERACOES_OBRAS'], true);
}

function conhecimento_buscar(PDO $pdo, string $termo = '', ?int $categoriaId = null, bool $incluirInativos = false): array
{
    $where = [];
    $params = [];
    if (!$incluirInativos) {
        $where[] = "a.status = 'PUBLICADO'";
    }
    if ($categoriaId) {
        $where[] = '(a.categoria_id = ? OR a.categoria_id IS NULL)';
        $params[] = $categoriaId;
    }
    if ($termo !== '') {
        $where[] = '(a.titulo LIKE ? OR a.problema LIKE ? OR a.solucao LIKE ? OR a.palavras_chave LIKE ?)';
        for ($i = 0; $i < 4; $i++) $params[] = '%' . $termo . '%';
    }
    $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $st = $pdo->prepare("SELECT a.*, c.nome categoria_nome, u.nome responsavel_nome,
            origem.numero_os origem_numero_os
        FROM conhecimento_artigos a
        LEFT JOIN categorias c ON c.id=a.categoria_id
        LEFT JOIN usuarios u ON u.id=a.responsavel_id
        LEFT JOIN ordens_servico origem ON origem.id=a.origem_os_id
        $sqlWhere
        ORDER BY (a.categoria_id IS NULL), a.titulo");
    $st->execute($params);
    return $st->fetchAll();
}

function conhecimento_sugestoes(PDO $pdo, int $categoriaId, string $texto = '', int $limite = 5): array
{
    $artigos = conhecimento_buscar($pdo, '', $categoriaId, false);
    $termos = array_filter(preg_split('/[^\pL\pN]+/u', mb_strtolower($texto, 'UTF-8')) ?: [], static fn($v) => mb_strlen($v, 'UTF-8') >= 4);
    foreach ($artigos as &$artigo) {
        $base = mb_strtolower(implode(' ', [$artigo['titulo'], $artigo['problema'] ?? '', $artigo['palavras_chave'] ?? '']), 'UTF-8');
        $artigo['_score'] = (int)((int)$artigo['categoria_id'] === $categoriaId) * 10;
        foreach ($termos as $termo) {
            if (str_contains($base, $termo)) $artigo['_score'] += 2;
        }
    }
    unset($artigo);
    usort($artigos, static fn($a, $b) => ($b['_score'] <=> $a['_score']) ?: strcmp($a['titulo'], $b['titulo']));
    return array_slice($artigos, 0, max(1, $limite));
}
