<?php
require_once "script_libs.php";

$footer_format = $page['page_settings']['footer']['format'] ?? ($config['branding']['footer']['format'] ?? 'full');
?>

<?php if ($footer_format == 'full') : ?>
<footer class="full">
<div class="row">

  <?php
  $counter = 1;
  $menu = get_menu(315, false);
  foreach ($menu as $item)
  {
    if ($item['depth'] == 0)
    {
      echo "
      <article class='col-md-4'>
      <div>
      <h3>{$item['title']}</h3>
      <ul>";

      foreach ($item['childs'] as $subitem)
      {
        if (empty($subitem['childs']))
        {
          echo "
          <li>
            <a {$subitem['formatted_url']} {$subitem['attributes']} title='{$subitem['title']}'>
            ". icon($subitem['icon']) ." {$subitem['title']}</a>
          </li>";
        }

        $counter++;
      }

      echo "
      </ul>
      </div>
      </article>";
    }
  }
  ?>

  <article class="col-md-4">
    <?php
    $social_media = [
      'title' => "Nos siga!",
      'content' => $info['social_media'],
    ];
    echo block('social_media', $social_media);
    ?>
  </article>

  <div class="developed-by">
    <span>Pensado por: <?= $config['developer']['author'] ?> &copy;</span>
    <!-- Developed by: <?= $config['developer']['author'] ?> &copy; -->
  </div>

</div>
</footer><!-- /footer -->

<section id="about-system" class="module bg-dark py-3">
<img loading="lazy" alt="SSL" src="<?= pg ?>/uploads/images/icons/ssl.png" width='105' height="40">
<div class="copyright">
  <span>Copyright &copy; <?= $info['name'] .' '. date('Y') ?></span>
  <span>Todos os direitos reservados.</span>
  <span><a href="<?= pg ."/termos-condicoes" ?>">Termos de uso</a> | <a href="<?= pg ."/politica" ?>">Políticas de privacidade</a></span>
</div>
</section><!--/#about-system -->

<?php elseif ($footer_format == 'medium') : ?>
<footer class="medium">
  <span>Copyright &copy; <?= $info['name'] .' '. date('Y') ?></span>
  <span>Todos os direitos reservados.</span>
</footer>
<?php endif; ?>
