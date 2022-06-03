<?php declare(strict_types=1);

namespace Frosh\HtmlMinify;

use Composer\Autoload\ClassLoader;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FroshPlatformHtmlMinify extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $file = __DIR__ . '/../vendor/autoload.php';

        if (!is_file($file)) {
            return;
        }

        $classLoader = require_once $file;

        if ($classLoader instanceof ClassLoader) {
            $classLoader->unregister();
            $classLoader->register(false);
        }
    }
}
