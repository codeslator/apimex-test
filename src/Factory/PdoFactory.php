<?php

namespace App\Factory;

use App\Database\PdoConnection;

use Exception;

use function PHPUnit\Framework\isNull;

final class PdoFactory
{
    public PdoConnection $pdo;
    // public PdoConnection $pdoConnection;

    public function __construct(PdoConnection $pdoConnection)
    {
        $this->pdo = $pdoConnection;
    }

    /**
     * Get all data from the database
     * @param string  $table    Name of table
     *
     */
    public function all(string $table)
    {
        try {
            $sql = $this->pdo->query("SELECT * FROM $table");
            $all = $sql->fetchAll(\PDO::FETCH_ASSOC);
            return $all;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Get all the data from the database and return it with a pagination
     *
     * @param string  $table    Name of table
     * @param int     $page     You place yourself on the page you want to display data, this depends on the amount per page to display.
     * @param int     $perPage  Designates the number of items to return
     * @param array   $filters  Designates the filters for the query
     * @param string  $sortBy   Designates the column to use for sorting, default is 'id'
     * @param string  $perPage  Designates the order that must have the query, default is ascending = ASC.
     */
    public function paginate(string $table, int $page, int $perPage, array $filters = [], string $sortBy = 'id', string $sortOrder = 'ASC'): array
    {
        try {
            $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

            $query = "SELECT * FROM $table";
            $countQuery = "SELECT COUNT(*) AS total FROM $table";
            $whereClauses = [];
            $params = [];

            foreach ($filters as $key => $value) {
                $whereClauses[] = "$key = :$key";
                $params[$key] = $value;
            }

            if (!empty($whereClauses)) {
                $whereString = ' WHERE ' . implode(' AND ', $whereClauses);
                $query .= $whereString;
                $countQuery .= $whereString;
            }

            $stmt = $this->pdo->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            $total = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

            $offset = ($page - 1) * $perPage;

            $query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $result = [
                'data' => ($data !== null) ? $data : [],
                'pagination' => [
                    'page' => $page,
                    'count' => (int) $total,
                    'total_page' => ($data !== null) ? count($data) : 0,
                    'next_page' => ($page >= 1) ? $page + 1 : 1,
                    'prev_page' => ($page > 1) ? $page - 1 : 1,
                    'last_page' => ceil($total / $perPage)
                ]
            ];

            return $result;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    /**
     * Find a data from the database by id
     * @param string  $table    Name of table
     * @param int     $id       Give a id for the search
     */
    public function find(string $table, int $id)
    {
        try {
            $sql = $this->pdo->query("SELECT * FROM $table WHERE id = $id");
            $response = $sql->fetch(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Find all data from the database by colunm and value of table
     * @param string  $table    Name of table
     * @param string  $column   Column of the table you want to find a value(s)
     * @param mixed   $value    Value of the table you want to find a value(s)
     */
    public function findAllBy(string $table, string $column, mixed $value)
    {
        try {
            $sql = $this->pdo->query("SELECT * FROM $table WHERE $column LIKE '$value'");
            $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Find all data from the database by colunm and value of table
     * @param string  $table    Name of table
     * @param string  $data     Column of the table you want to find a value(s)
     */
    public function findAllByCS(string $table, array $data)
    {

        try {
            $columns = '';

            foreach ($data as $key => $val) {
                $columns .= "`$key` = '$val' AND ";
            }
            $columns = trim($columns, 'AND ');

            $sql = $this->pdo->query("SELECT * FROM $table WHERE $columns");
            $response = $sql->fetchAll(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            //return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Find exact data from the database by colunm and value of table
     * @param string  $table    Name of table
     * @param $column Column of the table you want to find a value(s)
     * @param $value  Value of the table you want to find a value(s)
     */
    public function findByColumn(string $table, string $column, mixed $value)
    {
        try {
            $sql = $this->pdo->query("SELECT * FROM $table WHERE $column = '$value'");
            $response = $sql->fetch(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    /**
     * Find all data from the table by specified conditions
     * @param string $table
     * @param array $fields
     * @param string $operator
     * @throws \DomainException
     */
    public function findByConditions(string $table, array $fields, string $operator = 'AND')
    {
        $conditions = '';
        foreach ($fields as $key => $val) {
            $conditions .= "`$key` = '$val' $operator ";
        }
        $conditions = trim($conditions, "$operator ");

        try {
            $sql = $this->pdo->query("SELECT * FROM $table WHERE $conditions");
            $response = $sql->fetch(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    public function login(string $email, string $password)
    {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND password = :password";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', $password);
            $stmt->execute();
            $response = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    public function findActiveByKey(string $key): ?array
    {
        try {
            $query = "SELECT * FROM client_api_keys WHERE api_key = :key AND status = 'ACTIVE'";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':key', $key);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                return null;
            }
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $data;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    public function deactivateAllByClient(int $clientId): void
    {
        try {
            if (is_null($clientId)) {
                throw new Exception('Client ID cannot be null');
            }
            $query = "UPDATE client_api_keys SET status = 'REVOKED' WHERE client_id = :client_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':client_id', $clientId);
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    public function updateLastUsedAt(int $id, string $dateTime): void
    {
        try {
            $query = "UPDATE client_api_keys SET last_used_at = :last_used_at WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':last_used_at', $dateTime);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    public function deactivateKey(int $clientId, string $environment): void
    {
        try {
            if (is_null($clientId)) {
                throw new Exception('Client ID cannot be null');
            }
            $rotatedAt = date('Y-m-d H:i:s');
            $query = "UPDATE client_api_keys cak SET status = 'REVOKED', rotated_at = '$rotatedAt' WHERE cak.client_id = :client_id AND cak.environment = :environment AND cak.status = 'ACTIVE'";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':client_id', $clientId);
            $stmt->bindValue(':environment', $environment);
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }

    public function createKey(int $clientId, string $key): void
    {
        try {
            $query = "INSERT INTO client_api_keys (client_id, api_key) VALUES (:client_id, :api_key)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':client_id', $clientId);
            $stmt->bindValue(':api_key', $key);
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
        }
    }


    /**
     * Find exact data from the database by colunm and value of table
     * @param string  $table    Name of table
     * @param $column Column of the table you want to find a value(s)
     * @param $value  Value of the table you want to find a value(s)
     */
    public function findExactBy(string $table, string $column, mixed $value)
    {
        try {
            $sql = $this->pdo->query("SELECT * FROM $table WHERE $column LIKE '$value'");
            $response = $sql->fetch(\PDO::FETCH_ASSOC);
            return $response;
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Creates a new data in the database
     * @param string  $table    Name of table
     * @param array $data Give data to be able to insert in the database, example ['column' => value ]
     */
    public function create(string $table, array $data)
    {
        try {
            $columns = '';
            $values = '';

            foreach ($data as $key => $val) {
                $columns .= '`' . $key . '`,';
                $values .= ':' . $key . ',';
            }

            $columns = trim($columns, ',');
            $values = trim($values, ',');

            $sql = "INSERT INTO $table($columns) VALUE ($values)";
            $insert = $this->pdo->prepare($sql);
            $insert->execute($data);
            // $this->pdo->lastInsertId()
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Update a data in the database
     * @param string  $table    Name of table
     * @param int    $id    Give a id for the update
     * @param array  $data  Give data to be able to update in the database, example ['column' => value ]
     */
    public function update(string $table, int $id, array $data)
    {
        try {
            $columns = '';

            foreach ($data as $key => $val) {
                $columns .= "`$key` = :$key,";
            }
            $columns = trim($columns, ',');
            $sql = "UPDATE $table SET $columns WHERE id = $id;";
            $update = $this->pdo->prepare($sql);
            $update->execute($data);
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Update a data in the database
     * @param string  $table    Name of table
     * @param $column Column of the table you want to find a value(s)
     * @param $value  Value of the table you want to find a value(s)
     * @param array  $data  Give data to be able to update in the database, example ['column' => value ]
     */
    public function updateBy(string $table, string $column, mixed $value, array $data)
    {

        try {
            $columns = '';

            foreach ($data as $key => $val) {
                $columns .= "`$key` = :$key,";
            }
            $columns = trim($columns, ',');
            $sql = "UPDATE $table SET $columns WHERE $column = '$value';";
            $update = $this->pdo->prepare($sql);
            $update->execute($data);
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    public function updateByConditions(string $table, array $fields, array $data)
    {

        try {
            $columns = '';

            foreach ($data as $key => $val) {
                $columns .= "`$key` = :$key,";
            }
            $columns = trim($columns, ',');

            $conditions = '';

            foreach ($fields as $key => $val) {
                $conditions .= "`$key` = '$val' AND ";
            }
            $conditions = trim($conditions, 'AND ');

            $sql = "UPDATE $table SET $columns WHERE $conditions;";

            //return $sql;
            $update = $this->pdo->prepare($sql);
            return $update->execute($data);
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Delete a data in the database
     * @param string  $table    Name of table
     * @param int    $id    Give a id for delete
     */
    public function delete(string $table, int $id)
    {
        try {
            $sql = "DELETE FROM $table WHERE id=$id";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    /**
     * Delete a data in the database
     * @param string  $table    Name of table
     * @param string  $column   Column of the table you want to find a value(s)
     * @param mixed   $value    Value of the table you want to find a value(s)
     */
    public function deleteBy(string $table, string $column, mixed $value)
    {
        try {
            $sql = "DELETE FROM $table WHERE $column = '$value'";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }

    public function deleteByCondition(string $table, array $fields, string $operator = 'AND')
    {
        $conditions = '';
        foreach ($fields as $key => $val) {
            $conditions .= "`$key` = '$val' $operator ";
        }
        $conditions = trim($conditions, "$operator ");

        try {
            $sql = "DELETE FROM $table WHERE $conditions";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            throw new \DomainException($e->getMessage());
            // return ["error" => "fail", "message" => $e->getMessage()];
        }
    }
}
