<?php

namespace App\Factory\Pagination;

class PaginationResult
{
    private array $content;
    private int $totalElements;
    private int $pageNumber;
    private int $pageSize;
    private int $totalPages;

    public function __construct(array $content, int $totalElements, int $pageNumber, int $pageSize)
    {
        $this->content = $content;
        $this->totalElements = $totalElements;
        $this->pageNumber = $pageNumber;
        $this->pageSize = $pageSize;
        $this->totalPages = ceil($totalElements / $pageSize);
    }

    // Getters
    public function getContent(): array
    {
        return $this->content;
    }

    public function getTotalElements(): int
    {
        return $this->totalElements;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasNext(): bool
    {
        return $this->pageNumber < $this->totalPages;
    }

    public function hasPrevious(): bool
    {
        return $this->pageNumber > 1;
    }

    public function nextPage(): int
    {
        return ($this->pageNumber >= 1) ? $this->pageNumber + 1 : 1;
    }

    public function previousPage(): int
    {
        return ($this->pageNumber > 1) ? $this->pageNumber - 1 : 1;
    }

    public function toArray(): array
    {
        return [
            'data' => ($this->content != null) ? $this->content : [],
            'pagination' => [
                'total_elements' => $this->totalElements,
                'page_number' => $this->pageNumber,
                'page_size' => $this->pageSize,
                'total_pages' => $this->totalPages,
                'has_next' => $this->hasNext(),
                'has_previous' => $this->hasPrevious(),
                'next_page' => $this->nextPage(),
                'previous_page' => $this->previousPage(),
            ]
        ];
    }
}
