<?php

/**
 * Get uncode theme backup files.
 *
 * @return Array<String>
 */
function getBackups() {
    if (version_compare(PHP_VERSION, '5.6.0', '<'))
        return;

    $folder = ABSPATH . 'wp-content/uncode-backups';

    if (!file_exists($folder)) {
        return [];
    }

    $backups = scandir($folder);
    $_backups = [];

    foreach ($backups as $backup) {
        $file_ending = explode('.', $backup);
        if (empty($file_ending)) { continue; }
        if (sizeof($file_ending) < 2) { continue; }
        if ($file_ending[sizeof($file_ending) -1 ] != 'zip') { continue; }

        $_backups[] = $backup;
    }

    return $_backups;
}

function isInstallationLegit() {
    $communicator = new UncodeCommunicator();

    $envato = new Envato();
    $data = $envato->getToolkitData();

    $server_name = empty($_SERVER['SERVER_NAME']) ?
        $_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME'];

    if (
        substr_count($server_name, '.dev') > 0 ||
        substr_count($server_name, '.local') > 0
    ) { return true; }

    if (isset($data['api_key'])) {
        if (!empty($data['purchase_code'])) {
            $con_domain = $communicator->getConnectedDomains(
                    $data['purchase_code']
                );

            if (
                $con_domain != $server_name &&
                !empty($con_domain) &&
                substr_count($con_domain, '.dev') == 0 &&
                substr_count($con_domain, '.local') == 0

            ) {
                return false;
            }
        }
    }

    return true;
}

function requiredDataEmpty() {
    $communicator = new UncodeCommunicator();

    $envato = new Envato();
    return $envato->toolkitDataEmpty();
}

