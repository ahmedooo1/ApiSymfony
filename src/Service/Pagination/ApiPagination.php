<?php

namespace App\Service\Pagination;

use JasonGrimes\Paginator;

class ApiPagination
{

    private $itemsPerPage;
    private $totalItems;
    private $currentPage;
    private $urlPattern = '?page=(:num)';
    private $maxPagesToShow = 6;

    public function __construct($page,$limit,$count,$orderby = '',$order = '')
    {
        $this->currentPage = $page;
        $this->itemsPerPage = $limit;
        $this->totalItems = $count;
        if(!empty($orderby)) {
            $this->urlPattern = '?page=(:num)&limit=' . $limit . '&orderby='.$orderby.'&order='.$order;
        } else {
            $this->urlPattern = '?page=(:num)&limit=' . $limit;
        }

    }

    function getJsonpagination()
    {
        $paginator = new Paginator($this->totalItems, $this->itemsPerPage, $this->currentPage, $this->urlPattern);
        $paginator->setMaxPagesToShow($this->maxPagesToShow);
        return [
            'total'          => $this->totalItems,
            'items_per_page' => $this->itemsPerPage,
            'current_page'   => $this->currentPage,
            'links'          => $paginator->getPages()
        ];
    }

    /**
     * @param string $pattern
     * @return $this
     */
    public function concatPattern(string $pattern) {
        $this->urlPattern .= $pattern;
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function setMaxPagesToShow(int $count) {
        $this->maxPagesToShow = $count;
        return $this;
    }

}