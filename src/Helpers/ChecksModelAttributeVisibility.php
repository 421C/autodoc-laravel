<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use Illuminate\Database\Eloquent\Model;

trait ChecksModelAttributeVisibility
{
    protected function isModelAttributeHidden(Model $model, string $attributeName): bool
    {
        $visible = $model->getVisible();

        if (count($visible) > 0 && ! in_array($attributeName, $visible, true)) {
            return true;
        }

        return in_array($attributeName, $model->getHidden(), true);
    }
}
