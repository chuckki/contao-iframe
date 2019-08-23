<?php

/*
 * This file is part of [package name].
 *
 * (c) John Doe
 *
 * @license LGPL-3.0-or-later
 */

namespace Chuckki\HvzIframeBundle\ContaoManager;

use Chuckki\HvzIframeBundle\ChuckkiHvzIframeBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ChuckkiHvzIframeBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $path = __DIR__ . '/../../src/Resources/config/routing.yml';
        return $resolver->resolve($path)->load($path);
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('nelmio_security' !== $extensionName) {
            return $extensionConfigs;
        }

        $customCors = [
            '^/extern/.*' => 'ALLOW'
        ];

        foreach ($extensionConfigs as &$extensionConfig) {

            if (isset($extensionConfig['clickjacking'])
                && is_array($extensionConfig['clickjacking']['paths'])) {
                $extensionConfig['clickjacking']['paths'] = $customCors + $extensionConfig['clickjacking']['paths'];
            }
        }

        return $extensionConfigs;
    }
}
