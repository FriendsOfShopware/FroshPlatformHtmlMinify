<?php declare(strict_types=1);

namespace Frosh\HtmlMinify;

use Shopware\Core\Framework\Plugin;

class FroshPlatformHtmlMinify extends Plugin
{
    public function executeComposerCommands(): bool
    {
        return true;
    }
}
