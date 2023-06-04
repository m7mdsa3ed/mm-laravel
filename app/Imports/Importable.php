<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Importable as ImportableTrait;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class Importable implements SkipsEmptyRows
{
    use ImportableTrait;
}
