<?php

namespace Code16\Sharp\Form;

use Code16\Sharp\Exceptions\SharpException;
use Illuminate\Contracts\Support\Arrayable;

abstract class SharpSingleForm extends SharpForm
{
    public function formConfig(): array
    {
        return array_merge(
            parent::formConfig(),
            ["isSingle" => true]
        );
    }

    final function find($id): array
    {
        return $this->findSingle();
    }

    final function update($id, array $data)
    {
        return $this->updateSingle($data);
    }

    final public function storeInstance($data): void
    {
        throw new SharpException("Store is not possible in a SingleSharpForm.");
    }

    final function delete($id): void
    {
        throw new SharpException("Delete is not possible in a SingleSharpForm.");
    }

    abstract protected function findSingle();
    abstract protected function updateSingle(array $data);
}