<?php
if (!isset($seg)) exit;

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';
pwa_load_functions();

pageBaseTop();
?>
<div class="row mx-0 mb-3">

<div class="container my-5 pb-5">
<?php
$mode = $_GET['mode'] ?? 'plugin';
echo manage_pwa_form($mode);
?>

<?php if ($mode == 'generator') : ?>
<div class="bg-light rounded shadow mt-4 p-4">
    <h4 class="mb-3">Step by Step:</h4>
    <ol>
        <li>Extract the .zip on your project root</li>
        <li>
        Copy this script and past in your Head:
        <ul>
            <li><code>&lt;link rel="manifest" href="euphoric-pwa/manifest.json"&gt;</code></li>
        </ul>
        </li>
        <li>Let your project go ROCK'n ROLL!!</li>
    </ol>

</div>
<?php endif; ?>
</div>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
