<?php
// if (!isset($seg)) exit;

const SEARCHABLE_FIELDS_LIMIT = 7;

/**
 * Formato 1: [field-id="{x}"]
 */
function searchable_fields_by_field_id($fieldId, string $searchValue): array
{
    $row   = get_result("SELECT `Query` FROM tb_cruds_fields WHERE " . safe_where('id', '=', $fieldId));
    $query = $row['Query'] ?? null;

    if (empty($query)) return [];

    return run_searchable_query($query, $searchValue, null);
}


/**
 * Formato 2: [field-search='{x}']
 */
function searchable_fields_by_group(string $key, string $searchValue): array
{
    $group = $GLOBALS['searchable-fields'][$key] ?? null;

    if (empty($group) || empty($group['query'])) return [];

    if (!empty($group['permission']))
    {
      $permission = load_permission($group['permission'], 'custom');
      if (!$permission) {return [];}
    }

    return run_searchable_query($group['query'], $searchValue, $group['fields'] ?? []);
}


/**
 * Executa a query base (adicionando a condição de busca e o limite de
 * registros) e normaliza o resultado para [ ['value' => ..., 'display' => ...], ... ].
 *
 * @param string     $baseQuery   SQL base, já selecionando "value" e "display".
 * @param string     $searchValue Texto digitado pelo usuário.
 * @param array|null $fields      Lista explícita de colunas pesquisáveis
 *                                (formato 2). Quando vazio/null, as colunas
 *                                são deduzidas a partir da expressão usada
 *                                como "display" na própria query (formato 1),
 *                                desmontando um CONCAT(...) quando existir.
 */
function run_searchable_query(string $baseQuery, string $searchValue, ?array $fields): array
{
    $sql = rtrim(trim($baseQuery), "; \t\n\r\0\x0B");

    if ($searchValue !== '')
    {
        $columns = !empty($fields) ? $fields : extract_display_columns($sql);

        if (!empty($columns))
        {
            $likeValue  = '%' . escape_like_wildcards($searchValue) . '%';

            // skip_sanitize=true: esses nomes de coluna vêm de configuração
            // do próprio CMS (tb_cruds_fields.Query ou $GLOBALS['searchable-fields']),
            // não da requisição, então podem conter qualificação de tabela
            // ("t.coluna"), que a sanitização padrão de safe_where() removeria.
            // O valor de busca continua escapado por safe_where()/db_escape().
            $conditions = array_map(
                fn($column) => safe_where($column, 'LIKE', $likeValue, true),
                $columns
            );

            $sql = inject_where_condition($sql, implode(' OR ', $conditions));
        }
    }

    // Sempre respeita o limite fixo de 7 registros, substituindo
    // qualquer LIMIT que a query base já possua.
    $sql = strip_trailing_limit($sql);
    $sql.= ' LIMIT ' . SEARCHABLE_FIELDS_LIMIT;

    $rows = get_results($sql) ?: [];

    return normalize_searchable_rows($rows);
}


/**
 * Resolve o(s) valor(es) já selecionados de um campo de busca ASYNC,
 * para a tela poder exibi-los assim que a página carrega (edição), sem
 * esperar uma busca do usuário. Prioriza a query do próprio input
 * (tb_cruds_fields, via field-id); se não houver field-id, cai para a
 * query registrada em $GLOBALS['searchable-fields'] (via field-search).
 *
 * @param string|null $fieldId
 * @param string|null $fieldSearch
 * @param mixed       $value       Um valor único ou array de valores (multi-select).
 */
function resolve_searchable_options_by_value(?string $fieldId, ?string $fieldSearch, $value): array
{
    $values = is_array($value) ? $value : [$value];
    $values = array_values(array_filter($values, fn($v) => $v !== null && $v !== ''));

    if (empty($values)) return [];

    $query = null;

    if (!empty($fieldId))
    {
        $row   = get_result("SELECT `Query` FROM tb_cruds_fields WHERE " . safe_where('id', '=', $fieldId));
        $query = $row['Query'] ?? null;
    }
    elseif (!empty($fieldSearch))
    {
        $group = $GLOBALS['searchable-fields'][$fieldSearch] ?? null;

        if (!empty($group['permission']) && !load_permission($group['permission'], 'custom')) {
            return [];
        }

        $query = $group['query'] ?? null;
    }

    if (empty($query)) return [];

    $sql       = rtrim(trim($query), "; \t\n\r\0\x0B");
    $valueExpr = extract_aliased_expression($sql, 'value') ?? 'value';

    $sql = inject_where_condition($sql, safe_where($valueExpr, 'IN', $values, true));
    $sql = strip_trailing_limit($sql) . ' LIMIT ' . count($values);

    $res = (get_results($sql) ?: []);

    return $res;
}


