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
    public function getBackgroundColor()
    {
        return $this->fusionValue('backgroundColor') ?? '#ffffff';
    }

    public function evaluate()
    {
        $mjmlSource = $this->getMjmlSource();
        if (!str_contains($this->getMjmlSource(), 'mj-column')) {
            $mjmlSource = '<mj-section full-width="full-width" padding="0"> <mj-column vertical-align="middle">' . $mjmlSource . '</mj-column></mj-section>';
        }

        $mjml = '
            <mjml>
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

        preg_match('/<body[^>]*>([\s\S]*?)<\/body>/', $html, $bodyMatches);
        preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/', $html, $styleMatches);

        $body = $bodyMatches[1];

        $body = preg_replace('/<p/', '<div', $body);
        $body = preg_replace('/<\/p>/', '</div>', $body);



        $result = implode('', $styleMatches[0]) .  $body;
        $this->mjmlCache->set($cacheKey, $result);

        return $result;
    }

}
