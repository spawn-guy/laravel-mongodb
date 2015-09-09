<?php namespace Jenssegers\Mongodb\Relations;

use Illuminate\Database\Eloquent\Model;
use MongoId;

class EmbedsOne extends EmbedsOneOrMany {

    /**
     * Get the results of the relationship.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getResults()
    {
        return $this->toModel($this->getEmbedded());
    }

    /**
     * Save a new model and attach it to the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function performInsert(Model $model)
    {
        // Generate a new key if needed.
        if ($model->getKeyName() == '_id' and ! $model->getKey())
        {
            $model->setAttribute('_id', new MongoId);
        }

        // For deeply nested documents, let the parent handle the changes.
        if ($this->isNested())
        {
            $this->associate($model);

            return $this->parent->save();
        }

        $result = $this->getBaseQuery()->update(array($this->localKey => $model->getAttributes()));

        // Attach the model to its parent.
        if ($result) $this->associate($model);

        return $result ? $model : false;
    }

    /**
     * Save an existing model and attach it to the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model|bool
     */
    public function performUpdate(Model $model)
    {
        if ($this->isNested())
        {
            $this->associate($model);

            return $this->parent->save();
        }

        // Use array dot notation for better update behavior.
        $values = array_dot($model->getDirty(), $this->localKey . '.');

        $result = $this->getBaseQuery()->update($values);

        // Attach the model to its parent.
        if ($result) $this->associate($model);

        return $result ? $model : false;
    }

    /**
     * Delete an existing model and detach it from the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return int
     */
    public function performDelete(Model $model)
    {
        // For deeply nested documents, let the parent handle the changes.
        if ($this->isNested())
        {
            $this->dissociate($model);

            return $this->parent->save();
        }

        // Overwrite the local key with an empty array.
        $result = $this->getBaseQuery()->update(array($this->localKey => null));

        // Detach the model from its parent.
        if ($result) $this->dissociate();

        return $result;
    }

    /**
     * Attach the model to its parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate(Model $model)
    {
        return $this->setEmbedded($model->getAttributes());
    }

    /**
     * Detach the model from its parent.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        return $this->setEmbedded(null);
    }

    /**
     * Delete all embedded models.
     *
     * @return int
     */
    public function delete()
    {
        $model = $this->getResults();

        return $this->performDelete($model);
    }

    /**
     * Shorthand to get the results of the relationship.
     *
     * @return \Jenssegers\Mongodb\Eloquent\Collection
     */
    public function get()
    {
        return new \Jenssegers\Mongodb\Eloquent\Collection($this->getResults());
    }
    /**
     * Get the embedded records array.
     * dn additional because null will failed on match function
     * @return array
     */
    protected function getEmbedded()
    {
        return parent::getEmbedded() ?: [];
    }
}
