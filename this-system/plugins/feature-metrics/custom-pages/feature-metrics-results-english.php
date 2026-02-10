<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();

/**
 * Classify Click-Through Rate (views -> clicks)
 */
function feature_meter_ctr_label(float $ctr = 0)
{
    $rules = [
        'low' => [
            'until' => 2.0,
            'label' => 'Very low (no demand)'
        ],
        'weak' => [
            'until' => 5.0,
            'label' => 'Weak interest'
        ],
        'moderate' => [
            'until' => 10.0,
            'label' => 'Moderate interest'
        ],
        'high' => [
            'until' => 20.0,
            'label' => 'High interest'
        ],
        'strong' => [
            'until' => null,
            'label' => 'Very strong interest'
        ],
    ];

    if (!$ctr) {
        return $rules;
    }

    foreach ($rules as $rule)
    {
        if ($rule['until'] !== null && $ctr < $rule['until']) {
            return $rule['label'];
        }

        if ($rule['until'] === null) {
            return $rule['label'];
        }
    }

    return 'Undefined';
}

/**
 * Classify Use Rate (views -> uses)
 * (This is effectively the "conversion" to usage.)
 */
function feature_meter_use_label(float $useRate = 0)
{
    $rules = [
        [
            'until' => 1.0,
            'label' => 'Curiosity only',
        ],
        [
            'until' => 3.0,
            'label' => 'Low value',
        ],
        [
            'until' => 8.0,
            'label' => 'Niche but useful',
        ],
        [
            'until' => 15.0,
            'label' => 'Useful for a good group',
        ],
        [
            'until' => null,
            'label' => 'Core / important feature',
        ],
    ];

    if (!$useRate) {
        return $rules;
    }

    foreach ($rules as $rule) {
        if ($rule['until'] !== null && $useRate < $rule['until']) {
            return $rule['label'];
        }
        if ($rule['until'] === null) {
            return $rule['label'];
        }
    }

    return 'Undefined';
}

/**
 * High-level instructions based on CTR and use rate
 *
 * CONTEXT RULE:
 * - useRate == 0 → treated as "future feature" (does not exist yet)
 * - useRate > 0  → treated as "existing feature"
 *
 * When called with (0,0) → returns the full ruleset to be rendered on screen.
 */
function feature_meter_suggestion(float $ctr = 0, float $useRate = 0)
{
    $rules = [
        // -------------------------------
        // FUTURE FEATURES (useRate == 0)
        // -------------------------------
        [
            'segment'  => 'future',
            'label'    => 'CTR < 2% - idea is cold, keep it parked or remove.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c < 2.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR between 2% and 5% - weak curiosity, only test if the cost is very low.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 2.0 && $c < 5.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR between 5% and 10% - moderate interest, good candidate for discovery interviews or a simple test.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 5.0 && $c < 10.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR between 10% and 20% - strong interest, consider prototyping this feature with limited scope.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 10.0 && $c < 20.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR ≥ 20% - very strong interest without implementation, top candidate for design and development.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 20.0;
            },
        ],

        // --------------------------------
        // EXISTING FEATURES (useRate > 0)
        // --------------------------------
        [
            'segment'  => 'existing',
            'label'    => 'CTR < 2% and use < 1% - almost invisible and unused, consider removing or hiding.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c < 2.0 && $u < 1.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR < 5% but use ≥ 5% - hidden gem, people who find it use a lot, improve discoverability.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c < 5.0 && $u >= 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR ≥ 20% and use ≥ 15% - core feature, keep investing and highlight in product/marketing.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 20.0 && $u >= 15.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR ≥ 10% and use ≥ 5% - high priority, strong demand, plan roadmap and keep improving.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 10.0 && $u >= 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR ≥ 10% but use < 5% - users are interested but struggle to use it; review UX, copy and onboarding.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 10.0 && $u > 0.0 && $u < 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR between 5% and 10% and use between 1% and 5% - mid interest and mid usage, good candidate for a focused improvement test.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 5.0 && $c < 10.0 && $u >= 1.0 && $u < 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'Other cases - monitor, iterate and keep watching these metrics.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting;
            },
        ],
    ];

    // When called with no values, return ruleset so the screen can render instructions.
    if ($ctr == 0.0 && $useRate == 0.0) {
        return $rules;
    }

    $isExisting = ($useRate > 0.0);

    foreach ($rules as $rule) {
        if (call_user_func($rule['criteria'], $ctr, $useRate, $isExisting)) {
            return $rule['label'];
        }
    }

    return 'Undefined';
}


$permission = load_permission('manage-feature-meters', 'custom');

$delete = [
    'permission' => $permission,
    'url' => "redirect=true&table=tb_feature_meter&permission_id=manage-feature-meters&where_field=feature_name&where_value=",
];

// Optional filter by user_id
$user_id_filter = isset($_GET['user_id'])
    ? (int) $_GET['user_id']
    : null;

// Date filters (YYYY-MM-DD)
$start_date = isset($_GET['start_date']) ? trim((string)$_GET['start_date']) : '';
$end_date   = isset($_GET['end_date'])   ? trim((string)$_GET['end_date'])   : '';

