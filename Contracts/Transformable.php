<?php

namespace FpDbTest\Contracts;

use mysqli;

interface Transformable
{
    public function transform(mixed $value, mysqli $mysqli): mixed;
}
