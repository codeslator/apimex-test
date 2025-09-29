<?php

namespace App\Factory\Pagination;

class PageRequest
{
    private int $page;
    private int $size;
    private string $sortBy;
    private string $order;

    public function __construct(int $page = 1, int $size = 10, string $sortBy = 'id', string $order = 'ASC')
    {
        $this->page = $page;
        $this->size = $size;
        $this->sortBy = $sortBy;
        $this->order = $order;
    }

    public static function of(int $page, int $size, string $sortBy = 'id', string $order = 'ASC'): self
    {
        return new self($page, $size, $sortBy, $order);
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->size;
    }

    // Getters
    public function getPage(): int
    {
        return $this->page;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getOrder(): string
    {
        return $this->order;
    }
}