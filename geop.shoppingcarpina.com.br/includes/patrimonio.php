<?php
declare(strict_types=1);

require_once dirname(__FILE__) . '/../config/patrimonio.php';

function patrimonio_normalize_code($code): string
{
    return trim(
        preg_replace(
            '/[\x00-\x1F\x7F]/u',
            '',
            (string)$code
        )
    );
}

function patrimonio_select_sql(array $config): string
{
    $parts = [];

    foreach ($config['fields'] as $alias => $column) {
        if (
            $alias === 'responsavel'
            && !empty($config['responsavel_relation'])
        ) {
            $relation = $config['responsavel_relation'];

            $parts[] =
                'COALESCE(' .
                    '(' .
                        'SELECT u.' .
                        patrimonio_identifier($relation['name']) .
                        ' FROM ' .
                        patrimonio_identifier($relation['table']) .
                        ' AS u' .
                        ' WHERE u.' .
                        patrimonio_identifier($relation['id']) .
                        ' = a.' .
                        patrimonio_identifier($column) .
                        ' LIMIT 1' .
                    '),' .
                    'CAST(a.' .
                    patrimonio_identifier($column) .
                    ' AS CHAR)' .
                ') AS ' .
                patrimonio_identifier('responsavel');
        } else {
            $parts[] =
                'a.' .
                patrimonio_identifier($column) .
                ' AS ' .
                patrimonio_identifier($alias);
        }
    }

    return implode(', ', $parts);
}

function patrimonio_search(
    string $type,
    string $term,
    int $page = 1,
    int $limit = 20
): array {
    $config = patrimonio_config();
    $term = patrimonio_normalize_code($term);
    $page = max(1, $page);
    $limit = max(1, min(50, $limit));

    $map = [
        'codigo' => 'codigo_barras',
        'numero' => 'numero_patrimonio',
        'descricao' => 'descricao',
    ];

    $key = $map[$type] ?? '';

    if (
        $key === ''
        || empty($config['fields'][$key])
    ) {
        throw new RuntimeException(
            'Este tipo de consulta não foi mapeado.'
        );
    }

    if (
        $type === 'descricao'
        && mb_strlen($term) < 3
    ) {
        throw new RuntimeException(
            'Digite ao menos 3 caracteres.'
        );
    }

    if (
        $term === ''
        || mb_strlen($term) > 120
    ) {
        throw new RuntimeException(
            'Consulta inválida.'
        );
    }

    if ($type === 'descricao') {
        $searchColumns = [];

        foreach (
            [
                'descricao',
                'nome',
                'marca',
                'modelo',
                'observacoes',
            ] as $searchKey
        ) {
            if (!empty($config['fields'][$searchKey])) {
                $columnName = $config['fields'][$searchKey];

                $searchColumns[$columnName] =
                    'a.' . patrimonio_identifier($columnName);
            }
        }

        if (!$searchColumns) {
            throw new RuntimeException(
                'A pesquisa por descrição não foi configurada.'
            );
        }

        $conditions = [];

        foreach ($searchColumns as $column) {
            $conditions[] = $column . ' LIKE ?';
        }

        $condition = '(' . implode(' OR ', $conditions) . ')';

        $params = array_fill(
            0,
            count($conditions),
            '%' . $term . '%'
        );
    } else {
        $column =
            'a.' .
            patrimonio_identifier(
                $config['fields'][$key]
            );

        /*
         * Correspondência exata.
         * Tolera espaços antigos, mas não remove zeros à esquerda.
         */
        $condition =
            '(' .
                $column . ' = ?' .
                ' OR TRIM(' . $column . ') = ?' .
            ')';

        $params = [
            $term,
            $term,
        ];
    }

    $offset = ($page - 1) * $limit;

    $sql =
        'SELECT ' .
        patrimonio_select_sql($config) .
        ' FROM ' .
        patrimonio_identifier($config['table']) .
        ' AS a' .
        ' WHERE ' .
        $condition .
        ' LIMIT ' . $limit .
        ' OFFSET ' . $offset;

    try {
        $statement = patrimonio_pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    } catch (RuntimeException $e) {
        throw $e;
    } catch (Throwable $e) {
        error_log(
            'Consulta patrimonio falhou [' .
            $e->getCode() .
            '].'
        );

        throw new RuntimeException(
            'Não foi possível consultar o patrimônio.'
        );
    }
}

function patrimonio_snapshot(array $row): array
{
    $get = static function (
        string $key,
        int $length = 255
    ) use ($row): string {
        return mb_substr(
            trim((string)($row[$key] ?? '')),
            0,
            $length
        );
    };

    return [
        'patrimonio_externo_id' => $get('id', 190),
        'codigo_barras' => $get('codigo_barras', 190),
        'numero_patrimonio' => $get(
            'numero_patrimonio',
            190
        ),
        'descricao_snapshot' =>
            $get('nome')
            ?: $get('descricao'),
        'marca_snapshot' => $get('marca', 120),
        'modelo_snapshot' => $get('modelo', 120),
        'serie_snapshot' => $get('serie', 190),
        'setor_snapshot' => $get('setor', 190),
        'localizacao_snapshot' => $get(
            'localizacao',
            255
        ),
    ];
}