/**
 * Cria um novo registro através da query de inserção configurada em
 * $GLOBALS['searchable-fields'][$key]['insert']['query'], usada quando
 * o campo permite criação (data-allow-create) e o usuário digita um
 * valor que ainda não existe na lista. Só se aplica ao formato 2
 * (field-search) — tb_cruds_fields não tem uma query de inserção própria.
 *
 * O placeholder {value} na query é substituído pelo texto digitado
 * (escapado). Depois do INSERT, o novo registro é buscado de volta pela
 * mesma query base (filtrando pelo id gerado) para devolver o par
 * ['value' => ..., 'display' => ...] já formatado — assim o TomSelect
 * usa o id/display reais do banco, não o texto cru digitado.
 *
 * Retorna [] quando não há query de inserção configurada (ou falta
 * permissão) — quem chamou decide o fallback (ex.: criar só localmente).
 */
function create_searchable_field_option(?string $key, string $newValue): array
{
    if (empty($key) || $newValue === '') return [];

    $group = $GLOBALS['searchable-fields'][$key] ?? null;
    if (empty($group)) return [];

    if (!empty($group['permission']) && !load_permission($group['permission'], 'custom')) {
        return [];
    }

    $insertQuery = $group['insert']['query'] ?? null;
    if (empty($insertQuery)) return [];

    $sql = str_replace('{value}', db_escape($newValue), $insertQuery);
    query_it($sql);

    $newId = inserted_id();
    if (!$newId) return [];

    // Não refaz a query base pra montar o "display": o registro recém-criado
    // normalmente só tem o campo que acabamos de gravar preenchido — se o
    // display da query for um CONCAT com outras colunas (last_name, email,
    // etc.) ainda NULL, o CONCAT inteiro vira NULL e normalize_searchable_rows()
    // descartaria a linha, fazendo o formulário perder o id real e cair no
    // texto digitado como value. Como o próprio texto digitado é o dado que
    // acabamos de inserir, ele já É o display correto do novo registro.
    return [
        'value'   => $newId,
        'display' => $newValue,
    ];
}


/**
 * Normaliza linhas para [ ['value' => ..., 'display' => ...], ... ],
 * descartando linhas com value/display nulos ou vazios — evita mostrar
 * uma opção "null" (ex.: um CONCAT() que virou NULL porque um dos
 * campos concatenados está NULL no banco).
 */
function normalize_searchable_rows(array $rows): array
{
    $result = [];

    foreach ($rows as $row)
    {
        $row = (array) $row;

        $value   = $row['value']   ?? null;
        $display = $row['display'] ?? null;

        if ($value === null || $value === '') continue;
        if ($display === null || trim((string) $display) === '') continue;

        $result[] = [
            'value'   => $value,
            'display' => $display,
        ];
    }

    return $result;
}


/**
 * Insere a condição de busca respeitando um WHERE que a query já possa
 * ter: se já existir WHERE, adiciona " AND (...)"; caso contrário,
 * adiciona " WHERE (...)". Em ambos os casos, a condição é inserida
 * antes de um eventual ORDER BY / GROUP BY / HAVING / LIMIT já
 * existente na query base, e não depois dele.
 */
function inject_where_condition(string $sql, string $condition): string
{
    $hasWhere = (bool) preg_match('/\bwhere\b/i', $sql);
    $clause   = $hasWhere ? "AND ({$condition})" : "WHERE ({$condition})";

    if (preg_match('/\b(order\s+by|group\s+by|having|limit)\b/i', $sql, $m, PREG_OFFSET_CAPTURE))
    {
        $pos = $m[0][1];
        return rtrim(substr($sql, 0, $pos)) . " {$clause} " . substr($sql, $pos);
    }

    return rtrim($sql) . " {$clause}";
}


