<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

include_once plugin_path('/feature-metrics/instructions.php');

pageBaseTop();


$permission = load_permission('manage-feature-metrics', 'custom');

$delete = [
    'permission' => $permission,
    'url' => "redirect=true&table=tb_feature_metrics&permission_id=manage-feature-metrics&where_field=feature_name&where_value=",
];

// Filtro opcional por user_id

$user_filter = isset($_GET['user'])
    ? trim((string) $_GET['user'])
    : '';


/**
 * Filtros de data (YYYY-MM-DD)
 */
$start_date = isset($_GET['start_date']) ? trim((string)$_GET['start_date']) : '';
$end_date   = isset($_GET['end_date'])   ? trim((string)$_GET['end_date'])   : '';

/**
 * Sanitização básica: precisa bater com YYYY-MM-DD ou é ignorado
 */
$validDate = function ($d) {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

/**
 * Se ambos vazios → últimos 90 dias
 */
if ($start_date === '' && $end_date === '') {
    $end_date   = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-90 days'));
}
/**
 * Se só start informado → end = hoje
 */
elseif ($start_date !== '' && $end_date === '' && $validDate($start_date)) {
    $end_date = date('Y-m-d');
}
/**
 * Se só end informado → start = end - 90 dias
 */
elseif ($start_date === '' && $end_date !== '' && $validDate($end_date)) {
    $start_date = date('Y-m-d', strtotime('-90 days', strtotime($end_date)));
}

/**
 * Revalida depois do preenchimento automático
 */
if (!$validDate($start_date)) $start_date = '';
if (!$validDate($end_date))   $end_date   = '';

/**
 * Monta condições do WHERE
 */
$whereParts = [];

/**
 * pesquisa por user_id
 */
if ($user_filter) {
    $safe_email = addslashes($user_filter);
    $whereParts[] = "((u.email LIKE '%{$safe_email}%') OR (fm.user_id = '{$user_filter}'))";
}

/**
 * filtro por data
 */
if ($start_date !== '' && $end_date !== '') {
    $start_ts = $start_date . ' 00:00:00';
    $end_ts   = $end_date   . ' 23:59:59';
    $whereParts[] = "fm.created_at BETWEEN '{$start_ts}' AND '{$end_ts}'";
}

$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$joinUsers = ($user_filter !== '') ? "LEFT JOIN tb_users u ON u.id = fm.user_id" : "";


// ---------------------------------------------
// SQL agora traz eventos brutos + métricas por usuário
// ---------------------------------------------
$sql = "
SELECT
    fm.feature_name,

    -- Eventos totais (globais)
    SUM(fm.type = '1') AS views,
    SUM(fm.type = '2') AS clicks,
    SUM(fm.type = '3') AS uses,

    -- Métricas por usuário
    COUNT(DISTINCT CASE WHEN fm.type = '1' AND fm.user_id IS NOT NULL THEN fm.user_id END) AS unique_viewers,
    COUNT(DISTINCT CASE WHEN fm.type = '2' AND fm.user_id IS NOT NULL THEN fm.user_id END) AS unique_clickers,
    COUNT(DISTINCT CASE WHEN fm.type = '3' AND fm.user_id IS NOT NULL THEN fm.user_id END) AS unique_users

FROM tb_feature_metrics fm
{$joinUsers}
{$where}
GROUP BY fm.feature_name
ORDER BY fm.feature_name
";

$rows = get_results($sql) ?? [];

$body = [];
$rows = get_results($sql) ?? [];

// Array base que alimenta as duas tabelas
$features = [];

foreach ($rows as $row)
{
    $views          = (int) $row['views'];
    $clicks         = (int) $row['clicks'];
    $uses           = (int) $row['uses'];

    $unique_viewers  = (int) $row['unique_viewers'];
    $unique_clickers = (int) $row['unique_clickers'];
    $unique_users    = (int) $row['unique_users'];

    // Taxas por usuário (ADOÇÃO – principais)
    $ctr_users = $unique_viewers > 0
        ? round(($unique_clickers / $unique_viewers) * 100, 1)
        : 0;

    $conv_users = $unique_viewers > 0
        ? round(($unique_users / $unique_viewers) * 100, 1)
        : 0;

    // Notas (baseadas nas métricas por usuário)
    $ctr_label = feature_metric_ctr_label($ctr_users);
    $conv_label = feature_metric_use_label($conv_users);
    $instruction = feature_metric_suggestion($ctr_users, $conv_users);

    // Armazena tudo em um array só
    $features[] = [
        'feature'         => $row['feature_name'],

        // Globais
        'views'           => $views,
        'clicks'          => $clicks,
        'uses'            => $uses,

        // Únicos
        'unique_viewers'  => $unique_viewers,
        'unique_clickers' => $unique_clickers,
        'unique_users'    => $unique_users,

        // Taxas por usuário
        'ctr_users'       => $ctr_users,
        'conv_users'      => $conv_users,

        // Labels & instruções
        'ctr_label'       => $ctr_label,
        'conv_label'      => $conv_label,
        'instruction'     => $instruction,

        // Ações
        'actions'         => build_table_actions(
            ['delete' => $delete],
            $row['feature_name']
        ),
    ];
}


?>

<main class="row justify-content-center m-0" role="main">

    <form method="get" class="card col-md-8 mb-3">
        <div class="card-body row g-3">

            <div class="col-md-3">
                <label class="form-label">user_id ou email</label>
                <input type="text" class="form-control" name="user"
                       value="<?php echo htmlspecialchars((string)$user_filter, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Data inicial</label>
                <input type="date" class="form-control" name="start_date"
                       value="<?php echo htmlspecialchars((string)$start_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Data final</label>
                <input type="date" class="form-control" name="end_date"
                       value="<?php echo htmlspecialchars((string)$end_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2">Aplicar</button>
                <a href="<?= site_url('/admin/'. $page['slug']) ?>"
                   class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>

            <div class="col-12 mt-2">
                <small class="text-muted">
                    Se nenhuma data for informada, o relatório considera os últimos 90 dias por padrão.
                </small>
            </div>

        </div>
    </form>

    <?php
    $crud_panel = ['show_name' => true];

    $crud_panel['form_name'] = 'Agrupado por usuários';
    echo table([
        'crud_panel' => $crud_panel,
        'data_table' => true,
        'head' => [
            'Feature',
            'Views únicos',
            'Cliques únicos',
            'Usos únicos',
            'CTR (usuários)',
            'Nota CTR',
            'Conversão (usuários)',
            'Nota Conv.',
            'Instruções',
            'Ações'
        ],
        'body' => array_map(function ($f) {
            return [
                $f['feature'],
                $f['unique_viewers'],
                $f['unique_clickers'],
                $f['unique_users'],
                number_format($f['ctr_users'], 1, ".", "," ) . '%',
                $f['ctr_label'],
                number_format($f['conv_users'], 1, ".", "," ) . '%',
                $f['conv_label'],
                $f['instruction'],
                $f['actions'],
            ];
        }, $features),
    ]);

    $crud_panel['form_name'] = 'Contexto global';
    echo table([
        'crud_panel' => $crud_panel,
        'data_table' => true,
        'head' => [
            'Feature',
            'Views totais',
            'Cliques totais',
            'Usos totais',
            // 'Cliques por usuário que clicou',
            'Frequência uso méd.',
            'Ações'
        ],
        'body' => array_map(function ($f) {

            // Intensidade média
            $avg_clicks = $f['unique_clickers'] > 0
                ? round($f['clicks'] / $f['unique_clickers'], 2)
                : 0;

            $avg_uses = $f['unique_users'] > 0
                ? round($f['uses'] / $f['unique_users'], 2)
                : 0;

            $avg_uses = number_format($avg_uses, 2, ".", "," );

            return [
                $f['feature'],
                $f['views'],
                $f['clicks'],
                $f['uses'],
                // $avg_clicks,
                $avg_uses > 0 ? ($avg_uses."x") : '-',
                $f['actions'],
            ];
        }, $features),
    ]);
    ?>

    <div class="col-12">
        <?= block('alert',
        [
            'body'         => render_feature_instructions(),
            'variation'    => 'alert-disclaimer',
            'close_button' => false,
            'color'        => 'info'
        ]); ?>
    </div>

</main>

<?php
include_once AREAS_PATH .'/admin/include/script_libs.php';
