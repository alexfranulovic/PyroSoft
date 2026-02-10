<?php
if(!isset($seg)) exit;

feature('fields-card');

global $pages_to_choose;
$pages_to_choose = [];
foreach (get_pages([ 'page_area' => true ]) as $page)
{
    if ($page['status_id'] != 1 ) continue;
    $pages_to_choose[] = [ 'value' => $page['id'], 'display' => $page['title'] ];
}

function menu_instructions()
{
    return [
        "Use Shift+".icon('fas fa-arrow-up')." e Shift+".icon('fas fa-arrow-down')." para navegar entre os itens.",
        "Utilize as setas para cima ".icon('fas fa-arrow-up')." e para baixo ".icon('fas fa-arrow-down')." para mover o item na lista.",
        "Use as setas do teclado ".icon('fas fa-arrow-left')." e ".icon('fas fa-arrow-right')." para aumentar e diminuir a profundidade.",
    ];
}


global $custom_menu_type_options;

$add_pages = input(
    'selection_type',
    'insert',
    [
        'type' => 'search',
        'size' => 'col-12',
        'label' => 'Página de listagem',
        'name' => 'menu-item[page][id]',
        'Options' => $pages_to_choose,
        'Required' => true
    ]
);

$custom_links = input(
    'basic',
    'update',
    [
        'size' => 'col-12',
        'label' => 'URL',
        'name' => 'menu-item[custom_links][url]',
        'Required' => true
    ]
).
input(
    'basic',
    'update',
    [
        'size' => 'col-12',
        'label' => 'Texto do link',
        'name' => 'menu-item[custom_links][title]',
        'Required' => true
    ]
)
.input(
    'basic',
    'update',
    [
        'size' => 'col-12',
        'label' => 'Função de exibição',
        'name' => 'menu-item[custom_links][function_view]'
    ]
);

$user_links =
input(
    'basic',
    'update',
    [
        'size' => 'col-12',
        'label' => 'Texto do link',
        'name' => 'menu-item[user_links][title]',
        'Required' => true
    ]
).
input(
    'selection_type',
    'update',
    [
        'size' => 'col-12',
        'type' => 'checkbox',
        'name' => 'menu-item[user_links][url]',
        'Options' => [
            ['value' => "user-logout", 'display' => 'Logout'],
        ],
        'Required' => true
    ]
);

$groups = input(
    'hidden',
    'insert',
    [ 'name' => "menu-item[groups][]" ]
).
input(
    'basic',
    'update',
    [
        'size' => 'col-12',
        'label' => 'Titulo do grupo',
        'name' => 'menu-item[groups][title]',
        'Required' => true
    ]
);

function add_menu_item_form(string $fields = '')
{
    return $user_links = "
    <form class='add-menu-item' method='POST' action='". rest_api_route_url("add-menu-item") ."'>
        ". $fields ."
        <button type='submit' class='btn btn-outline-info btn-sm' title='Adicionar'>Adcionar ao menu</button>
    </form>";
}

global $menu_type_options;
$menu_type_options = [
    [
      'title' => "Grupo",
      'content' => add_menu_item_form($groups),
    ],
    [
      'title' => "Páginas",
      'content' => add_menu_item_form($add_pages),
    ],
    [
      'title' => "Personalizado",
      'content' => add_menu_item_form($custom_links),
    ],
    [
      'title' => "Links de usuário",
      'content' => add_menu_item_form($user_links),
    ],
];


$menu_type_options += array_merge($menu_type_options, $custom_menu_type_options);





