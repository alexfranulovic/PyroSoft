<?php
if (!isset($seg)) exit;

/**
 * "Cancelar assinatura" -- customer-facing cancellation screen.
 *
 * URL: /cancelar-assinatura?id=<subscription_id> (linked from the "cancelar"
 * button on "Minhas assinaturas"). A second GET shape,
 * ?id=<subscription_id>&canceled=1, is what the confirm button below
 * redirects to on success -- a small standalone "done" screen so a reload
 * of that URL (or a bookmark/back-button) never re-submits the form.
 *
 * Backend: posts to `cancel-subscription` (api.php), which -- on purpose --
 * does NOT call pyrosales' finalize_plan_subscription() here. It only flips
 * auto_renew off, so the customer keeps whatever they already paid for
 * through the end of the current period; the actual status flip to
 * 'canceled' happens later, once that period really ends (see the note left
 * in api.php + the new close_non_renewing_plan_subscriptions() in
 * pyrosales for why, and how that lands on 'canceled' rather than
 * 'expired').
 */

global $current_user, $info;

add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/card-hero.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/plans-subscriptions/assets/styles/my-subscriptions.css', 'url') ."'>");
// add_asset('footer', "<script src='" . plugin_path('/plans-subscriptions/assets/scripts/cancel-subscription.js', 'url') . "' defer></script>");

include_once AREAS_PATH .'/app/include/head.php';
include_once AREAS_PATH .'/app/include/menu.php';

$user_id         = (int) ($current_user['id'] ?? 0);
$subscription_id = (int) ($_GET['id'] ?? 0);
$subscription    = $subscription_id > 0 ? get_subscription($subscription_id) : null;
$owns_it         = !empty($subscription['id']) && (int) $subscription['user_id'] === $user_id;
$plan            = $owns_it ? get_plan($subscription['plan_id']) : null;
$cancelable      = $owns_it && in_array($subscription['status'], ['active', 'trialing', 'past_due'], true);
$just_canceled   = $owns_it && !empty($_GET['canceled']);
$status          = (string) ($subscription['status'] ?? '');
$recurrence = format_subscription_recurrence(
    (int) ($subscription['plan_interval_count'] ?? 1),
    (string) ($subscription['plan_interval_unit'] ?? 'month')
);
?>

<main class="container cancel-subscription-page" role="main" style="max-width: 720px;">

<?php if ($just_canceled): ?>

    <div class="card hero">
    <div class="card-body">
        <div class="big-icon success"><?= icon('fas fa-check') ?></div>
        <h1 class="h3 mb-2">Assinatura cancelada</h1>
        <p>
            A renovação automática foi desativada.
            <?php if (!empty($subscription['current_period_end']) || !empty($subscription['next_billing_at'])): ?>
            Você continua com acesso normalmente até <strong><?= format_date_long_ptbr((string)($subscription['current_period_end'] ?? $subscription['next_billing_at'])) ?></strong>.
            <?php endif; ?>
        </p>
        <a class="btn btn-st" href="<?= get_url_page('minhas-assinaturas', 'full') ?>">Voltar para minhas assinaturas</a>
    </div>
    </div>

<?php elseif (!$owns_it): ?>

    <section class="card hero">
    <div class="card-body">
        <div class="big-icon error"><?= icon('fas fa-x') ?></div>
        <h1>Assinatura não encontrada</h1>
        <p>Não encontramos essa assinatura na sua conta.</p>
        <a class="btn btn-st" href="<?= get_url_page('minhas-assinaturas', 'full') ?>">Voltar para minhas assinaturas</a>
    </div>
    </section>

<?php elseif (!$cancelable): ?>

    <section class="card hero">
    <div class="card-body">
        <div class="big-icon error"><?= icon('fas fa-x') ?></div>
        <h1>Esta assinatura não pode ser cancelada no status atual</h1>
        <p>Status atual: <?= general_stats($subscription['status'], 'subscription_status', 'button') ?></p>
        <a class="btn btn-st" href="<?= get_url_page('minhas-assinaturas', 'full') ?>">Voltar para minhas assinaturas</a>
    </div>
    </section>

<?php else: ?>

<section class="">
<div class="container">

    <?php
    $next_billing = $cancelable
        ? "<small>Sua assinatura será renovada em ". format_date_long_ptbr($subscription['next_billing_at'], false) .".</small>"
        : '';
    ?>

    <article class='col-12'>
    <div class='about-plan'>
        <div class='details'>
        <?= icon('fas fa-heart-crack') ?>
        <div>
            <div class='title'>
                <h2><?= e((string) ($plan['name'] ?? '')) ?></h2> ·
                <?= general_stats($status, 'subscription_status', 'button') ?>
            </div>
            <p><?= $recurrence ?></p>
            <?= $next_billing ?>
        </div>
        </div>
        <div class='cta'>
            <a class='btn btn-st' href='<?= site_url('/planos') ?>'>Ajustar plano</a>
        </div>
    </div>
    </article>

    <?php
    echo block('alert',
    [
        'body' => icon('fas fa-warning') ."&nbsp; A renovação automática será desativada, mas você continua com acesso normalmente até
            **". format_date_long_ptbr((string)($subscription['current_period_end'] ?? $subscription['next_billing_at'] ?? '-')) ."**
            - seus benefícios não são removidos agora.",
        'variation' => 'alert-3',
        'close_button' => false,
        'color' => 'warning',
    ]);
    ?>

    <p>Para nos ajudar a melhorar o <?= e((string)($info['name'] ?? '')) ?>, conte rapidinho sobre o motivo do cancelamento.</p>

    <form class='form-row' data-send-without-reload action="<?= rest_api_route_url('cancel-subscription') ?>" method="POST">
        <?= input('hidden', 'insert', ['name' => 'subscription_id', 'Value' => $subscription_id]) ?>


        <?= input('selection_type', 'insert', [
              'size'     => 'col-12',
              'type'     => 'radio',
              // 'variation'=> 'group-block',
              'label'    => 'Qual desses motivos mais se aproxima do seu caso?',
              'name'     => 'reason_category',
              'Options'  => get_subscription_cancel_reason_options(),
              'Required' => true,
            ]) ?>

        <?= input('textarea', 'insert', [
              'size'     => 'col-12',
              'label'    => 'Quer contar mais alguns detalhes?',
              'name'     => 'reason',
              'Required' => true,
            ]) ?>

        <?= input('selection_type', 'insert', [
              'size'     => 'col-12',
              'type'     => 'radio',
              // 'variation'=> 'group-block',
              'label'    => 'Qual a probabilidade de você voltar a assinar no futuro?',
              'name'     => 'return_likelihood',
              'Options'  => get_subscription_return_likelihood_options(),
              'Required' => true,
            ]) ?>

        <div class="d-flex gap-2 mt-4">
        <a class="btn btn-outline-st" href="<?= get_url_page('minhas-assinaturas', 'full') ?>">Voltar</a>
        <button type="submit" class="btn btn-danger" data-cancel-subscription-submit>
            <?= icon('fas fa-ban') ?> Confirmar cancelamento
        </button>
        </div>

    </form>
</div>
</section>

<?php endif; ?>

</main>

<?php include_once AREAS_PATH .'/app/include/footer.php'; ?>
