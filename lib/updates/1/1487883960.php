<?php
// Удаляем banner3.jpg
$path_file = wa()->getConfig()->getPluginPath('orderlogs').DIRECTORY_SEPARATOR.'locale/en_US/LC_MESSAGES/banner3.jpg';
if(file_exists($path_file)) {
    waFiles::delete($path_file);
}