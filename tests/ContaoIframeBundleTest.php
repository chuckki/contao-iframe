<?php

/*
 * This file is part of [package name].
 *
 * (c) John Doe
 *
 * @license LGPL-3.0-or-later
 */

namespace Chuckki\IframeBundle\Tests;

use Chuckki\IframeBundle\ContaoIframeBundle;
use PHPUnit\Framework\TestCase;

class ContaoIframeBundleTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundle = new ContaoIframeBundle();

        $this->assertInstanceOf('Contao\SkeletonBundle\ContaoIframeBundle', $bundle);
    }
}