function common_inputs_for_menu(string $type_form, string $selector, $data = null, $counter = 1)
{
    if ($selector == 'id')
    return input(
        'hidden',
        $type_form,
        [
            'name' => "menu-item[$counter][id]",
            'Value' => $data,
            'Required' => true,
        ]
    );


    if ($selector == 'depth')
    return input(
        'hidden',
        $type_form,
        [
            'name' => "menu-item[$counter][depth]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'type')
    return input(
        'hidden',
        $type_form,
        [
            'name' => "menu-item[$counter][type]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'status_id')
    return input(
        'status_selector',
        $type_form,
        [
            'size' => 'col-md-6',
            'function_proccess' => 'general_status',
            'name' => "menu-item[$counter][status_id]",
            'Value' => $data,
            'Required' => true
        ]
    );

    if ($selector == 'title')
    return input(
        'basic',
        $type_form,
        [
            'attributes' => 'update-title:();',
            'size' => 'col-md',
            'label' => 'Título',
            'name' => "menu-item[$counter][title]",
            'Value' => $data,
            'Required' => true,
        ]
    );

    if ($selector == 'icon')
    return input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Ícone',
            'name' => "menu-item[$counter][icon]",
            'Value' => $data,
        ]
    );

    if ($selector == 'function_view')
    return input(
        'basic',
        'update',
        [
            'size' => 'col-md-6',
            'label' => 'Função de exibição',
            'name' => "menu-item[$counter][function_view]",
            'Value' => $data,
        ]
    );

    if ($selector == 'attributes')
    return input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Atributos do Input',
            'name' => "menu-item[$counter][attributes]",
            'Value' => $data,
        ]
    );

    if ($selector == 'which_users')
    return input(
    'selection_type',
        'update',
        [
            'size' => 'col-md-6',
            'label' => 'Quem pode ver?',
            'name' => "menu-item[$counter][which_users]",
            'Value' => $data,
            'Options' => [
                ['value' => 'everyone', 'display' => 'Todos'],
                ['value' => 'logged_in', 'display' => 'Usuários logados'],
                ['value' => 'logged_out', 'display' => 'Usuários não logados'],
            ],
            'Required' => true,
        ]
    );

    if ($selector == 'style')
    return input(
    'selection_type',
        'update',
        [
            'size' => 'col-md-6',
            'label' => 'Estilo do item',
            'name' => "menu-item[$counter][style]",
            'Value' => (($type_form == 'update') ? $data : 'generic') ?? '',
            'Options' => [
                ['value' => 'generic', 'display' => 'Padrão'],
                ['value' => 'button', 'display' => 'Botão'],
            ],
            'Required' => true,
        ]
    );
}



