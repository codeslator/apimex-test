<?php

namespace App\Traits;

use App\Factory\Pagination\PaginationResult;
use App\Factory\Pagination\PageRequest;
use App\Database\PdoConnection;
use PDO;

trait PaginateTrait
{
    public PdoConnection $pdo;

    /**
     * Ejecuta una consulta paginada con filtros
     * 
     * @param string $baseQuery Consulta SQL base (sin LIMIT)
     * @param string $countQuery Consulta para contar el total de elementos
     * @param array $filters Filtros a aplicar
     * @param PageRequest $pageRequest Configuración de paginación
     * @param callable $mapper Función para mapear los resultados
     * @return PaginationResult
     */
    protected function paginateByQuery(
        string $sql,
        PageRequest $pageRequest,
    ): PaginationResult {
        try {
            $sortOrder = strtoupper($pageRequest->getOrder()) === 'DESC' ? 'DESC' : 'ASC';
            $sortBy = $pageRequest->getSortBy();
            
            $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
            $stmt = $this->pdo->prepare($countSql);
            $stmt->execute();
            $totalElements = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $sql .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

            // Ejecutar consulta paginada
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':limit', $pageRequest->getSize(), PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pageRequest->getOffset(), PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return new PaginationResult(
                $results,
                $totalElements,
                $pageRequest->getPage(),
                $pageRequest->getSize()
            );
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    protected function paginateByFilters(
        string $baseQuery,
        array $filters,
        PageRequest $pageRequest
    ): PaginationResult {
        // Aplicar filtros
        $sortOrder = strtoupper($pageRequest->getOrder()) === 'DESC' ? 'DESC' : 'ASC';
        $sortBy = $pageRequest->getSortBy();

        $countQuery = "SELECT COUNT(*) AS total FROM $baseQuery";
        $whereClauses = [];
        $params = [];

        foreach ($filters as $key => $value) {
            $whereClauses[] = "$key = :$key";
            $params[$key] = $value;
        }

        if (!empty($whereClauses)) {
            $whereString = ' WHERE ' . implode(' AND ', $whereClauses);
            $baseQuery .= $whereString;
            $countQuery .= $whereString;
        }

        $stmt = $this->pdo->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        $totalElements = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $offset = ($pageRequest->getPage() - 1) * $pageRequest->getSize();

        $baseQuery .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($baseQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $pageRequest, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return new PaginationResult(
            $data,
            $totalElements,
            $pageRequest->getPage(),
            $pageRequest->getSize()
        );
    }
}
