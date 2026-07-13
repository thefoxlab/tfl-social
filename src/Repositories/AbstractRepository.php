<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;

abstract class AbstractRepository
{
    public function __construct(
        protected readonly Model $model,
        private readonly string $primaryKey
    ) {
    }

    public function findById(int|string $id): ?Entity
    {
        $result = $this->model->find($id);

        if ($result === null) {
            return null;
        }

        return $this->ensureEntity($result);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOne(array $criteria, ?string $orderBy = null): ?Entity
    {
        $builder = $this->model->where($criteria);
        
        if ($orderBy !== null) {
            [$column, $direction] = explode(' ', trim($orderBy), 2);
            $builder->orderBy($column, $direction ?? 'ASC');
        }
        
        $result = $builder->first();
        
        if ($result === null) {
            return null;
        }
        
        return $this->ensureEntity($result);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<Entity>
     */
    public function findAll(array $criteria = [], ?int $limit = null, int $offset = 0): array
    {
        if ($criteria !== []) {
            $this->model->where($criteria);
        }

        $results = $limit === null
            ? $this->model->findAll()
            : $this->model->findAll($limit, $offset);

        return $this->ensureEntityList($results);
    }

    /**
     * @param array<string, mixed>|Entity $data
     */
    public function insert(array|Entity $data): Entity
    {
        $id = $this->model->insert($data, true);

        if ($id === false) {
            throw RepositoryException::forModelFailure('insert', $this->model->errors());
        }

        $entity = $this->findById($id);

        if ($entity === null) {
            throw new RepositoryException('Inserted record could not be resolved.');
        }

        return $entity;
    }

    /**
     * @param array<string, mixed>|Entity $data
     */
    public function update(int|string $id, array|Entity $data): Entity
    {
        if (! $this->model->update($id, $data)) {
            throw RepositoryException::forModelFailure('update', $this->model->errors());
        }

        $entity = $this->findById($id);

        if ($entity === null) {
            throw new RepositoryException(sprintf('Updated record [%s] could not be resolved.', (string) $id));
        }

        return $entity;
    }

    public function delete(int|string $id): void
    {
        if (! $this->model->delete($id)) {
            throw RepositoryException::forModelFailure('delete', $this->model->errors());
        }
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function exists(array $criteria): bool
    {
        return $this->findOne($criteria) !== null;
    }

    /**
     * @param array<string, mixed>|Entity $data
     */
    public function save(array|Entity $data): Entity
    {
        $entity = $this->model->save($data);

        if (! $entity) {
            throw RepositoryException::forModelFailure('save', $this->model->errors());
        }

        $id = $this->extractPrimaryKey($data);

        if ($id === null) {
            $insertId = $this->model->getInsertID();

            if ($insertId === 0 || $insertId === '0') {
                throw new RepositoryException('Saved record primary key could not be determined.');
            }

            $id = $insertId;
        }

        $saved = $this->findById($id);

        if ($saved === null) {
            throw new RepositoryException(sprintf('Saved record [%s] could not be resolved.', (string) $id));
        }

        return $saved;
    }

    protected function primaryKey(): string
    {
        return $this->primaryKey;
    }

    private function ensureEntity(mixed $result): Entity
    {
        if (! $result instanceof Entity) {
            throw new RepositoryException('Model did not return an entity instance.');
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $results
     *
     * @return list<Entity>
     */
    private function ensureEntityList(array $results): array
    {
        $entities = [];

        foreach ($results as $result) {
            $entities[] = $this->ensureEntity($result);
        }

        return $entities;
    }

    /**
     * @param array<string, mixed>|Entity $data
     */
    private function extractPrimaryKey(array|Entity $data): int|string|null
    {
        if ($data instanceof Entity) {
            $value = $data->{$this->primaryKey};

            return is_int($value) || is_string($value) ? $value : null;
        }

        $value = $data[$this->primaryKey] ?? null;

        return is_int($value) || is_string($value) ? $value : null;
    }
}
