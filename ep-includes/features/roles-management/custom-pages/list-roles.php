<?php
if (!isset($seg)) exit;

$Table       = 'tb_roles';
$foreign_key = 'role_id';

$result = query_it("SELECT * FROM $Table");

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

pageBaseTop();
?>

<!--main-container-part-->
<main class="row m-0" role="main">
    <table><tbody><tr line="0"><td class="w2ui-grid-data w2ui-grid-data-spacer" col="start" style="height: 0px; width: 0px"></td><td class="w2ui-grid-data" col="0" style="height: 0px; width: 567px;"></td><td class="w2ui-grid-data" col="1" style="height: 0px; width: 566px;"></td><td class="w2ui-grid-data-last" col="end" style="height: 0px; width: 0px;"></td></tr><tr id="grid_grid_rec_top" line="top" style="height: 50752px;">    <td colspan="2000"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1587" index="1586" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1586_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">DIS COMERCIO DE ELETRODOMESTICOS S.A</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1586_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://web.discomercio.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1588" index="1587" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1587_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">SABINO CONSULTORIA IMOBILIARIA LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1587_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1589" index="1588" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1588_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">LVT BRASIL CONSULTORIA DE IMOVEIS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1588_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://livato.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1590" index="1589" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1589_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">GBFI EMPREENDIMENTOS IMOBILIARIOS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1589_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1591" index="1590" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1590_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">GUSTAVO ZERO DALMONTE</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1590_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://easyrent.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1592" index="1591" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1591_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">UPIK - DECORACAO DE INTERIORES LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1591_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://arquitetodebolso.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1593" index="1592" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1592_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">TELLUS IMOVEIS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1592_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1594" index="1593" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1593_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">ATACADAO S.A.</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1593_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://www.atacadao.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1595" index="1594" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1594_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">A. S. DUTRA GESTAO IMOBILIARIA LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1594_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1596" index="1595" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1595_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">PREDIAL MARANATA NEGOCIOS IMOBILIARIOS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1595_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://www.predialmaranata.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1597" index="1596" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1596_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">ECHAVE SERVICOS DE APOIO ADMINISTRATIVOS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1596_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px">https://www.echave.com.br/</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1598" index="1597" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1597_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">VALERIA APARECIDA PEREIRA IMOVEIS</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1597_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1599" index="1598" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1598_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">J IMOBI GESTAO E NEGOCIOS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1598_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1600" index="1599" class="w2ui-even w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1599_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">APE CERTO NEGOCIOS IMOBILIARIOS LTDA</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1599_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_undefined" recid="undefined" line="1601" index="1600" class="w2ui-odd w2ui-record " style="height: 32px; "><td class="w2ui-grid-data-spacer" col="start" style="border-right: 0"></td><td class="w2ui-grid-data " id="grid_grid_data_1600_0" col="0" style=""><div style="max-height: 32px;margin-right: 20px">EDGARD GIMENEZ CANCIAM NEGOCIOS IMOBILIARIOS</div><span class="w2ui-clipboard-copy w2ui-icon-paste"></span></td><td class="w2ui-grid-data " id="grid_grid_data_1600_1" col="1" style=""><div style="max-height: 32px;margin-right: 20px"></div></td><td class="w2ui-grid-data-last" col="end"></td></tr><tr id="grid_grid_rec_bottom" rec="bottom" line="bottom" style="height: 0px; vertical-align: top;">    <td colspan="2000" style="border: 0"></td></tr><tr id="grid_grid_rec_more" style="display: none">    <td colspan="2000" class="w2ui-load-more"></td></tr></tbody></table>
<?php
$manager = 'role-management';
$permission = 'role-management';
$controller = "redirect=true&table={$Table}&permission_id={$permission}&foreign_key={$foreign_key}&id=";


$table_actions = [
    'order'       => [ 'permission' => false, 'url' => $controller ],
    'duplicate'   => [ 'permission' => true, 'url' => $controller ],
    'edit'        => [ 'permission' => load_permission($manager), 'url' => get_url_page($manager, 'full') ],
    'delete'      => [ 'permission' => true, 'url' => $controller ],
];

if (load_permission($manager))
{
    $hooks_out[] = [
        'title' => 'Cadastrar',
        'url'  => get_url_page($manager, 'full'),
        'color' => 'outline-success',
        'pre_icon' => 'fas fa-plus',
    ];
}

foreach (array_merge(get_roles(), get_plans()) as $data)
{
    $row = [];
    $row[] = $data['order_reg'];
    $row[] = $data['name'];
    $row[] = $data['type'];
    $row[] = $data['id'];

    $row[] = build_table_actions($table_actions, $data['id']);

    $body[] = $row;
}

$table = [
    'data_table' => true,
    'crud_panel' => [
        'show_panel' => true,
        'hooks_out' => $hooks_out ?? [],
    ],
    'head' => [
        'Order',
        'Name',
        'Type',
        'ID',
        'Ações',
    ],
    'body' => $body ?? [],
];
echo table($table);

?>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
