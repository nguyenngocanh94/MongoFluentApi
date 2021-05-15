<?php


namespace FluentApi;


class Paging
{
    public function __construct($page, $itemPerPages)
    {
        $this->page = $page;
        $this->itemPerPages = $itemPerPages;
    }

    public $page;
    public $itemPerPages;

    public function getOffset(){
        return $this->page * $this->itemPerPages;
    }
}