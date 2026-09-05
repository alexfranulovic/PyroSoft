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
?>


<?php
$structuredData = seo_structred_data($page, true, $info['about_business']['type'] ?? 'local_business');
echo $structuredData;
?>
