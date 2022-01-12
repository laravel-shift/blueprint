<?php

namespace Blueprint\Contracts;

interface Model
{
    public function name();

    public function namespace();

    public function fullyQualifiedNamespace();

    public function fullyQualifiedClassName();
}