// Basic sanitization: must match YYYY-MM-DD or be ignored
$validDate = function ($d) {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

// If both empty → last 90 days
if ($start_date === '' && $end_date === '') {
    $end_date   = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-90 days'));
}
// If only start provided → end = today
elseif ($start_date !== '' && $end_date === '' && $validDate($start_date)) {
    $end_date = date('Y-m-d');
}
// If only end provided → start = end - 90 days
elseif ($start_date === '' && $end_date !== '' && $validDate($end_date)) {
    $start_date = date('Y-m-d', strtotime('-90 days', strtotime($end_date)));
}

// Re-validate after auto-fill
if (!$validDate($start_date)) $start_date = '';
if (!$validDate($end_date))   $end_date   = '';

// Build WHERE conditions
$whereParts = [];

if ($user_id_filter) {
    $whereParts[] = "user_id = {$user_id_filter}";
}

if ($start_date !== '' && $end_date !== '') {
    $start_ts = $start_date . ' 00:00:00';
    $end_ts   = $end_date   . ' 23:59:59';
    $whereParts[] = "created_at BETWEEN '{$start_ts}' AND '{$end_ts}'";
}

$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$sql = "
SELECT
    feature_name,
    SUM(type = '1')  AS views,
    SUM(type = '2') AS clicks,
    SUM(type = '3')   AS uses,
    COUNT(DISTINCT CASE WHEN type = 'use' AND user_id IS NOT NULL THEN user_id END) AS unique_users
FROM tb_feature_meter
{$where}
GROUP BY feature_name
ORDER BY feature_name";

$rows = get_results($sql) ?? [];

$body = [];
foreach ($rows as $row)
{
    $table_actions = [];
    $views        = (int) ($row['views'] ?? 0);
    $clicks       = (int) ($row['clicks'] ?? 0);
    $uses         = (int) ($row['uses'] ?? 0);
    $unique_users = (int) ($row['unique_users'] ?? 0);

    $click_rate = $views > 0 ? round(($clicks / $views) * 100, 1) : 0.0; // views -> clicks
    $use_rate   = $views > 0 ? round(($uses   / $views) * 100, 1) : 0.0; // views -> uses (conversion)

    $ctr_label   = feature_meter_ctr_label($click_rate);
    $use_label   = feature_meter_use_label($use_rate);
    $instruction = feature_meter_suggestion($click_rate, $use_rate);

    $table_actions['delete'] = $delete;
    $table_actions = build_table_actions($table_actions, $row['feature_name']);

    $body[] = [
        $row['feature_name'],
        $views,
        $clicks,
        $uses,
        $unique_users,
        $click_rate . '%',
        $ctr_label,
        $use_rate   . '%',
        $use_label,
        $instruction,
        $table_actions,
    ];
}

?>

<main class="row justify-content-center m-0" role="main">

    <form method="get" class="card col-md-8 mb-3">
        <div class="card-body row g-3">

            <div class="col-md-3">
                <label class="form-label">Filter by user_id</label>
                <input type="number" class="form-control" name="user_id"
                       value="<?php echo htmlspecialchars((string)$user_id_filter, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Start date</label>
                <input type="date" class="form-control" name="start_date"
                       value="<?php echo htmlspecialchars((string)$start_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">End date</label>
                <input type="date" class="form-control" name="end_date"
                       value="<?php echo htmlspecialchars((string)$end_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2">Apply</button>
                <a href="<?= site_url('/admin/'. $page['slug']) ?>"
                   class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>

            <div class="col-12 mt-2">
                <small class="text-muted">
                    If no date is provided, the report uses the last 90 days by default.
                </small>
            </div>

        </div>
    </form>

    <?php
    echo table([
        'head' => [
            'Feature',
            'Views',
            'Clicks',
            'Uses',
            'Unique users (use)',
            'CTR',
            'CTR note',
            'Conv.',
            'Conv. note',
            'Instructions',
            'Actions',
        ],
        'body' => $body,
    ]);

    $instructions = '';

    // CTR rules
    $instructions .= "
    <h4>CTR - Click rate (views → clicks)</h4>
    <ul class='mb-4'>";
    foreach (feature_meter_ctr_label() as $item)
    {
        $until = !is_null($item['until'])
            ? "< {$item['until']}%"
            : "≥ last threshold";

        $instructions .= "<li><strong>{$until}</strong> – {$item['label']}</li>";
    }
    $instructions .= "</ul>";

    // Use / conversion rules
    $instructions .= "
    <h4>Conv. - Usage Frequency (views → uses)</h4>
    <ul class='mb-4'>";
    foreach (feature_meter_use_label() as $item)
    {
        $until = !is_null($item['until'])
            ? "< {$item['until']}%"
            : "≥ last threshold";

        $instructions .= "<li><strong>{$until}</strong> – {$item['label']}</li>";
    }
    $instructions .= "</ul>";

    // High-level instructions rules (future vs existing)
    $rules = feature_meter_suggestion();

    $instructions .= "
    <h4>How to interpret the final instructions</h4>";

    $instructions .= "<h5>Future features (use = 0)</h5><ul class='mb-3'>";
    foreach ($rules as $rule) {
        if ($rule['segment'] === 'future') {
            $instructions .= "<li>{$rule['label']}</li>";
        }
    }
    $instructions .= "</ul>";

    $instructions .= "<h5>Existing features (use > 0)</h5><ul class='mb-0'>";
    foreach ($rules as $rule) {
        if ($rule['segment'] === 'existing') {
            $instructions .= "<li>{$rule['label']}</li>";
        }
    }
    $instructions .= "</ul>";
    ?>

    <div class="col-12">
        <?= block('alert',
        [
            'body'         => $instructions,
            'variation'    => 'alert-disclaimer',
            'close_button' => false,
            'color'        => 'info'
        ]); ?>
    </div>

</main>

<?php
include_once AREAS_PATH .'/admin/include/script_libs.php';
