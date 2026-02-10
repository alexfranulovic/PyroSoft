<?php

/**
 * Auxiliary Libraries
 */
include_once 'load.php';

load_crons_options();
cron_exec();

include_once 'ep-includes/core/cronjobs.php';

log_it("cron.log", date('Y-m-d H:i:s') . " - CRON Done\n");
