<?php

namespace GlpiPlugin\Meshlinking;

use CommonGLPI;
use Html;
use Profile as BaseProfile;
use Session;

/**
 * Profile tab exposing the "plugin_meshlinking_connect" right.
 */
class Profile extends BaseProfile
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(
            __('MeshCentral link', 'meshlinking'),
            0,
            $item::getType(),
            'ti ti-plug-connected'
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof BaseProfile) {
            $profile = new self();
            $profile->showForm($item->getID());
        }

        return true;
    }

    public function showForm($ID, array $options = [])
    {
        $profile = new BaseProfile();
        $profile->getFromDB($ID);

        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

        if ($canedit) {
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $rights = [[
            'itemtype' => 'Computer',
            'label'    => __('Connect to a computer', 'meshlinking'),
            'field'    => Linker::RIGHT_CONNECT,
        ]];

        $profile->displayRightsChoiceMatrix($rights, [
            'canedit' => $canedit,
            'title'   => __('MeshCentral link', 'meshlinking'),
        ]);

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>";
            Html::closeForm();
        }

        return true;
    }
}
