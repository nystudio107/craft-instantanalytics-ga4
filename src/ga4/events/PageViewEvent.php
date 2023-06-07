<?php
/**
 * User: Damian Zamojski (br33f)
 * Date: 25.06.2021
 * Time: 13:33
 */

namespace nystudio107\instantanalyticsGa4\ga4\events;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use Br33f\Ga4\MeasurementProtocol\Dto\Parameter\AbstractParameter;
use Br33f\Ga4\MeasurementProtocol\Enum\ErrorCode;
use Br33f\Ga4\MeasurementProtocol\Exception\ValidationException;

/**
 * Class PageViewEvent
 *
 * @method string getPageTitle()
 * @method string getPageLocation()
 * @method PageViewEvent setPageTitle(string $title)
 * @method PageViewEvent setPageLocation(string $url)
 */
class PageViewEvent extends BaseEvent
{
    private $eventName = 'page_view';

    /**
     * PageViewEvent constructor.
     * @param AbstractParameter[] $paramList
     */
    public function __construct(array $paramList = [])
    {
        parent::__construct($this->eventName, $paramList);
    }

    public function validate()
    {
        parent::validate();

        if (empty($this->getPageTitle())) {
            throw new ValidationException('Field "page_title" is required.', ErrorCode::VALIDATION_FIELD_REQUIRED, 'page_title');
        }

        if (empty($this->getPageLocation())) {
            throw new ValidationException('Field "page_location" is required if "value" is set', ErrorCode::VALIDATION_FIELD_REQUIRED, 'page_location');
        }

        return true;
    }
}
