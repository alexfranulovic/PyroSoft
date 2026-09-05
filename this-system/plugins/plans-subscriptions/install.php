<?php
if (!isset($seg)) exit;


/**
 * Create plans table.
 */
$sql = "
CREATE TABLE IF NOT EXISTS `tb_plans` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `order_reg` int(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'BRL',
  `regular_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sale_price_cycles` INT NOT NULL DEFAULT 0,
  `badge_label` VARCHAR(50) DEFAULT NULL,
  `interval_unit` enum('day','week','month','year','lifetime') NOT NULL DEFAULT 'month',
  `interval_count` int(10) NOT NULL DEFAULT 1,
  `target_audience` varchar(50) DEFAULT NULL,
  `trial_days` int(10) NOT NULL DEFAULT 0,
  `grace_days` int(10) NOT NULL DEFAULT 0,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `fee_mode` varchar(50) DEFAULT 'merchant',
  `activation_function` varchar(255) DEFAULT NULL,
  `deactivation_function` varchar(255) DEFAULT NULL,
  `allows_price_lock` tinyint(1) NOT NULL DEFAULT 0,
  `accepted_payment_methods` JSON DEFAULT NULL,
  `user_id` int(10) DEFAULT NULL,
  `status_id` int(11) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tb_plans_slug` (`slug`),
  KEY `idx_tb_plans_user_id` (`user_id`),
  KEY `idx_tb_plans_status_id` (`status_id`),
  KEY `idx_tb_plans_is_visible` (`is_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


/**
 * Create user subscriptions table.
 */
$sql = "
CREATE TABLE IF NOT EXISTS `tb_plan_user_subscriptions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `plan_id` int(10) NOT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,

  /* Subscription */
  `trial_ends_at` date DEFAULT NULL,
  `status` enum('pending','active','trialing','past_due','canceled','expired','paused') NOT NULL DEFAULT 'pending',
  `started_at` DATE DEFAULT NULL,
  `ended_at` DATE DEFAULT NULL,
  `current_period_start` DATE DEFAULT NULL,
  `current_period_end` DATE DEFAULT NULL,
  `cycles_quantity` INT NOT NULL DEFAULT 0,
  `next_billing_at` DATE NULL,
  `grace_ends_at` DATE NULL,
  `last_renewal_attempt_at` DATETIME NULL,
  `renewal_attempts_count` INT NOT NULL DEFAULT 0,
  `cancel_flow` VARCHAR(255) NULL,          -- how/where this subscription was canceled (system-set), e.g. grace_period_ended, customer_requested_auto_renew_off, admin_canceled -- was `cancel_reason`.
  `cancel_reason` VARCHAR(50) NULL,         -- customer-selected reason category from the cancellation form's radio -- see get_subscription_cancel_reason_options() (src/ui.php).
  `probability_return` VARCHAR(50) NULL,    -- customer-selected return-likelihood radio -- see get_subscription_return_likelihood_options() (src/ui.php).
  `user_cancel_detail` VARCHAR(500) DEFAULT NULL, -- customer's own free-text comment (textarea) -- was `user_cancel_reason`.
  `canceled_at` DATETIME NULL,

  /* Payment */
  `statement_descriptor` VARCHAR(20) NULL,
  `user_payment_method_id` bigint(20) DEFAULT NULL,
  `bypass_payment` tinyint(1) NOT NULL DEFAULT 0,

  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  -- UNIQUE KEY `uq_tb_plan_user_subscriptions_user_plan` (`user_id`, `plan_id`),
  KEY `idx_tb_plan_user_subscriptions_user_id` (`user_id`),
  KEY `idx_tb_plan_user_subscriptions_plan_id` (`plan_id`),
  KEY `idx_tb_plan_user_subscriptions_status` (`status`),
  KEY `idx_tb_plan_user_subscriptions_next_billing_at` (`next_billing_at`),
  KEY `idx_tb_plan_user_subscriptions_user_payment_method_id` (`user_payment_method_id`),

  KEY `idx_tb_plan_user_subscriptions_grace_ends_at` (`grace_ends_at`),
  KEY `idx_tb_plan_user_subscriptions_last_renewal_attempt_at` (`last_renewal_attempt_at`),
  KEY `idx_tb_plan_user_subscriptions_canceled_at` (`canceled_at`),

  KEY `idx_tb_plan_user_subscriptions_user_status` (`user_id`, `status`),
  KEY `idx_tb_plan_user_subscriptions_user_plan_status` (`user_id`, `plan_id`, `status`),
  KEY `idx_tb_plan_user_subscriptions_status_next_billing` (`status`, `next_billing_at`),

  CONSTRAINT `fk_tb_plan_user_subscriptions_plan_id`
    FOREIGN KEY (`plan_id`) REFERENCES `tb_plans` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


/**
 * Create proposal lock table.
 *
 * This table stores the locked commercial conditions
 * for a subscription.
 *
 * Rules:
 * - Only one lock row is allowed per subscription.
 * - If the commercial lock changes, update the current row.
 */
$sql = "
CREATE TABLE IF NOT EXISTS `tb_plan_proposal_lock` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) NOT NULL,

  -- Proposal snapshot only
  `currency` varchar(10) NOT NULL DEFAULT 'BRL',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `trial_days` int(10) NOT NULL DEFAULT 0,
  `grace_days` int(10) NOT NULL DEFAULT 0,
  `interval_unit` enum('day','week','month','year','lifetime') NOT NULL DEFAULT 'month',
  `interval_count` int(10) NOT NULL DEFAULT 1,
  `fee_mode` varchar(50) DEFAULT 'merchant',
  `activation_function` varchar(255) DEFAULT NULL,
  `deactivation_function` varchar(255) DEFAULT NULL,
  `reason` varchar(50) DEFAULT 'initial_contract',

  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tb_plan_proposal_lock_subscription_id` (`subscription_id`),

  CONSTRAINT `fk_tb_plan_proposal_lock_subscription_id`
    FOREIGN KEY (`subscription_id`) REFERENCES `tb_plan_user_subscriptions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


/**
 * Create plan roles table.
 *
 * A plan may activate one or multiple roles.
 */
$sql = "
CREATE TABLE IF NOT EXISTS `tb_plan_roles` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `plan_id` int(10) NOT NULL,
  `role_id` int(11) NOT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tb_plan_roles_plan_role` (`plan_id`, `role_id`),
  KEY `idx_tb_plan_roles_role_id` (`role_id`),

  CONSTRAINT `fk_tb_plan_roles_plan_id`
    FOREIGN KEY (`plan_id`) REFERENCES `tb_plans` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_tb_plan_roles_role_id`
    FOREIGN KEY (`role_id`) REFERENCES `tb_user_roles` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
query_it($sql);


$sql = "
ALTER TABLE `tb_user_role_assignments`
    ADD COLUMN `plan_id` INT(10) NULL,
    ADD UNIQUE KEY `uq_tb_user_role_assignments_user_plan` (`user_id`, `role_id`, `plan_id`),
    ADD KEY `idx_tb_user_role_assignments_plan_id` (`plan_id`),
    ADD CONSTRAINT `fk_tb_user_role_assignments_plan_id`
        FOREIGN KEY (`plan_id`) REFERENCES `tb_plans` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
";
query_it($sql);


/**
 * PLUGIN OPTIONS (DEFAULTS)
 *
 * Initializes configuration options used by PyroSales.
 */
update_option('subscriptions_business_model', 'd2c_single_plan');

feature('permissions-management');

$payload = [
  'allowed'         => [1],
  'name'            => 'Plan manager',
  'slug'            => 'plan-manager',
  'permission_type' => 'only_these',
  'type'            => 'permission',
];
update_permissions($payload, false);

$payload = [
  'allowed'         => [1],
  'name'            => 'Subscription manager',
  'slug'            => 'subscription-manager',
  'permission_type' => 'only_these',
  'type'            => 'permission',
];
update_permissions($payload, false);


feature('page-crud-management-system');

$page = [
  'title' => 'Plan manager',
  'slug' => 'plan-manager',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/plans-subscriptions/custom-pages/plan-manager.php',
];
manage_page_system($page, 'insert');

$page = [
  'title' => 'Plans',
  'slug' => 'plans',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/plans-subscriptions/custom-pages/plans.php',
];
manage_page_system($page, 'insert');

/**
 * Admin: "All subscriptions" listing and "Subscription manager"
 * (insert/update por presença de ?id=). Also doubles as the per-user
 * subscription journey timeline (render_user_plans_timeline_html(), src/ui.php)
 * when called as ?id=subscriptions&user_id=<id> -- only meaningful when
 * subscriptions_business_model == 'd2c_single_plan' (one subscription
 * lineage per user), linked from the customer name in the listing when
 * that's the active business model.
 */
$page = [
  'title' => 'Subscriptions',
  'slug' => 'subscriptions',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/plans-subscriptions/custom-pages/subscriptions.php',
];
manage_page_system($page, 'insert');

$page = [
  'title' => 'Subscription manager',
  'slug' => 'subscription-manager',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'admin',
  'permission_type' => 'only_these',
  'allowed' => [1],
  'page_template' => PLUGINS_PATH . '/plans-subscriptions/custom-pages/subscription-manager.php',
];
manage_page_system($page, 'insert');

/**
 * Customer-facing: "My subscriptions" (self-service list -- auto renew,
 * statement_descriptor and payment method are editable inline) and the
 * cancellation screen it links to. Mirrors the `/app` area flavor
 * pyrosales' own custom-pages/payment-methods.php uses for "my account"
 * pages -- NOT proven from a manage_page_system() call in either plugin
 * (payment-methods.php's own page record isn't created by pyrosales'
 * install.php either), so double check `page_area`/`permission_type` here
 * against how that page is actually registered on your install if these
 * two don't render for a logged-in customer.
 */
$page = [
  'title' => 'Minhas assinaturas',
  'slug' => 'minhas-assinaturas',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'app',
  'permission_type' => 'except_these',
  'page_template' => PLUGINS_PATH . '/plans-subscriptions/custom-pages/my-subscriptions.php',
];
manage_page_system($page, 'insert');

$page = [
  'title' => 'Cancelar assinatura',
  'slug' => 'cancelar-assinatura',
  'page_type' => 'not_essential',
  'status_page_id' => 1,
  'page_area' => 'app',
  'permission_type' => 'except_these',
  'page_template' => PLUGINS_PATH . '/plans-subscriptions/custom-pages/cancel-subscription.php',
];
manage_page_system($page, 'insert');

/**
 * ADMIN MENU
 *
 * Adds a "Shop" group and a "List orders" item under the admin main menu.
 *
 * Notes:
 * - `position` is used to force ordering within the menu.
 * - `depth` defines hierarchy (0 = group, 1 = child item).
 * - The "Shop" group is created with slug `pyrosales`.
 */
feature('menu-management');

manage_menu_items([
  'menu_id' => get_menu_id_by_slug('admin-main-menu'),
  'mode' => 'update',
  'from' => 'out',
  'menu_order' => [
    [
      'position' => 16,
      'depth' => '1',
      'title' => 'Plans',
      'icon' => 'fas fa-calendar-days',
      'which_users' => 'logged_in',
      'style' => 'generic',
      'page_id' => get_page_id_by_slug('plans'),
      'type' => 'page',
      'slug' => 'plans',
    ],
    [
      'position' => 17,
      'depth' => '1',
      'title' => 'Subscriptions',
      'icon' => 'fas fa-repeat',
      'which_users' => 'logged_in',
      'style' => 'generic',
      'page_id' => get_page_id_by_slug('subscriptions'),
      'type' => 'page',
      'slug' => 'subscriptions',
    ],
  ],
], $debug);


$crud = [
  'crud_id' => get_crud_id_by_slug('general-settings'),
  'piece_name' => 'Plans subscriptions',
  'slug' => 'plans-subscriptions-settings',
  'type_crud' => 'update',
  'status_id' => 1,
  'login_required' => 1,
  'related_to' => 'system_info',

  'form_settings' => [
    'without_reload' => 1,
    'view_mode' => 'tabs_form',
  ],

  'form_action' => [
    'type' => 'api',
    'action' => 'form-processor'
  ],

  'Fields' => [
    [
      'depth' => 0,
      'type_field' => 'divider',
      'title' => 'Subscriptions',
    ],
    [
      'depth' => 1,
      'type_field' => 'selection_type',
      'label' => 'Type of business model',
      'name' => 'subscriptions_business_model',
      'type' => 'radio',
      // 'variation' => 'inline',
      'Required' => 1,
      'status_id' => 1,
      'Options' => [
        [
          'value' => 'marketplace',
          'display' => 'Subscription marketplace — users can create and sell their own plans',
        ],
        [
          'value' => 'd2c_multiple_plans',
          'display' => 'Direct-to-consumer — offer multiple subscription plans',
        ],
        [
          'value' => 'd2c_single_plan',
          'display' => 'Direct-to-consumer — offer a single subscription plan',
        ],
      ]
    ],
    [
      'depth' => 0,
      'type_field' => 'submit_button',
      'name' => 'process-form',
      'old_name' => 'process-form',
      'Value' => 'Enviar',
      'class' => 'btn btn-st',
      'allow_schedule' => 1,
    ]
  ],
];
$crud = manage_crud_system($crud, 'insert');

/**
 * Attaches the newly created settings CRUD to the `system-settings` page modules.
 *
 * Notes:
 * - `from => out` indicates this update is executed from outside the page editor.
 * - `$debug` must exist in the installer context; otherwise it should be set/removed.
 */
manage_page_modules([
  'page_id' => get_page_id_by_slug('system-settings'),
  'mode' => 'update',
  'from' => 'out',
  'Modules' => [
    [
      'TypeModule' => 'crud',
      'crud_id' => $crud['crud_id'],
      'status_id' => 1,
    ]
  ],
], $debug);
