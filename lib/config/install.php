<?php
$log_path = wa()->getConfig()->getPluginPath('orderlogs').DIRECTORY_SEPARATOR.'templates/Log.html';
$orderlogs_path = wa()->getDataPath('orderlogs').DIRECTORY_SEPARATOR.'templates/Log.html';
waFiles::copy($log_path, $orderlogs_path);
