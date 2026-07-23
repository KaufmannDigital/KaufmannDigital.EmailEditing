<?php
namespace KaufmannDigital\EmailEditing\Fusion\FusionObjects;

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\ValidationLevel;


class MjmlElementRendererImplementation extends AbstractFusionObject
{

    /**
     * @Flow\Inject
     * @var StringFrontend $mjmlCache
     */
    protected $mjmlCache;


    /**
     * @return string
     */
    public function getMjmlHead()
    {
        return $this->fusionValue('mjmlHead');
    }

    /**
     * @return string
     */
    public function getMjmlSource()
    {
        return $this->fusionValue('mjmlSource');
    }

    /**
     * @return string
     */
    public function getMaxWidth()
    {
        return $this->fusionValue('maxWidth') ?? '600px';
    }
    /**
     * @return string
     */
    public function getBackgroundColor(): string
    {
        return $this->fusionValue('backgroundColor') ?? '#ffffff';
    }

    /**
     * @return string|null
     */
    public function getColumnPadding(): ?string
    {
        return $this->fusionValue('columnPadding');
    }

    public function evaluate()
    {
        $mjmlSource = $this->getMjmlSource();
        if (!str_contains($mjmlSource, '<mj-column')) {
            $columnPadding = $this->getColumnPadding();
            $columnPaddingAttr = $columnPadding !== null ? ' padding="' . $columnPadding . '"' : '';
            $mjmlSource = '<mj-section full-width="full-width" padding="0"><mj-column' . $columnPaddingAttr . ' vertical-align="middle">' . $mjmlSource . '</mj-column></mj-section>';
        }

        $mjml = '
            <mjml>
                ' . $this->getMjmlHead() .'
                <mj-body width="' . $this->getMaxWidth() . '" background-color="' . $this->getBackgroundColor() . '">
                ' . $mjmlSource . '
                </mj-body>
            </mjml>
        ';

        $cacheKey = sha1($mjml);
        if ($this->mjmlCache->has($cacheKey)) {
            return $this->mjmlCache->get($cacheKey);
        }

        $html = Mjml::new()
            ->validationLevel(ValidationLevel::Skip)
            ->toHtml($mjml);

        // Outlook-only rules are applyed conditionally
        // (e.g. .mj-outlook-group-fix, which forces mj-group columns to width:100% and breaks side-by-side layout everywhere else).
        // We remove them to don't get the styles for outlook applied, which breaks mj-group in all other browsers.
        $htmlWithoutMsoConditionals = preg_replace('/<!--\[if[^\]]*mso[^\]]*\]>[\s\S]*?<!\[endif\]-->/', '', $html);

        preg_match('/<body[^>]*>([\s\S]*?)<\/body>/', $html, $bodyMatches);
        preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/', $htmlWithoutMsoConditionals, $styleMatches);

        $body = $bodyMatches[1];

        $body = preg_replace('/<p/', '<div', $body);
        $body = preg_replace('/<\/p>/', '</div>', $body);



        $result = implode('', $styleMatches[0]) .  $body;
        $this->mjmlCache->set($cacheKey, $result);

        return $result;
    }

}
