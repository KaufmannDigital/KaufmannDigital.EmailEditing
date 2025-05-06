<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Fusion\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\ValidationLevel;

class MjmlHelper implements ProtectedContextAwareInterface {




    public function test($value)
    {
        $mjmlSource = $value;
        if (!str_contains($mjmlSource, 'mj-column')) {
            $mjmlSource = '<mj-section full-width="full-width" padding="0"> <mj-column vertical-align="middle">' . $mjmlSource . '</mj-column></mj-section>';
        }

        $mjml = '
            <mjml>
                <mj-body>
                ' . $mjmlSource . '
                </mj-body>
            </mjml>
        ';


        $html = Mjml::new()
            ->validationLevel(ValidationLevel::Skip)
            ->toHtml($mjml);




        preg_match('/<body[^>]*>([\s\S]*?)<\/body>/', $html, $bodyMatches);
        preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/', $html, $styleMatches);



        return implode('', $styleMatches[0]) .  $bodyMatches[1];
    }

    public function renderSingle($value, bool $debug = false)
    {


        $mjml = '
    <mjml>
        <mj-body>
        ' . $value . '
        </mj-body>
    </mjml>
    ';

        if ($debug) {
            \Neos\Flow\var_dump($mjml);
        }

        $html = Mjml::new()
            ->validationLevel(ValidationLevel::Skip)
            ->toHtml($mjml);

        if ($debug) {
            \Neos\Flow\var_dump($html);
        }

        preg_match('/<body[^>]*>([\s\S]*?)<\/body>/', $html, $matches);
        $innerHtml = $matches[1];


        return $innerHtml;
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
