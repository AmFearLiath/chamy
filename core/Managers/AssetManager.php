<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class AssetManager implements ManagerInterface
{
    private string $publicPath;

    /** @var array<string, string[]> */
    private array $css = ['head' => [], 'footer' => []];

    /** @var array<string, string[]> */
    private array $js = ['head' => [], 'footer' => []];

    /** @var string[] */
    private array $inlineCss = [];

    /** @var string[] */
    private array $inlineJs = [];

    public function __construct(string $publicPath)
    {
        $this->publicPath = $publicPath;
    }

    public function getName(): string
    {
        return 'asset';
    }

    public function boot(): void
    {
        if (!is_dir($this->publicPath)) {
            mkdir($this->publicPath, 0755, true);
        }
    }

    public function addCss(string $url, string $position = 'head'): void
    {
        if (!in_array($url, $this->css[$position] ?? [], true)) {
            $this->css[$position][] = $url;
        }
    }

    public function addJs(string $url, string $position = 'footer'): void
    {
        if (!in_array($url, $this->js[$position] ?? [], true)) {
            $this->js[$position][] = $url;
        }
    }

    public function addInlineCss(string $css): void
    {
        $this->inlineCss[] = $css;
    }

    public function addInlineJs(string $js): void
    {
        $this->inlineJs[] = $js;
    }

    public function renderCss(string $position = 'head'): string
    {
        $output = '';

        foreach ($this->css[$position] ?? [] as $url) {
            $output .= '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        if ($position === 'head') {
            foreach ($this->inlineCss as $css) {
                $output .= '<style>' . $css . '</style>' . "\n";
            }
        }

        return $output;
    }

    public function renderJs(string $position = 'footer'): string
    {
        $output = '';

        foreach ($this->js[$position] ?? [] as $url) {
            $output .= '<script src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        }

        if ($position === 'footer') {
            foreach ($this->inlineJs as $js) {
                $output .= '<script>' . $js . '</script>' . "\n";
            }
        }

        return $output;
    }

    public function getPublicPath(): string
    {
        return $this->publicPath;
    }
}
