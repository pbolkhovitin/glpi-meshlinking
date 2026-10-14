<?php

namespace GlpiPlugin\Meshlinking\Controller;

use Config;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Meshlinking\Linker;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/config", name: "meshlinking_config")]
final class ConfigController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_ADMIN_ACCESS)]
    public function __invoke(Request $request): Response
    {
        Session::checkRight('config', UPDATE);

        if ($request->isMethod('POST')) {
            $submitted = array_map('strtolower', array_map('strval', (array) $request->request->all('types')));
            $types = array_values(array_intersect(array_keys(Linker::getKnownTypes()), $submitted));

            Config::setConfigurationValues('meshlinking', [
                'url_template' => trim($request->request->getString('url_template', Linker::DEFAULT_URL_TEMPLATE)),
                'types'        => implode(',', $types),
                'open_new_tab' => $request->request->getInt('open_new_tab', 0),
            ]);
            Html::back();
        }

        $config = Linker::getConfig();

        ob_start();
        Html::header(__('MeshCentral link', 'meshlinking'), '', 'config', 'plugin');
        echo TemplateRenderer::getInstance()->render('@meshlinking/config.html.twig', [
            'config'      => $config,
            'known_types' => Linker::getKnownTypes(),
        ]);
        Html::footer();

        return new Response(ob_get_clean());
    }
}
