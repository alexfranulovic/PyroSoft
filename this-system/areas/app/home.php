<?php
if (!isset($seg)) exit;

include "include/head.php";
include "include/menu.php";
?>

<!--
 *
 * You can edit/delete everything.
 * Your creativity rules!
 *
-->
<main class="container">
<div class="card animate-top">

  <div class="col-lg left-box">
  <div class="card-body ">
    <h1>Let's get the party started.</h1>
    <p>The PyroSoft is getting it ecosystem richer every day, <a class="easter-egg" target="_blank" href="https://www.youtube.com/watch?v=4pcusbhhfQA" rel="nofollow">brick by brick.</a></p>
    <p>We recommend starting with the following</p>

    <?php
    $steps = [
      [
        'title' => "Follow on <a class='link' target='_blank' href='https://github.com/alexfranulovic/PyroSoft'>GitHub</a>",
        'icon' => 'fab fa-github',
      ],
      [
        'title' => "Follow the creator on <a class='link' target='_blank' href='https://www.linkedin.com/in/alex-franulovic/'>LinkedIn</a>",
        'icon' => 'fab fa-linkedin',
      ],
      [
        'title' => 'Start your project',
        'icon' => 'fas fa-play',
      ],
    ];

    echo block('progress', [
      'variation'   => 'progress_steps_detailed_vertical',
      'steps'       => $steps,
      'color' => 'st',
      'step_active' => 1,
    ]);
    ?>

    <a href="<?= site_url('/login') ?>" class="btn btn-st">Start your project now</a>
    <small>v<?= PYROSOFT_VERSION ?></small>
  </div>
  </div>

  <div class="col-lg right-box">
    <img alt="PyroSoft logotype" src="<?= file_url('images/brand', false, 'imagotype-black-st-colab.png') ?>">
  </div>

</main>

<!--
 *
 * I bet you create extraordinary things with this engine
 * Let's go!!!!
 *
-->

<?php include "include/footer.php"; ?>
