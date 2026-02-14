<?php

namespace Petshop\Marketing\Api\Data;

interface CampaignInterface
{
	public const CAMPAIGN_ID = 'campaign_id';
	public const NAME = 'name';
	public const STATUS = 'status';
	public const SUBJECT = 'subject';
	public const SENDER_NAME = 'sender_name';
	public const SENDER_EMAIL = 'sender_email';
	public const TEMPLATE_IDENTIFIER = 'template_identifier';
	public const AUDIENCE_TYPE = 'audience_type';
	public const AUDIENCE_FILTER_JSON = 'audience_filter_json';
	public const SCHEDULED_AT = 'scheduled_at';
	public const STARTED_AT = 'started_at';
	public const FINISHED_AT = 'finished_at';
	public const TOTAL_RECIPIENTS = 'total_recipients';
	public const PROCESSED_COUNT = 'processed_count';
	public const SENT_COUNT = 'sent_count';
	public const FAILED_COUNT = 'failed_count';
}
