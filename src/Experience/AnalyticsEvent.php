<?php
declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Experience;

final readonly class AnalyticsEvent
{
    private const EVENTS = ['page_view','member_summary_loaded','transfer_section_viewed','transfer_preview_started','transfer_preview_submitted','transfer_preview_validation_failed','transfer_preview_succeeded','verification_review_shown','navigation_selected','help_opened'];
    private const PROPERTIES = ['transfer_preview_validation_failed'=>['error_category'],'navigation_selected'=>['destination']];
    private const ERRORS = ['amount_format','same_account','insufficient_available_balance','verification_required'];
    private const DESTINATIONS = ['accounts','transfer','help'];

    /** @param array<string,string> $properties */
    public function __construct(
        public string $eventId,
        public string $anonymousSessionId,
        public string $eventName,
        public \DateTimeImmutable $occurredAt,
        public DeviceClass $device,
        public string $pageOrWorkflow,
        public array $properties = [],
    ) {
        if (!preg_match('/^event-\d{6}$/', $eventId)) throw new \InvalidArgumentException('Analytics event ID must be deterministic.');
        if (!preg_match('/^session-\d{4}$/', $anonymousSessionId)) throw new \InvalidArgumentException('An anonymous teaching session ID is required.');
        if (!in_array($eventName, self::EVENTS, true)) throw new \InvalidArgumentException('Analytics event name is not allow-listed.');
        $allowed=self::PROPERTIES[$eventName]??[];
        if (array_diff(array_keys($properties),$allowed)!==[]) throw new \InvalidArgumentException('Analytics property is not allow-listed.');
        if(isset($properties['error_category'])&&!in_array($properties['error_category'],self::ERRORS,true)) throw new \InvalidArgumentException('Error category is not allow-listed.');
        if(isset($properties['destination'])&&!in_array($properties['destination'],self::DESTINATIONS,true)) throw new \InvalidArgumentException('Navigation destination is not allow-listed.');
    }
}
