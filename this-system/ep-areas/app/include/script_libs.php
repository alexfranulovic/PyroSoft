<script src="<?= pg ?>/dist/scripts/app.js"></script>
<script defer src="<?= pg ?>/dist/scripts/libs.js"></script>

<?= footer() ?>

<?php feature('security-js'); ?>

<?php
if (!isset($_COOKIE['cookie-consent']))
{
  echo block('modal', [
    'id' => 'cookies-alert',
    'attributes' => 'data-modal: (true);',
    'static' => 'true',
    'variation' => 'modal_default',
    'title' => 'Alerta de Cookies',
    'body' => "A {$info['name']} utiliza cookies para melhorar sua experiência em nosso site, personalizar conteúdo e anúncios, fornecer recursos de mídia social e analisar nosso tráfego. Essas informações podem ser compartilhadas com nossos parceiros de publicidade e análise, de acordo com nossos <a href='#'>termos de uso</a>.",
    'footer' => "
    <button data-bs-dismiss='modal' class='btn btn-st' data-accept-cookies='true'>Aceitar</button>
    <button data-bs-dismiss='modal' class='btn btn-outline-nd'>Recusar</button>
    "
  ]);
}


if (in_array('feature-metrics', $config['activated_plugins']))
{
  /**
   *
   * Exclusive for subscribers.
   *
   */
  echo block('modal', [
    'id' => 'premium-alert',
    // 'attributes' => 'data-modal: (true);',
    'variation' => 'modal_news',
    'close_button' => true,
    // 'title' => 'Conteúdo exclusivo para assinantes',
    'dialog' => 'modal-dialog-centered',
    'animation' => '',
    'body' => "

      <div class='header'>
        <h2>Privilégios a um clique</h2>
        <p>Apenas assinantes têm acesso completo às informações e funcionalidades desta área.</p>
      </div>

      <div class='row features'>".
        feature_item([
          'size' => 'col-4',
          'icon' => 'fas fa-lock',
          'description' => '+2000 modelos **exclusivas**',
        ]).

        feature_item([
          'size' => 'col-4',
          'icon' => 'fas fa-heart',
          'description' => 'Modelos premium **entrarão em contato** com você',
        ]).

        feature_item([
          'size' => 'col-4',
          'icon' => 'fas fa-eye',
          'description' => '**Desconto em assinaturas** de modelos',
        ])
      ."</div>

      <div class='row signatures-modal'>".

        signature_box_mini([
          'size' => 'col-4',
          'recurrence' => '24h',
          'discount' => 'R$ 15,90',
          'price' => 'R$ 9,90',
          'active' => false,
        ]).

        signature_box_mini([
          'size' => 'col-4',
          'highlight' => 'Popular',
          'recurrence' => 'Mensal',
          'discount' => 'R$ 15,90',
          'price' => 'R$ 9,90',
          'active' => true,
        ]).

        signature_box_mini([
          'size' => 'col-4',
          'recurrence' => '15 dias',
          'discount' => 'R$ 15,90',
          'price' => 'R$ 9,90',
          'active' => false,
        ])

      ."</div>

      <a class='btn btn-primary btn-lg btn-block' href='".pg."/planos'>Assinar agora</a>

      <div class='d-flex content-center'>
        <p class='badge rounded-pill text-bg-subtle-success'>". icon('fas fa-check-circle') ." Sigilo e segurança garantidos</p>
      </div>

      <p class='small text-center'>
        Já é assinante? <a href='#' class='fw-bold' data-open-login='true'>Faça login</a>
      </p>
    ",
  ]);

  /**
   *
   * Future feature modal (Fake Door test)
   *
   */
  echo block('modal', [
    'id' => 'future-feature',
    'variation' => 'modal_news',
    'close_button' => true,
    'animation' => '',
    'dialog' => 'modal-dialog-centered',
    'body' => "
      <div class='text-center px-3 py-2'>

        <h4 class='fw-bold mb-2'>Obrigado pelo seu interesse!</h4>

        ". svg('maintenance-amico') ."

        <p>
          <strong><span id='future-feature-name'>Este recurso</span></strong> ainda está em desenvolvimento.
          Estamos finalizando os últimos ajustes para lançar em breve.
        </p>

        <p>Quer que a gente te avise assim que estiver disponível?</p>

        <button class='btn btn-primary btn-block' data-bs-dismiss='modal' data-notify-future-feature>
          Me avise quando lançar
        </button>

        <small class='text-muted'>Sem spam. Só um aviso.</small>

        <button class='btn btn-link btn-block' data-bs-dismiss='modal'>
          Vou deixar essa passar
        </button>

      </div>
    ",
    'footer' => ""
  ]);

}

?>


<?php
//echo(seo_structred_data($articleData));

/*$data = [
    'title' => 'Example Article',
    'alternativeHeadline' => 'An Example of Article Structured Data',
    'image' => 'http://example.com/image.jpg',
    'author' => ['name' => 'John Doe'],
    'publisher' => [
        'name' => 'Example Publisher',
        'logo' => 'http://example.com/logo.jpg'
    ],
    'created_at' => '2021-01-14 14:28:03',
    'updated_at' => '2021-01-14 14:28:03',
    //'expires' => '2021-01-15 14:28:03',
    'article_body' => 'This is an example of the body of an article.',
];*/


$structuredData = seo_structred_data($page, true, $info['about_business']['type'] ?? 'local_business');
echo $structuredData;
?>
