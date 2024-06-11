<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class DefaultController extends AppController
{

    private $noAuthorized = ['error_controller', 'App\Controller\DefaultController'];

    #[Route('/', name: 'app_documentation')]
    public function index(RouterInterface $router): JsonResponse
    {
        $collection = $router->getRouteCollection();
        $allRoutes = $collection->all();
        $routes = array();
        foreach($allRoutes as $route => $params) {
            $defaults = $params->getDefaults();
            if (isset($defaults['_controller']))
            {
                $controllerAction = explode(':', $defaults['_controller']);
                $controller = $controllerAction[0];
                if(!in_array($controller, $this->noAuthorized)) {
                    $goodControllerAction = explode('\\', $controller);
                    $goodController = trim(mb_strtolower(str_replace(array('Controller','Api'),'', $goodControllerAction[2])));
                    $infos = array(
                        'path' => $params->getPath(),
                        'method' => $params->getMethods()
                    );
                    if(!empty($params->getOption('security'))) {
                        $infos['security'] = $params->getOption('security');
                    }
                    if(!empty($params->getOption('params'))) {
                        $infos['params'] = $params->getOption('params');
                    }
                    if(!empty($params->getOption('data'))) {
                        $infos['data'] = $params->getOption('data');
                    }
                    $routes[$goodController][] = $infos;
                }
            }
        }
        // ici ajouter des routes
        return $this->json( $routes);
    }
}
