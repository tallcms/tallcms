<?php

declare(strict_types=1);

namespace TallCms\Cms\View\Components;

use Illuminate\View\Component;
use TallCms\Cms\Models\SiteSetting;

class CodeInjection extends Component
{
    public string $code;

    private const ALLOWED_ZONES = ['head', 'body_start', 'body_end'];

    public function __construct(
        public string $zone = 'head',
    ) {
        if (! in_array($this->zone, self::ALLOWED_ZONES)) {
            $this->code = '';

            return;
        }

        $this->code = (string) SiteSetting::get("code_{$this->zone}", '');
    }

    public function render()
    {
        return view('tallcms::components.code-injection');
    }

    public function shouldRender(): bool
    {
        return $this->code !== '';
    }
}
