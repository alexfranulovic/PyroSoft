import '../styles/admin.scss';
import '../../../../assets/scripts/main.js';

// Importação assíncrona do arquivo JSON
import area_info from '../../include/area-info.json';

// Função para importar condicionalmente o color-modes.js
if (area_info.allow_change_color_mode) {
  import('../../../../assets/scripts/inc/color-modes.js');
}
