<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Service;

use KaufmannDigital\EmailEditing\Exception\MjmlConversionException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\ValidationLevel;

/**
 * Converts MJML to HTML, either locally via Node.js (spatie/mjml-php) or via an MJML API server
 */
#[Flow\Scope('singleton')]
class MjmlConverter
{
    public const MODE_LOCAL = 'local';
    public const MODE_API = 'api';

    #[Flow\InjectConfiguration(path: 'mjmlRendering.converter')]
    protected array $settings;

    public function toHtml(string $mjml): string
    {
        $mode = $this->settings['mode'] ?? self::MODE_LOCAL;

        return match ($mode) {
            self::MODE_LOCAL => $this->convertLocally($mjml),
            self::MODE_API => $this->convertViaApi($mjml),
            default => throw new MjmlConversionException(sprintf('Unknown MJML converter mode "%s", use "%s" or "%s".', $mode, self::MODE_LOCAL, self::MODE_API), 1761555600),
        };
    }

    protected function convertLocally(string $mjml): string
    {
        if (!class_exists(Mjml::class)) {
            throw new MjmlConversionException('Local MJML conversion requires spatie/mjml-php. Install it or set KaufmannDigital.EmailEditing.mjmlRendering.converter.mode to "api".', 1761555601);
        }

        return Mjml::new()
            ->validationLevel(ValidationLevel::Skip)
            ->toHtml($mjml);
    }

    protected function convertViaApi(string $mjml): string
    {
        $apiSettings = $this->settings['api'] ?? [];
        if (empty($apiSettings['url'])) {
            throw new MjmlConversionException('MJML converter mode "api" requires KaufmannDigital.EmailEditing.mjmlRendering.converter.api.url to be set.', 1761555602);
        }

        $engine = new CurlEngine();
        $engine->setOption(CURLOPT_TIMEOUT, (int)($apiSettings['timeout'] ?? 30));
        $browser = new Browser();
        $browser->setRequestEngine($engine);
        $browser->addAutomaticRequestHeader('Content-Type', 'application/json');
        $browser->addAutomaticRequestHeader('Accept', 'application/json');
        if (!empty($apiSettings['token'])) {
            $browser->addAutomaticRequestHeader('Authorization', 'Bearer ' . $apiSettings['token']);
        }

        $body = json_encode(['mjml' => $mjml, 'options' => ['validationLevel' => 'skip']], JSON_THROW_ON_ERROR);

        try {
            $response = $browser->request($apiSettings['url'], 'POST', [], [], [], $body);
        } catch (\Throwable $exception) {
            throw new MjmlConversionException(sprintf('MJML API at "%s" is not reachable: %s', $apiSettings['url'], $exception->getMessage()), 1761555603, $exception);
        }

        $result = json_decode((string)$response->getBody(), true);
        if ($response->getStatusCode() !== 200 || !is_array($result) || !isset($result['html'])) {
            throw new MjmlConversionException(sprintf('MJML API responded with status %d: %s', $response->getStatusCode(), is_array($result) ? ($result['error'] ?? 'unknown error') : 'invalid response'), 1761555604);
        }

        return $result['html'];
    }
}