/**
 * Remove um LIMIT já existente ao final da query (se houver), para que
 * possamos aplicar o limite fixo de 7 registros sem gerar SQL inválido.
 */
function strip_trailing_limit(string $sql): string
{
    return preg_replace('/\s+limit\s+\d+(\s*,\s*\d+)?\s*$/i', '', rtrim($sql));
}


/**
 * Descobre quais colunas reais devem ser pesquisadas a partir da
 * expressão usada como "display" no SELECT da query base. Se a
 * expressão for um CONCAT(...)/CONCAT_WS(...), retorna cada campo
 * usado dentro dele (para permitir busca com OR em cada um); caso
 * contrário, retorna a própria expressão/coluna.
 */
function extract_display_columns(string $sql): array
{
    $expr = extract_aliased_expression($sql, 'display');

    return $expr !== null ? extract_columns_from_expression($expr) : [];
}


/**
 * Encontra, no SELECT da query base, a expressão aliasada com o nome
 * dado (ex.: "value" ou "display") e devolve essa expressão crua (ex.:
 * "id" ou "CONCAT(first_name, ' ', last_name)"). Retorna null quando o
 * alias não é encontrado.
 */
function extract_aliased_expression(string $sql, string $alias): ?string
{
    if (!preg_match('/select\s+(.*?)\s+from\s/is', $sql, $m)) {
        return null;
    }

    $columns    = split_top_level_commas($m[1]);
    $aliasQuoted = preg_quote($alias, '/');

    foreach ($columns as $column)
    {
        $column = trim($column);

        if (preg_match('/^(.*?)\s+as\s+' . $aliasQuoted . '\s*$/i', $column, $mm)
            || preg_match('/^(.*?)\s+' . $aliasQuoted . '\s*$/i', $column, $mm))
        {
            return trim($mm[1]);
        }
    }

    return null;
}


/**
 * Dada uma expressão de SELECT (ex.: "first_name" ou
 * "CONCAT(first_name, ' ', last_name)"), retorna as colunas reais que
 * devem entrar na busca.
 */
function extract_columns_from_expression(string $expr): array
{
    if (preg_match('/^concat(_ws)?\s*\((.*)\)$/is', $expr, $m))
    {
        $isConcatWs = !empty($m[1]);
        $args = array_map('trim', split_top_level_commas($m[2]));

        // Em CONCAT_WS o primeiro argumento é o separador, não uma coluna.
        if ($isConcatWs) array_shift($args);

        $columns = [];
        foreach ($args as $arg)
        {
            // Ignora literais de texto/número usados só como separador.
            if (preg_match('/^\'.*\'$/s', $arg)) continue;
            if (preg_match('/^".*"$/s', $arg)) continue;
            if (is_numeric($arg)) continue;
            if ($arg === '') continue;

            $columns[] = $arg;
        }

        return $columns;
    }

    return [$expr];
}


/**
 * Divide uma lista separada por vírgulas (ex.: a lista de colunas de um
 * SELECT) ignorando vírgulas dentro de parênteses (funções como
 * CONCAT(...)) ou dentro de strings entre aspas.
 */
function split_top_level_commas(string $text): array
{
    $parts   = [];
    $buffer  = '';
    $depth   = 0;
    $inStr   = false;
    $strChar = '';

    $len = strlen($text);
    for ($i = 0; $i < $len; $i++)
    {
        $char = $text[$i];

        if ($inStr)
        {
            $buffer.= $char;
            if ($char === $strChar) $inStr = false;
            continue;
        }

        if ($char === "'" || $char === '"')
        {
            $inStr   = true;
            $strChar = $char;
            $buffer.= $char;
            continue;
        }

        if ($char === '(') { $depth++; $buffer.= $char; continue; }
        if ($char === ')') { $depth--; $buffer.= $char; continue; }

        if ($char === ',' && $depth === 0)
        {
            $parts[] = $buffer;
            $buffer  = '';
            continue;
        }

        $buffer.= $char;
    }

    if (trim($buffer) !== '') $parts[] = $buffer;

    return $parts;
}


/**
 * Escapa os curingas do LIKE (% e _) presentes no texto digitado pelo
 * usuário, para que sejam tratados como caracteres literais.
 */
function escape_like_wildcards(string $value): string
{
    return addcslashes($value, '%_');
}
