<?php

namespace Onramplab\ComposerPackageTemplate;

/**
 * A sample class
 *
 * Use this section to define what this class is doing, the PhpDocumentor will use this
 * to automatically generate the API documentation
 *
 * @author Onramplab
 *
 * @package Onramplab\ComposerPackageTemplate
 */
class YourClass
{
    /**
     * Sample method
     *
     * Always create a corresponding DocBlock for each method, describing what it is for,
     * this helps the PhpDocumentor to properly generate the documentation
     */
    public function sayHello(string $name): string
    {
        return sprintf('Hello %s!', ucfirst($name));
    }
}
