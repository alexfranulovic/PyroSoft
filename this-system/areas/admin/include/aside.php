<aside>
<div class="offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">

  <div class="offcanvas-header">
    <a class="navbar-brand" href="<?= pg ?>/admin/administration" aria-label="<?= $info['name'] ?>" title="<?= $info['name'] ?>">
      <img class="logotype-light" src="<?= file_url('images/brand', false, 'imagotype-black-st.png') ?>" loading="lazy">
      <img class="logotype-dark" src="<?= file_url('images/brand', false, 'imagotype-white-st.png') ?>" loading="lazy">
    </a>
    <!--<h5><?= $info['name'] ?></h5>-->
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body">
  <?php
  $counter = 1;
  $menu = get_menu(270, false);
  foreach ($menu as $item)
  {
    if ($item['depth'] == 0)
    {
      echo "
      <section class='list'>
      <span>{$item['title']}</span>
      <ul class='list-unstyled'>";

      foreach ($item['childs'] as $subitem)
      {
        if (empty($subitem['childs']))
        {
          echo "
          <li>
            <a class='btn {$subitem['active']}' {$subitem['formatted_url']} {$subitem['attributes']} title='{$subitem['title']}'>
            ". icon($subitem['icon']) ." &nbsp;&nbsp; {$subitem['title']}</a>
          </li>";
        }

        else
        {
          echo "
          <li>
            <button class='btn btn-toggle collapsed' data-bs-toggle='collapse' data-bs-target='#subitem{$counter}' aria-expanded='{$subitem['expanded']}'>
              ". icon($subitem['icon']) ." &nbsp;&nbsp; {$subitem['title']}
            </button>
            <div class='collapse {$subitem['show']}' id='subitem{$counter}'>
            <ul class='list-unstyled'>";

            foreach($subitem['childs'] as $child)
            {
              echo "
              <li>
                <a class='{$child['active']}' {$child['formatted_url']} {$child['attributes']} title='{$child['title']}'>
                ". icon($child['icon']) ." &nbsp;&nbsp; {$child['title']}</a>
              </li>";
            }

            echo "
            </ul>
            </div>
          </li>";
        }
        $counter++;
      }

      echo "
      </ul>
      </section>";
    }
  }
  ?>
  </div>

</div>
</aside>
