<?php

namespace Blueprint\Contracts;

interface Model
{
    public function name(): string;

    public function namespace(): string;

    public function fullyQualifiedNamespace(): string;

    public function fullyQualifiedClassName(): string;
}
