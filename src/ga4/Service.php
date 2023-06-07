<?php
/**
 * Instant Analytics plugin for Craft CMS
 *
 * @author    nystudio107
 * @copyright Copyright (c) 2017 nystudio107
 * @link      http://nystudio107.com
 * @package   InstantAnalytics
 * @since     1.0.0
 */

namespace nystudio107\instantanalyticsGa4\ga4;

use Br33f\Ga4\MeasurementProtocol\Service as BaseService;

/**
 * @author    nystudio107
 * @package   InstantAnalytics
 * @since     1.2.0
 */
class Service extends BaseService
{
    protected array $additionalParams = [];

    public function setAdditionalQueryParam(string $name, ?string $value): void
    {
        if ($value === null) {
            unset($this->additionalParams[$name]);
        } else {
            $this->additionalParams[$name] = $value;
        }
    }

    public function deleteAdditionalQueryParam(string $name): void
    {
        unset($this->additionalParams[$name]);
    }

    public function getQueryParameters(): array
    {
        $parameters = parent::getQueryParameters();

        // Return without overwriting existing
        return $parameters + $this->additionalParams;
    }
}
