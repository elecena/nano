<?php

/**
 * Helper class for generating paginations
 */

class Pagination
{
    const PAGE = '__PAGE_PATTERN__';

    private $router;
    private $url = '';

    private $count = 0;
    private $limit = 0;

    private $page = 0;

    public function __construct(NanoApp $app)
    {
        $this->router = $app->getRouter();
    }

    /**
     * Set URL pattern to be used by pagination class
     *
     * $pager->setUrl('foo', 'results', array('q' => $query, 'p' => Pagination::PAGE))
     */
    public function setUrl(/* $controllerName, $methodName, ..., $params = array() */)
    {
        $args = func_get_args();
        $this->url = call_user_func_array([$this->router, 'formatUrl'], $args);
    }

    /**
     * Set number of items shown per page
     */
    public function setPerPageLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Set number of items pager is generated for
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * Set number of pages pager is generated for
     */
    public function setPages($pages)
    {
        $this->count = intval($pages * $this->limit);
    }

    /**
     * Set current page (starts from 1)
     */
    public function setCurrentPage($page)
    {
        $page = max($this->getFirstPage(), $page);
        $page = min($this->getLastPage(), $page);

        $this->page = intval($page);
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Get ID of the first page
     */
    public function getFirstPage()
    {
        return 1;
    }

    /**
     * Get ID of the last page
     */
    public function getLastPage()
    {
        return intval(ceil($this->count / $this->limit));
    }

    public function isFirstPage()
    {
        return $this->getCurrentPage() === $this->getFirstPage();
    }

    public function isLastPage()
    {
        return $this->getCurrentPage() === $this->getLastPage();
    }

    /**
     * Get ID of the previous page (or false if the current one is the first one)
     */
    public function getPrevPage()
    {
        return ($this->isFirstPage()) ? false : $this->page - 1;
    }

    /**
     * Get ID of the next page (or false if the current one is the last one)
     */
    public function getNextPage()
    {
        return ($this->isLastPage()) ? false : $this->page + 1;
    }

    /**
     * Returns URL pointing to a given page
     */
    public function getUrlForPage($page)
    {
        return str_replace(self::PAGE, $page, $this->url);
    }

    /**
     * Returns URL pointing to the first page
     */
    public function getUrlForFirstPage()
    {
        return $this->getUrlForPage($this->getFirstPage());
    }

    /**
     * Returns URL pointing to the first page
     */
    public function getUrlForLastPage()
    {
        return $this->getUrlForPage($this->getLastPage());
    }

    /**
     * Get paginator items
     */
    public function getItems($limit = 5)
    {
        $items = [];

        if ($this->getLastPage() == 1) {
            return $items;
        }

        // add the previous page
        if ($this->getPrevPage()) {
            $items[] = [
                'class' => 'prev',
                'url' => $this->getUrlForPage($this->getPrevPage()),
                'page' => $this->getPrevPage(),
            ];
        }

        // render neighbours of the current page
        $current = $this->getCurrentPage();
        $range = intval(floor($limit / 2));

        $from = max($current - $range, $this->getFirstPage());
        $to = min($current + $range, $this->getLastPage());

        // add left spacer
        if ($current - $range > $this->getFirstPage()) {
            $items[] = [
                'url' => $this->getUrlForFirstPage(),
                'page' => $this->getFirstPage(),
            ];

            $items[] = false;
        }

        // render current page
        for ($p = $from; $p <= $to; $p++) {
            $items[] = array_filter([
                'class' => ($p === $current) ? 'current' : false,
                'url' => $this->getUrlForPage($p),
                'page' => $p,
            ]);
        }

        // add right spacer
        if ($current + $range  < $this->getLastPage()) {
            $items[] = false;

            $items[] = [
                'url' => $this->getUrlForLastPage(),
                'page' => $this->getLastPage(),
            ];
        }

        // add the next page
        if ($this->getNextPage()) {
            $items[] = [
                'class' => 'next',
                'url' => $this->getUrlForPage($this->getNextPage()),
                'page' => $this->getNextPage(),
            ];
        }

        return $items;
    }
}
