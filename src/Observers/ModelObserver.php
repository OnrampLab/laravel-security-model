<?php

namespace OnrampLab\SecurityModel\Observers;

use OnrampLab\SecurityModel\Contracts\Securable;

class ModelObserver
{
    public function retrieved(Securable $model): void
    {
        $model->decrypt();
    }

    public function saved(Securable $model): void
    {
        $model->encrypt();
        $model->decrypt();
    }
}
