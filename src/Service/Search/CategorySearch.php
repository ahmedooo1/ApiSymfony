<?php

namespace App\Service\Search;


use App\Repository\CategoryRepository;
use App\Service\Pagination\ApiPagination;
use Symfony\Component\HttpFoundation\Request;

class CategorySearch
{
    private int $itemPerPage = 10;
    private int $page;
    private int $limit;
    private string $all;
    private string $order;
    private string $orderby;
    private CategoryRepository $repo;

    public function __construct(CategoryRepository $catrepo)
    {
        $this->repo = $catrepo;
    }

    public function request(Request $request)
    {

        $this->page = abs($request->get('page', 1));
        $this->limit = abs($request->get('limit', $this->itemPerPage));
        $this->all = $request->get('all', 'no'); // no / yes
        $this->order = $request->get('order', 'asc');
        $this->orderby = $request->get('orderby', 'title');
        if($this->all === 'yes') {
            $data = $this->getAllCategories();
        } else {
            $data = $this->getCatPaginate();
        }
        return $data;
    }

    private function getAllCategories()
    {
        $cats = $this->repo->findAllOrdered($this->order);
        return $this->prepareArray(null, $cats);
    }

    private function getCatPaginate()
    {
        $count = $this->repo->countAll();
        $cats = $this->repo->findOrderedPaginate($this->page, $this->limit, $this->order);
        $pagination = new ApiPagination($this->page, $this->limit, $count, $this->orderby, $this->order);
        return $this->prepareArray($pagination, $cats);
    }

    private function prepareArray(ApiPagination|null $pagination, $cats)
    {
        if($pagination === null) {
            $json = null;
        } else {
            $json = $pagination->getJsonpagination();
        }
        return [
            'pagination' => $json,
            'category'   =>  $cats
        ];
    }



}