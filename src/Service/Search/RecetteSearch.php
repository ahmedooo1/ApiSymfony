<?php
namespace App\Service\Search;

use App\Repository\RecetteRepository;
use App\Service\Pagination\ApiPagination;
use Symfony\Component\HttpFoundation\Request;

class RecetteSearch
{
    private int $itemPerPage = 12;
    private int $page;
    private int $limit;
    private string $all;
    private string $order;
    private string $orderby;
    private RecetteRepository $repo;

    public function __construct(RecetteRepository $recetterepo)
    {
        $this->repo = $recetterepo;
    }

    public function request(Request $request)
    {

        $this->page = abs($request->get('page', 1));
        $this->limit = $request->get('limit', $this->itemPerPage);
        $this->all = $request->get('all', 'no'); // no (default) / yes
        $this->order = $request->get('order', 'asc');
        $this->orderby = $request->get('orderby', 'title');


        if($this->all === 'yes') {
            $data = $this->getAllRecettes();
        } else {
            $data = $this->getRecettesPaginate();
        }
        return $data;
    }


    private function getAllRecettes() {
        $cats = $this->repo->findAllOrdered($this->order);
        return $this->prepareArray(null, $cats);
    }

    private function getRecettesPaginate() {
        $count = $this->repo->countAll();
        $recettes = $this->repo->findAllOrderedPaginate($this->page,$this->limit,$this->order);
        $pagination = new ApiPagination($this->page,$this->limit,$count,$this->orderby,$this->order);
        return $this->prepareArray($pagination, $recettes);
    }

    private function prepareArray(ApiPagination|null $pagination, $recettes) : array
    {
        if($pagination === null) {
            $json = null;
        } else {
            $json = $pagination->getJsonpagination();
        }
        return [
            'pagination' => $json,
            'recettes'   => $recettes,
        ];
    }
}