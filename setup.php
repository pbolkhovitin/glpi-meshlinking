<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2024-2026 pbolkhovitin
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of MeshCentral link plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Meshlinking\Linker;

define('PLUGIN_MESHLINKING_VERSION', '1.0.0');

/**
 * Plugin metadata used by GLPI to check compatibility before install.
 */
function plugin_version_meshlinking(): array
{
    return [
        'name'           => 'MeshCentral link',
        'version'        => PLUGIN_MESHLINKING_VERSION,
        'author'         => 'pbolkhovitin',
        'license'        => 'GPL v3+',
        'homepage'       => 'https://github.com/pbolkhovitin/glpi-meshlinking',
        'requirements'   => [
            'glpi' => [
                'min' => '11.0',
            ],
            'php'  => [
                'min' => '8.1',
            ],
        ],
    ];
}

/**
 * Register hooks and profile tab.
 *
 * IMPORTANT: this function lives in setup.php (not hook.php) because GLPI 11
 * checks function_exists('plugin_init_*') right after loading setup.php and
 * before includeHook(), so an init function defined in hook.php is never found.
 */
function plugin_init_meshlinking(): void
{
    global $PLUGIN_HOOKS;

    // Profile tab that exposes the "plugin_meshlinking_connect" right.
    Plugin::registerClass(
        \GlpiPlugin\Meshlinking\Profile::class,
        ['addtabon' => ['Profile']]
    );

    // "Connect" button in the right-hand info panel of Ticket/Change/Problem.
    $PLUGIN_HOOKS[Hooks::POST_ITIL_INFO_SECTION]['meshlinking']
        = 'plugin_meshlinking_post_itil_info_section';

    // Rewrites the MeshCentral remote ID cell into a clickable link.
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['meshlinking'] = 'js/meshlinking.js';

    // Expose the configuration to the JS layer through a <meta> tag.
    try {
        $config = Linker::getConfig();
        $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG]['meshlinking'] = [[
            'tag'        => 'meta',
            'properties' => [
                'name'    => 'meshlinking-config',
                'content' => json_encode([
                    'url_template' => $config['url_template'],
                    'types'        => $config['types'],
                    'open_new_tab' => $config['open_new_tab'],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ],
        ]];
    } catch (\Throwable) {
        // Never break the bootstrap because of a configuration read error.
    }

    // Gear icon ("Configure") in Setup > Plugins.
    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['meshlinking'] = 'config';
}

/**
 * Plugin installation: default configuration + profile right.
 */
function plugin_meshlinking_install(): bool
{
    $config = new \Config();
    $config->setConfigurationValues('meshlinking', [
        'url_template' => Linker::DEFAULT_URL_TEMPLATE,
        'types'        => Linker::DEFAULT_TYPES,
        'open_new_tab' => 1,
    ]);

    // Create the right for every profile (value defaults to 0 = no access).
    \ProfileRight::addProfileRights(['plugin_meshlinking_connect']);

    // Grant READ|UPDATE to profiles that may update tickets.
    plugin_meshlinking_grant_to_ticket_updaters();

    return true;
}

/**
 * Plugin uninstallation: drop configuration and profile right.
 */
function plugin_meshlinking_uninstall(): bool
{
    $config = new \Config();
    $config->deleteConfigurationValues('meshlinking');

    \ProfileRight::deleteProfileRights(['plugin_meshlinking_connect']);

    return true;
}

/**
 * Grant the connect right to every profile allowed to update tickets.
 */
function plugin_meshlinking_grant_to_ticket_updaters(): void
{
    global $DB;

    $grant = READ | UPDATE;

    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'glpi_profiles']) as $profile) {
        $rights = \ProfileRight::getProfileRights($profile['id'], ['ticket']);
        if (!isset($rights['ticket'])) {
            continue;
        }
        if (($rights['ticket'] & UPDATE) === UPDATE) {
            $DB->update(
                'glpi_profilerights',
                ['rights' => $grant],
                [
                    'profiles_id' => $profile['id'],
                    'name'        => 'plugin_meshlinking_connect',
                ]
            );
        }
    }
}
