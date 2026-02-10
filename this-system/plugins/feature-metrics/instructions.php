<?php
if (!isset($seg)) exit;

/**
 * Classificar CTR (visualizações -> cliques)
 */
function feature_metric_ctr_label(float $ctr = 0)
{
    $rules = [
        'low' => [
            'until' => 2.0,
            'label' => 'Muito baixo (sem demanda)'
        ],
        'weak' => [
            'until' => 5.0,
            'label' => 'Interesse fraco'
        ],
        'moderate' => [
            'until' => 10.0,
            'label' => 'Interesse moderado'
        ],
        'high' => [
            'until' => 20.0,
            'label' => 'Alto interesse'
        ],
        'strong' => [
            'until' => null,
            'label' => 'Interesse muito forte'
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

    return 'Indefinido';
}

/**
 * Classificar taxa de uso (visualizações -> usos)
 * (Na prática é a "conversão" em uso.)
 */
function feature_metric_use_label(float $useRate = 0)
{
    $rules = [
        [
            'until' => 1.0,
            'label' => 'Apenas curiosidade',
        ],
        [
            'until' => 3.0,
            'label' => 'Baixo valor percebido',
        ],
        [
            'until' => 8.0,
            'label' => 'Útil para nicho específico',
        ],
        [
            'until' => 15.0,
            'label' => 'Útil para um bom grupo de pessoas',
        ],
        [
            'until' => null,
            'label' => 'Funcionalidade central / importante',
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

    return 'Indefinido';
}

/**
 * Instruções de alto nível com base em CTR e taxa de uso
 *
 * REGRA DE CONTEXTO:
 * - useRate == 0 → tratamos como "future feature" (ainda não existe)
 * - useRate > 0  → tratamos como "existing feature"
 *
 * Quando chamada com (0,0) → retorna o conjunto completo de regras para montar as instruções da tela.
 */
function feature_metric_suggestion(float $ctr = 0, float $useRate = 0)
{
    $rules = [
        // -------------------------------
        // FUTURAS FUNCIONALIDADES (useRate == 0)
        // -------------------------------
        [
            'segment'  => 'future',
            'label'    => 'CTR < 2% – ideia fria; mantenha estacionada ou remova.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c < 2.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR entre 2% e 5% – curiosidade fraca; só teste se o custo for muito baixo.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 2.0 && $c < 5.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR entre 5% e 10% – interesse moderado; bom candidato para entrevistas exploratórias ou um teste simples.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 5.0 && $c < 10.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR entre 10% e 20% – interesse forte; considere prototipar essa funcionalidade com escopo limitado.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 10.0 && $c < 20.0;
            },
        ],
        [
            'segment'  => 'future',
            'label'    => 'CTR ≥ 20% – interesse muito forte sem implementação; top candidata para design e desenvolvimento.',
            'criteria' => function ($c, $u, $isExisting) {
                return !$isExisting && $c >= 20.0;
            },
        ],

        // --------------------------------
        // FUNCIONALIDADES EXISTENTES (useRate > 0)
        // --------------------------------
        [
            'segment'  => 'existing',
            'label'    => 'CTR < 2% e USO < 1% – quase invisível e não usada; considere remover ou esconder.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c < 2.0 && $u < 1.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR < 5% mas USO ≥ 5% – “jóia escondida”; quem encontra usa bastante, melhore a descoberta.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c < 5.0 && $u >= 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR ≥ 20% e USO ≥ 15% – funcionalidade central; continue investindo e destaque no produto/marketing.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 20.0 && $u >= 15.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR ≥ 10% e USO ≥ 5% – alta prioridade; forte demanda, planeje roadmap e siga melhorando.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 10.0 && $u >= 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR ≥ 10% mas USO < 5% – usuários se interessam mas têm dificuldade de usar; revise UX, textos e onboarding.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 10.0 && $u > 0.0 && $u < 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'CTR entre 5% e 10% e USO entre 1% e 5% – interesse médio e uso médio; bom candidato para um teste de melhoria focado.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting && $c >= 5.0 && $c < 10.0 && $u >= 1.0 && $u < 5.0;
            },
        ],
        [
            'segment'  => 'existing',
            'label'    => 'Outros casos – monitorar, iterar e seguir acompanhando essas métricas.',
            'criteria' => function ($c, $u, $isExisting) {
                return $isExisting;
            },
        ],
    ];

    // Quando chamada sem valores, retorna o ruleset para montar instruções na tela
    if ($ctr == 0.0 && $useRate == 0.0) {
        return $rules;
    }

    $isExisting = ($useRate > 0.0);

    foreach ($rules as $rule) {
        if (call_user_func($rule['criteria'], $ctr, $useRate, $isExisting)) {
            return $rule['label'];
        }
    }

    return 'Indefinido';
}


/**
 * Render instructions block for feature metrics,
 * including CTR rules, Conversion rules, and contextual suggestions.
 *
 * @return string HTML
 */
function render_feature_instructions(): string
{
    $html = '';

    // -------------------------------
    // CTR Rules
    // -------------------------------
    $html .= "
    <h4>CTR – Taxa de clique (usuários que visualizaram → usuários que clicaram)</h4>
    <ul class='mb-4'>";

    foreach (feature_metric_ctr_label() as $item)
    {
        $until = !is_null($item['until'])
            ? "< {$item['until']}%"
            : "≥ último limite";

        $html .= "<li><strong>{$until}</strong> – {$item['label']}</li>";
    }

    $html .= "</ul>";

    // -------------------------------
    // Use / Conversion Rules
    // -------------------------------
    $html .= "
    <h4>Conv. – Frequência de uso (usuários que visualizaram → usuários que usaram)</h4>
    <ul class='mb-4'>";

    foreach (feature_metric_use_label() as $item)
    {
        $until = !is_null($item['until'])
            ? "< {$item['until']}%"
            : "≥ último limite";

        $html .= "<li><strong>{$until}</strong> – {$item['label']}</li>";
    }

    $html .= "</ul>";

    // -------------------------------
    // General suggestion rules
    // -------------------------------
    $rules = feature_metric_suggestion();

    $html .= "<h4>Como interpretar as instruções finais</h4>";

    // Future features
    $html .= "<h5>Funcionalidades futuras (Uso = 0)</h5><ul class='mb-3'>";
    foreach ($rules as $rule)
    {
        if ($rule['segment'] === 'future') {
            $html .= "<li>{$rule['label']}</li>";
        }
    }
    $html .= "</ul>";

    // Existing features
    $html .= "<h5>Funcionalidades existentes (Uso > 0)</h5><ul class='mb-0'>";
    foreach ($rules as $rule)
    {
        if ($rule['segment'] === 'existing') {
            $html .= "<li>{$rule['label']}</li>";
        }
    }
    $html .= "</ul>";

    return $html;
}
