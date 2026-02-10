<?php
if (!isset($seg)) exit;

/**
 * Registers core database tables used by the system.
 *
 * This file is responsible for declaring table identifiers and their
 * human-readable labels, which are later consumed by the CMS for:
 * - CRUD generation
 * - Admin listings
 * - Internal mappings and references
 *
 * The `$tables` array acts as a central registry of known tables.
 *
 * Notes:
 * - Keys represent the physical table names in the database.
 * - Values are descriptive labels intended for UI/display purposes.
 */
global $tables;

$tables['patch_notes']       = 'Notas de atualizações';
$tables['tb_profile_details'] = 'Detalhes do perfil';


/**
 * Load login and authentication-related settings.
 *
 * This file typically defines UI configuration for authentication flows
 */
require __DIR__ . '/login-settings.php';


/**
 * Extension point for additional modules.
 *
 * Use this section to manually include new modules or feature files.
 * Keep imports explicit to preserve load order and avoid hidden side effects.
 *
 * Example:
 * require __BASE_DIR__ . 'modules/example-module.php';
 */
