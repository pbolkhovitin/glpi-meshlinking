<?php

namespace GlpiPlugin\Meshlinking;

use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use Config;
use DB;
use Item_RemoteManagement;

/**
 * Configuration and URL building helper.
 */
final class Linker
{
    public const CONFIG_CONTEXT = 'meshlinking';
    public const RIGHT_CONNECT = 'plugin_meshlinking_connect';

    public const DEFAULT_URL_TEMPLATE = 'https://meshcentral.example.com/?viewmode=11&gotonode={id}';
    public const DEFAULT_TYPES = 'meshcentral';

    /**
     * Known remote management types (raw key => display label).
     *
     * @return array<string, string>
     */
    public static function getKnownTypes(): array
    {
        return [
            Item_RemoteManagement::MESHCENTRAL => 'MeshCentral',
            Item_RemoteManagement::TEAMVIEWER  => 'TeamViewer',
            Item_RemoteManagement::ANYDESK     => 'AnyDesk',
            Item_RemoteManagement::LITEMANAGER => 'LiteManager',
            Item_RemoteManagement::SUPREMO     => 'SupRemo',
            Item_RemoteManagement::RUSTDESK    => 'RustDesk',
        ];
    }

    /**
     * Current plugin configuration.
     *
     * @return array{url_template: string, types: string[], open_new_tab: bool}
     */
    public static function getConfig(): array
    {
        $values = Config::getConfigurationValues(self::CONFIG_CONTEXT, [
            'url_template',
            'types',
            'open_new_tab',
        ]);

        return [
            'url_template' => (string) ($values['url_template'] ?? self::DEFAULT_URL_TEMPLATE),
            'types'        => self::parseTypes((string) ($values['types'] ?? self::DEFAULT_TYPES)),
            'open_new_tab' => !empty($values['open_new_tab']),
        ];
    }

    /**
     * Parse a comma separated list of type keys.
     *
     * @return string[]
     */
    public static function parseTypes(string $csv): array
    {
        $types = [];
        foreach (explode(',', $csv) as $type) {
            $type = strtolower(trim($type));
            if ($type !== '') {
                $types[] = $type;
            }
        }
        return array_values(array_unique($types));
    }

    /**
     * Build the connection URL for a remote id.
     * The id is inserted as-is (MeshCentral expects the raw node id).
     */
    public static function buildUrl(string $template, string $id): string
    {
        return str_replace('{id}', $id, $template);
    }

    /**
     * Configured remote management links for a given asset.
     *
     * @return array<int, array{type: string, remoteid: string, url: string}>
     */
    public static function getAssetRemoteLinks(CommonDBTM $item): array
    {
        $config = self::getConfig();
        if ($config['url_template'] === '' || $config['types'] === []) {
            return [];
        }

        $links = [];
        $management = new Item_RemoteManagement();
        $rows = $management->find([
            'itemtype'   => $item->getType(),
            'items_id'   => $item->getID(),
            'is_deleted' => 0,
        ]);

        foreach ($rows as $row) {
            $remoteid = (string) ($row['remoteid'] ?? '');
            if (!in_array(strtolower((string) $row['type']), $config['types'], true)) {
                continue;
            }
            if ($remoteid === '') {
                continue;
            }
            $links[] = [
                'type'     => (string) $row['type'],
                'remoteid' => $remoteid,
                'url'      => self::buildUrl($config['url_template'], $remoteid),
            ];
        }

        return $links;
    }

    /**
     * Computers whose main user is a requester of the given ticket.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public static function getRequesterComputers(CommonITILObject $ticket): array
    {
        global $DB;

        $user_ids = [];
        try {
            foreach ($ticket->getUsers(CommonITILActor::REQUESTER) as $actor) {
                if (!empty($actor['users_id'])) {
                    $user_ids[] = (int) $actor['users_id'];
                }
            }
        } catch (\Throwable) {
            return [];
        }

        $user_ids = array_values(array_unique($user_ids));
        if ($user_ids === []) {
            return [];
        }

        $computers = [];
        $rows = $DB->request([
            'FROM'  => 'glpi_computers',
            'WHERE' => [
                'users_id'    => $user_ids,
                'is_deleted'  => 0,
                'is_template' => 0,
            ],
        ]);
        foreach ($rows as $row) {
            $computers[] = [
                'id'   => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $computers;
    }

    /**
     * Connection links for every computer belonging to a ticket requester.
     *
     * @return array<int, array{computer_id: int, computer: string, type: string, remoteid: string, url: string}>
     */
    public static function getConnectLinks(CommonITILObject $ticket): array
    {
        $links = [];

        foreach (self::getRequesterComputers($ticket) as $computer) {
            $item = new \Computer();
            if (!$item->getFromDB($computer['id'])) {
                continue;
            }
            foreach (self::getAssetRemoteLinks($item) as $link) {
                $links[] = [
                    'computer_id' => $computer['id'],
                    'computer'    => $computer['name'],
                    'type'        => $link['type'],
                    'remoteid'    => $link['remoteid'],
                    'url'         => $link['url'],
                ];
            }
        }

        return $links;
    }
}
