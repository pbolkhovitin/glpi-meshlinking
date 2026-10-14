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
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Meshlinking\Linker;

/**
 * Render a "Connect" button in the info panel of an ITIL object
 * (Ticket / Change / Problem) for every computer of the requester that has a
 * configured remote management entry (e.g. MeshCentral).
 *
 * Registered through Glpi\Plugin\Hooks::POST_ITIL_INFO_SECTION.
 *
 * @param array $params ['item' => CommonITILObject, 'options' => array]
 */
function plugin_meshlinking_post_itil_info_section(array $params): void
{
    $item = $params['item'] ?? null;

    if (!($item instanceof \CommonITILObject)) {
        return;
    }

    // Only users allowed to use the connect button see it.
    if (!\Session::haveRight('plugin_meshlinking_connect', READ)) {
        return;
    }

    // Keep the feature on tickets; other ITIL types would need a computer link too.
    if (!($item instanceof \Ticket)) {
        return;
    }

    $links = Linker::getConnectLinks($item);
    if ($links === []) {
        return;
    }

    TemplateRenderer::getInstance()->display('@meshlinking/connect_button.html.twig', [
        'links'        => $links,
        'item'         => $item,
        'open_new_tab' => Linker::getConfig()['open_new_tab'],
    ]);
}