function menu_inputs_for_page(string $type_form, $counter, array $data = [])
{
    global $pages_to_choose;

    $type = 'page';

    $res = '<div class="form-row row">';
    $res.= input(
        'selection_type',
        $type_form,
        [
            'type' => 'search',
            'size' => 'col-12',
            'label' => 'Página de listagem',
            'name' => "menu-item[$counter][url]",
            'Options' => $pages_to_choose,
            'Value' => $data['url'] ?? '',
            'Required' => true
        ]
    );
    $res.= common_inputs_for_menu($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'depth', $data['depth'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'type', $type, $counter);
    $res.= common_inputs_for_menu($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'icon', $data['icon'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'which_users', $data['which_users'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'style', $data['style'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}

function menu_inputs_for_custom_links(string $type_form, $counter, array $data = [])
{
    $type = 'custom_links';

    $res = '<div class="form-row row">';
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-12',
            'label' => 'URL',
            'name' => "menu-item[$counter][url]",
            'Value' => $data['url'] ?? '',
        ]
    );
    $res.= common_inputs_for_menu($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'depth', $data['depth'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'type', $type, $counter);
    $res.= common_inputs_for_menu($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'icon', $data['icon'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'which_users', $data['which_users'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'style', $data['style'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}

function menu_inputs_for_user_links(string $type_form, $counter, array $data = [])
{
    $type = 'user_links';

    $res = '<div class="form-row row">';
    $res.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-12',
            'label' => 'URL',
            'name' => "menu-item[$counter][url]",
            'Value' => $data['url'] ?? '',
            'readonly' => true,
            'Required' => true,
        ]
    );
    $res.= common_inputs_for_menu($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'depth', $data['depth'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'type', $type, $counter);
    $res.= common_inputs_for_menu($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'icon', $data['icon'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'which_users', $data['which_users'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'style', $data['style'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'attributes', $data['attributes'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'function_view', $data['function_view'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}

function menu_inputs_for_groups(string $type_form, $counter, array $data = [])
{
    $type = 'groups';

    $res = '<div class="form-row row">';
    $res.= common_inputs_for_menu($type_form, 'id', $data['id'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'depth', $data['depth'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'type', $type, $counter);
    $res.= common_inputs_for_menu($type_form, 'title', $data['title'] ?? '', $counter);
    $res.= common_inputs_for_menu($type_form, 'icon', $data['icon'] ?? '', $counter);
    $res.= "</div>";

    return $res;
}


function menu_inputs_select_type(string $type_item = '', string $type_form = 'update', $counter = 1, array $data = [])
{
    if (empty($type_item)) return 'No selected type.';

    $function = "menu_inputs_for_$type_item";
    return $function($type_form, $counter, $data);
}


/**
 * Generate a menu management form for a content management system.
 *
 * @param string $type_form - The type of form, either 'insert' for creating a new record or 'update' for editing an existing one.
 * @param int $counter - An optional counter used for form fields (default is 1).
 *
 * @return int - The updated counter value.
 */
function manage_menu_form(string $type_form = 'insert', int $counter = 1)
{
    global
            $info,
            $routes_to_choose,
            $page,
            $pages_to_choose,
            $menu_type_options;

    $id = id_by_get();

    $menu = get_result("SELECT * FROM tb_menus WHERE id = '{$id}' AND type = 'list'");

    $type_form = $id
        ? 'update'
        : 'insert';

    if (count($menu) == 0) {
        $type_form = 'insert';
    }

    if ($type_form == 'update')
    {
        $menu_panel['hooks_out'][] = [
            'title' => 'Duplicar',
            'url'  => rest_api_route_url("duplicate-record?id={$id}&table=tb_menus&foreign_key=menu_id"),
            'attr'  => 'data-controller: (duplicate);',
            'color' => 'outline-info',
            'pre_icon' => 'fas fa-copy',
        ];
    }

    if ($type_form == 'update')
    {
        $menu_panel['hooks_out'][] = [
            'title' => 'Apagar',
            'url'  => rest_api_route_url("delete-record?id={$id}&table=tb_menus&foreign_key=menu_id"),
            'attr'  => 'data-controller: (delete);',
            'color' => 'outline-danger',
            'pre_icon' => 'fas fa-trash',
        ];
    }

    ?>

    <section class="row menu-management">

    <section class="col-12">
    <div class="card box-fields">
        <form method="GET" action="" class="card-body row content-center">
            <?= input(
            'selection_type',
                'update',
                [
                    'size' => 'col-md-8',
                    'type' => 'search',
                    'label' => 'Selecione o menu para editar',
                    'name' => 'id',
                    'Query' => "SELECT id as value, title as display FROM tb_menus WHERE type = 'list'",
                    'Value' => $id,
                ]
            ).
            input(
            'submit_button',
            $type_form,
            [
                'size' => 'col',
                'class' => 'btn btn-outline-nd btn-block',
                'Value' => 'Selecionar'
            ])?>

            <div class="col-12">
                <a href="<?= get_url_page($page['id'], 'full') ?>">ou criar um novo menu.</a>
            </div>

        </form>
    </div>
    </section>

    <?= crud_panel( $menu_panel ?? [] ) ?>

    <section class="col-md-4 col-xl-3">
        <h3 class="title">Adicionar itens</h3>
        <?= block('accordion', [ 'contents' => $menu_type_options ] ) ?>
    </section>

    <form class="col-md col-lg col-xl main-form" method="POST" data-send-ctrl-s data-send-without-reload action="<?= rest_api_route_url("manage-menu?mode={$type_form}") ?>">

        <h3 class="title">Estrutura do menu</h3>
        <div class="card box-fields">

        <div class="card-body" id='field-container-card'>

        <?php
        foreach (menu_instructions() as $instructions) {
            echo "<p class='instruction'>". format_text($instructions) ."</p>";
        }
        ?>

        <ul id="draggable-menu-items">

            <?php
            if ($type_form == 'update')
            {
                $items = get_results("SELECT * FROM tb_menus WHERE menu_id = '{$id}' ORDER BY order_reg ASC");
                foreach ($items as $item)
                {
                    $questions = menu_inputs_select_type($item['type'], 'update', $counter, $item);

                    echo field_content_card(
                        'update',
                        [
                            'delete' => true,
                            'click' => true,
                            'label' => $item['title'] ?? '',
                            'depth' => $item['depth'],
                            'counter' => "menu-item-$counter",
                            'questions' => $questions,
                            'id' => $item['id'],
                        ]
                    );

                    $counter++;
                }
            }
            ?>
        </ul>

            <hr>

        <h4 class="title">Configurações do menu</h4>
        <div class="row">
            <?= input(
            'basic',
            'update',
            [
                'size' => 'col-12',
                'label' => 'Nome do menu',
                'name' => 'title',
                'Value' => ($type_form=='update') ? $menu['title'] : '',
                'Required' => true
            ])?>

            <?= input(
            'basic',
            'update',
            [
                'size' => 'col-6',
                'label' => 'Slug',
                'name' => 'url',
                'Value' => ($type_form=='update') ? $menu['url'] : '',
                'Required' => true
            ])?>

            <?= input(
                'status_selector',
                $type_form,
                [
                    'size' => 'col-md-6',
                    'function_proccess' => 'general_status',
                    'name' => "status_id",
                    'Value' => ($type_form=='update') ? $menu['status_id'] : '',
                    'Required' => true
                ]
            ); ?>
            <?= input(
            'submit_button',
            $type_form,
            [
                'size' => 'col-md-4',
                'class' => 'btn btn-st btn-block',
                'Value' => ($type_form == 'update') ? 'Editar' : 'Cadastrar'
            ])
            ?>
        </div>
        </div>

        </div>

        <?php
        echo input('hidden', $type_form, [ 'name' => 'menu-items-order', 'Value' => $id ]);
        if ($type_form == 'update') echo input('hidden', $type_form, [ 'name' => 'id', 'Value' => $id ]);
        if ($type_form == 'update') echo input('hidden', $type_form, [ 'name' => 'delete_menus' ]);
        ?>
    </form>
    </section>

    <?php
    return $counter;
    unset($_SESSION['FormData']);
}


/**
 * Manage menu data in a content management system.
 *
 * @param array $data - An array containing menu data.
 * @param string $mode - The mode of operation, either 'insert' or 'update'.
 * @param bool $debug - A flag for enabling debugging (default is false).
 *
 * @return array - An array containing status information.
 */
function manage_menu_system(array $data, string $mode, bool $debug = false)
{
    $error        = false;
    $valid_data   = $data;

    $msg_type = 'toast';

    /*
     * Define the verifyer function.
     */
    if     ($mode == 'insert') $verifyer = 'inserted_id';
    elseif ($mode == 'update') $verifyer = 'affected_rows';
    else                       $error    = true;


    // Verify If there's an error
    if ($error) :
        $_SESSION['FormData'] = $Data;

    // Else do the routine
    else :

        // print_r($valid_data);

        $args = [
            'title' => $valid_data['title'] ?? 'Rascunho',
            'url' => $valid_data['url'] ?? null,
            'status_id' => $valid_data['status_id'] ?? '4',
            'type' => 'list',
        ];

        if ($mode == 'insert') {
            $args['created_at'] = 'NOW()';
        }
        else if ($mode == 'update')
        {
            $args['updated_at'] = 'NOW()';
            $args['data']  = $args;
            $args['where'] = where_equal_id($valid_data['id']);
        }

        // Lights, camera & action.
        $mode('tb_menus', $args, $debug);

        /*
         * Verify if inserted/updated correctaly.
         */
        if ($verifyer()) :

            $menu_id = ($mode == 'insert') ? inserted_id() : $valid_data['id'];

            unset($_SESSION['FormData']);

            $order_reg = 1;
            $prev = $parent_id = 0;

            $menu_order = $valid_data['menu-item'] ?? [];
            $updated_menu_order = $stack = [];
            $order_reg  = 1;

            foreach ($menu_order as $menu)
            {
                $depth = isset($menu['depth']) ? (int)$menu['depth'] : 0;

                while (!empty($stack) && end($stack)['depth'] >= $depth) {
                    array_pop($stack);
                }

                $parent_id = empty($stack) ? 0 : end($stack)['id'];

                $menu_mode = !empty($menu['id']) ? 'update' : 'insert';

                $args_item = [
                    'depth'         => $depth,
                    'order_reg'     => $order_reg,
                    'menu_id'       => $menu_id,
                    'parent_id'     => $parent_id,
                    'title'         => $menu['title'] ?? '',
                    'icon'          => $menu['icon'] ?? '',
                    'url'           => $menu['url'] ?? '',
                    'function_view' => $menu['function_view'] ?? '',
                    'type'          => $menu['type'] ?? 'list',
                    'which_users'   => $menu['which_users'] ?? 'everyone',
                    'style'         => $menu['style'] ?? 'generic',
                ];

                if ($menu_mode == 'insert') {
                    $args_item['created_at'] = 'NOW()';
                    insert('tb_menus', $args_item, false, $debug);
                    $current_id = inserted_id();
                }

                else {
                    $args_item['updated_at'] = 'NOW()';
                    $args_item['data']     = $args_item;
                    $args_item['where']    = where_equal_id($menu['id']);
                    update('tb_menus', $args_item, false, $debug);
                    $current_id = $menu['id'];
                }

                $stack[] = ['id' => $current_id, 'depth' => $depth];
                $updated_menu_order[] = ['id' => $current_id, 'depth' => $depth];

                $order_reg++;
            }

            $msg = alert_message("SC_TO_". strtoupper($mode), $msg_type);

        else :
            $_SESSION['FormData'] = $data;
            $msg = alert_message("ER_TO_". strtoupper($mode), $msg_type);
        endif;


        /*
         * Delete the wished modules if is updating.
         */
        if ($mode == 'update' AND !empty($valid_data['delete_menus']))
        {
            $delete_menus = explode( '-', $valid_data['delete_menus'] );
            foreach ($delete_menus as $delete_field) {
                query_it("DELETE FROM tb_menus WHERE id = '{$delete_field}'", $debug);
            }
        }

    endif;

    $res = [
        'code' => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg' => $msg ?? '',
        ],
    ];

    if ($mode == 'update') {
        $res['updated_menu_items_order'] = $updated_menu_order ?? [];
    }

    if ($mode == 'insert') {
        $res['redirect'] = get_url_page('gerenciar-menus', 'full'). "?id={$menu_id}";
    }

    return $res;
}


/**
 * Output JavaScript for dynamically managing form fields and user interactions.
 *
 * @param $counter - An optional counter for form fields.
 */
function menu_js_call_inputs($counter = 1)
{
    global $type_items,
           $routes_to_choose,
           $pages_to_choose;

    // Start a JavaScript block
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>

    const BASE_URL = window.BASE_URL;
    const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;

    $(document).ready(function ()
    {
        const maxDepth = 3;
        let counter = <?= $counter ?>;

        saveMenuOrder();

        (function () {
          // Evita instalar duas vezes
          if (!('fetch' in window) || window.fetch.__menuOrderHookInstalled) return;
          const _origFetch = window.fetch;
          window.fetch.__menuOrderHookInstalled = true;

          // Utilitário: espera até um seletor existir (com timeout)
          function waitFor(selector, { timeout = 2000, step = 50 } = {}) {
            return new Promise((resolve, reject) => {
              const t0 = performance.now();
              (function tick() {
                const el = document.querySelector(selector);
                if (el) return resolve(el);
                if (performance.now() - t0 > timeout) return reject(new Error('timeout'));
                setTimeout(tick, step);
              })();
            });
          }

          // Aplica os IDs novos
          async function applyUpdatedMenuOrder(payload) {
            // Aceita array ou string JSON
            let items = Array.isArray(payload) ? payload : null;
            if (!items && typeof payload === 'string') {
              try { items = JSON.parse(payload); } catch { /* ignore */ }
            }
            if (!Array.isArray(items) || !items.length) return;

            const containerSel  = '#draggable-menu-items';
            const inputSelector = 'input[name^="menu-item["][name$="[id]"]';

            try { await waitFor(`${containerSel} ${inputSelector}`); } catch {}

            const container = document.querySelector(containerSel);
            const inputs = container ? container.querySelectorAll(inputSelector) : null;
            if (!inputs || inputs.length === 0) {
              console.warn('[menu-order] inputs não encontrados.');
              return;
            }

            inputs.forEach((el, idx) => {
              const newId = items[idx] && items[idx].id;
              if (newId != null) el.value = newId;
            });

            if (typeof window.saveMenuOrder === 'function') {
              try { window.saveMenuOrder(); } catch (e) { console.warn('saveMenuOrder() falhou:', e); }
            }
          }

          // Patch do fetch: inspeciona respostas sem quebrar o fluxo
          window.fetch = async function (...args) {
            const res = await _origFetch.apply(this, args);

            // Clona a resposta pra ler o corpo sem consumir o stream original
            const clone = res.clone();
            clone.text().then(txt => {
              // Tenta parsear JSON; se falhar, ignora
              let json = null;
              try { json = JSON.parse(txt); } catch { /* ignore */ }
              if (!json) return;

              if ('updated_menu_items_order' in json) {
                applyUpdatedMenuOrder(json.updated_menu_items_order);
              }
            }).catch(() => { /* ignore */ });

            return res;
          };
        })();


        $(document).on('input', 'input[update-title]', function() {
            var newTitle = $(this).val();
            var $card = $(this).closest('.field-content-card');
            $card.find('.accordion-button .name').text(newTitle);
        });


        $('.add-menu-item').on('submit', function(e)
        {
            e.preventDefault();
            var $form = $(this);
            var formData = $form.serialize() + '&counter=' + counter;
            var url = $form.attr('action') || 'l';

            $.post(url, formData, function(response)
            {
                if (response != '') {
                    $('#draggable-menu-items').append(response);
                }
            })

            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Erro na requisição POST:', textStatus, errorThrown);
            });
            counter++;
        });

        $('form').on('click', '.remove-db', function ()
        {
            var button_id = $(this).attr('id');
            var data = $(this).attr('remove-item');

            if ($('#delete_menus').val() == '') {
                $('#delete_menus').val(data);
            } else if (data == undefined) {
                // do nothing
            } else {
                $('#delete_menus').val($('#delete_menus').val() + '-' + data);
            }

            var $deleted = $('#form-item-' + button_id);
            if (!$deleted.hasClass('active')) {
                $deleted.addClass('active');
            }

            $deleted.fadeOut(250, function ()
            {
                let currentDepth = getDepthClass($deleted);
                let newDepth = currentDepth > 0 ? Math.max(0, currentDepth - 1) : 0;
                let delta = currentDepth > 0 ? (currentDepth - newDepth) : 1;
                setDepthClass($deleted, newDepth);
                updateDescendants($deleted, delta, currentDepth);

                $(this).remove();
                saveMenuOrder();
            });
        });


        $(document).click(function(e)
        {
            if (!$(e.target).closest('.field-content-card').length) {
                $('.field-content-card').removeClass('active');
            }
            saveMenuOrder();
        });

        $(document).on('click', '.field-content-card', function()
        {
            $('.field-content-card').removeClass('active');
            $(this).addClass('active');
            saveMenuOrder();
        });

        function getDepthClass($item) {
          if ($item.length > 0) {
            let match = $item.attr('class').match(/depth-(\d+)/);
            return match ? parseInt(match[1]) : 0;
          }
        }

        function setDepthClass($item, depth) {
            if ($item.index() === 0) depth = 0;

            $item.removeClass(function (index, className) {
                return (className.match(/depth-\d+/) || []).join(' ');
            }).addClass(`depth-${depth}`);

            $item.find('input[name^="menu-item["][name$="[depth]"]').val(depth);
            return depth;
        }

        function saveMenuOrder()
        {
            let order = [];
            $("#draggable-menu-items .field-content-card").each(function(index, element) {
                let $item = $(element);
                let depth = getDepthClass($item);
                let id = $item.find('input[name^="menu-item["][name$="[id]"]').val() || "";
                order.push({
                    id: id,
                    depth: depth
                });
            });
            $("#menu-items-order").val(JSON.stringify(order));
        }

        function updateDescendants($parent, delta, parentOriginalDepth)
        {
            let $next = $parent.next('.field-content-card');
            while ($next.length)
            {
                let childDepth = getDepthClass($next);
                if (childDepth > parentOriginalDepth)
                {
                    let newChildDepth = childDepth - delta;
                    setDepthClass($next, newChildDepth);
                    $next = $next.next('.field-content-card');
                } else {
                    break;
                }
            }
            saveMenuOrder();
        }

        $(document).keydown(function(e)
        {
            if ($(e.target).is('input, textarea, select, [contenteditable="true"]'))
            {
                if (e.which === 37 || e.which === 39) {
                    return;
                }
            }

            let $active = $('.field-content-card.active');
            if (!$active.length) return;
            let currentDepth = getDepthClass($active);

            // Arrow right
            if (e.which === 39)
            {
                let prev = $active.prev('.field-content-card');
                if (prev.length)
                {
                    let prevDepth = getDepthClass(prev);
                    let newMax = ((prevDepth + 1) >= maxDepth) ? maxDepth : prevDepth + 1;
                    let newDepth = Math.min(currentDepth + 1, newMax);
                    setDepthClass($active, newDepth);
                }
            }

            // Arrow left
            else if (e.which === 37)
            {
                let originalDepth = currentDepth;
                let newDepth = Math.max(0, currentDepth - 1);
                let delta = currentDepth - newDepth;
                setDepthClass($active, newDepth);
                updateDescendants($active, delta, originalDepth);
            }

            // Arrow up
            else if (e.which === 38)
            {
                if (e.shiftKey)
                {
                    let prev = $active.prev('.field-content-card');
                    if (prev.length) {
                        $active.removeClass('active');
                        prev.addClass('active');
                    }
                }

                else
                {
                    let prev = $active.prev('.field-content-card');
                    if (prev.length)
                    {
                        if (prev.prev('.field-content-card').length === 0 ||
                            getDepthClass(prev.prev('.field-content-card')) <= (currentDepth + 1)) {
                            if (getDepthClass(prev) >= currentDepth) {
                                $active.insertBefore(prev);
                            }
                        }
                    }
                }
            }

            // Arrow down
            else if (e.which === 40)
            {
                if (e.shiftKey)
                {
                    let next = $active.next('.field-content-card');
                    if (next.length) {
                        $active.removeClass('active');
                        next.addClass('active');
                    }
                }

                else
                {
                    let next = $active.next('.field-content-card');
                    if (next.length)
                    {
                        if (getDepthClass($active) === 0 && getDepthClass(next) > 0) {
                            return;
                        }
                        if (next.next('.field-content-card').length === 0 ||
                            getDepthClass(next.next('.field-content-card')) <= (currentDepth + 1)) {
                            if (getDepthClass(next) >= currentDepth) {
                                $active.insertAfter(next);
                            }
                        }
                    }
                }
            }

            saveMenuOrder();
        });

    });
    </script>
    <?php
}
