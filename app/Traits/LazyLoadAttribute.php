<?php

namespace App\Traits;

trait LazyLoadAttribute
{
    public function loadAttribute($listOfAttributes)
    {
        foreach ($listOfAttributes as $attribute => $requiredRelations) {

            foreach ($requiredRelations as $relation) {

                if (!$this->relationLoaded($relation)) {
                    $this->load($relation);
                }
            }

            $this->append($attribute)->toArray();
        }
    }
}
