# Magento 2 Cheat Sheet + LeisPet Marketing Module Starter

## 1) Magento 2 at a Glance

Magento 2 is a modular PHP ecommerce platform. Typical stack:

- PHP 8.x (application layer)
- MySQL/MariaDB (data)
- Redis (cache/session)
- OpenSearch (catalog search)
- RabbitMQ (message queue / async consumers)
- Nginx + PHP-FPM (runtime)
- Optional PWA Studio (React storefront)

---

## 2) Core Concepts You Must Know

### Module System
Every feature is a module. Custom modules live in:

- `magento2/app/code/Vendor/Module`

Minimum module files:

- `registration.php`
- `etc/module.xml`

### DI (Dependency Injection)
Inject services via constructor. Avoid `new` for framework/domain dependencies.
Example:

```php
public function __construct(
    private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    private readonly \Magento\Framework\MessageQueue\PublisherInterface $publisher
) {
}
```

### Plugin vs Observer

- Plugin = intercept method (`before`, `around`, `after`)
- Observer = react to event (`events.xml`)

### Service Contracts
Use interfaces for API/domain boundaries:

- `Api/*Interface.php`
- `Api/Data/*Interface.php`

### Resource Models
Persistence pattern:

- `Model/*`
- `Model/ResourceModel/*`
- `Model/ResourceModel/*/Collection.php`

### Declarative Schema
Use `etc/db_schema.xml` for tables/indexes.

---

## 3) Commands You Will Use Daily

```bash
cd /home/cmedeiros/Documents/Cassiano-Portfolio/PHP-Projects/magento2_ecomm
./magento.sh setup:upgrade
./magento.sh cache:clean
./magento.sh cache:flush
./magento.sh indexer:reindex
./magento.sh module:status
./magento.sh module:enable Vendor_Module
./magento.sh queue:consumers:list
./magento.sh queue:consumers:start <consumer_name>
```

For this Docker workspace:

```bash
cd /home/cmedeiros/Documents/Cassiano-Portfolio/PHP-Projects/magento2_ecomm
./magento.sh cache:flush
./magento.sh indexer:reindex
docker compose logs -f phpfpm web
```

---

## 4) Use Case: Create a New Storefront Page (PWA Studio)

Location:

- `pwa-studio/react-storefront/src/components/HelpCenterPage`

Create:

- `helpCenterPage.js`
- `helpCenterPage.module.css`

### `helpCenterPage.js`

```javascript
import React from 'react';
import classes from './helpCenterPage.module.css';

const HelpCenterPage = () => {
    return (
        <main className={classes.root}>
            <h1>Help Center</h1>
            <p>This is a new storefront page.</p>
        </main>
    );
};

export default HelpCenterPage;
```

### `helpCenterPage.module.css`

```css
.root {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}
```

Register route in `pwa-studio/react-storefront/local-intercept.js`:

```javascript
targets.of('@magento/venia-ui').routes.tap(routes => {
    routes.push({
        name: 'HelpCenterPage',
        pattern: '/help-center',
        path: require.resolve('./src/components/HelpCenterPage/helpCenterPage.js')
    });
});
```

---

## 5) Use Case: Create a New Magento Module (from scratch)

Example: `LeisPet_Blog`

Path:

- `magento2/app/code/LeisPet/Blog`

Minimum files:

### `registration.php`

```php
<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'LeisPet_Blog',
    __DIR__
);
```

### `etc/module.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="LeisPet_Blog"/>
</config>
```

Enable/install:

```bash
cd /home/cmedeiros/Documents/Cassiano-Portfolio/PHP-Projects/magento2_ecomm
./magento.sh module:enable LeisPet_Blog
./magento.sh setup:upgrade
./magento.sh cache:flush
```

---

## 6) LeisPet Marketing Module (Complete Starter Blueprint)

Goal: native email campaigns with async workers, resumable progress, and admin controls.

Module name:

- `LeisPet_Marketing`

Root path:

- `magento2/app/code/LeisPet/Marketing`

---

## 6.1 Folder Structure

```text
app/code/LeisPet/Marketing/
├── registration.php
├── composer.json
├── etc/
│   ├── module.xml
│   ├── db_schema.xml
│   ├── di.xml
│   ├── acl.xml
│   ├── communication.xml
│   ├── queue_topology.xml
│   ├── queue_publisher.xml
│   ├── queue_consumer.xml
│   └── adminhtml/
│       ├── routes.xml
│       └── menu.xml
├── Api/
│   ├── CampaignRepositoryInterface.php
│   └── Data/
│       └── CampaignInterface.php
├── Controller/Adminhtml/Campaign/
│   ├── Index.php
│   ├── Save.php
│   ├── Enqueue.php
│   ├── Status.php
│   ├── Pause.php
│   ├── Resume.php
│   └── Cancel.php
├── Model/
│   ├── Campaign.php
│   ├── CampaignRepository.php
│   ├── CampaignJob.php
│   ├── Queue/
│   │   ├── Publisher.php
│   │   └── Consumer/CampaignSendConsumer.php
│   ├── Service/
│   │   ├── CampaignManager.php
│   │   └── RecipientProvider.php
│   └── ResourceModel/
│       ├── Campaign.php
│       ├── Campaign/Collection.php
│       ├── CampaignJob.php
│       └── CampaignJob/Collection.php
└── view/adminhtml/
    ├── layout/
    │   ├── leispet_marketing_campaign_index.xml
    │   └── leispet_marketing_campaign_edit.xml
    ├── ui_component/
    │   ├── leispet_marketing_campaign_listing.xml
    │   └── leispet_marketing_campaign_form.xml
    └── templates/
        └── campaign/progress.phtml
```

---

## 6.2 Core Module Files

### `registration.php`

```php
<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'LeisPet_Marketing',
    __DIR__
);
```

### `composer.json`

```json
{
  "name": "leispet/module-marketing",
  "description": "LeisPet Marketing Campaigns",
  "type": "magento2-module",
  "version": "1.0.0",
  "license": ["proprietary"],
  "autoload": {
    "files": ["registration.php"],
    "psr-4": {
      "LeisPet\\Marketing\\": ""
    }
  }
}
```

### `etc/module.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="LeisPet_Marketing">
        <sequence>
            <module name="Magento_Newsletter"/>
            <module name="Magento_Email"/>
        </sequence>
    </module>
</config>
```

---

## 6.3 Database Schema

### `etc/db_schema.xml`

```xml
<?xml version="1.0"?>
<schema xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd">

    <table name="leispet_marketing_campaign" resource="default" engine="innodb" comment="LeisPet Marketing Campaign">
        <column xsi:type="int" name="campaign_id" unsigned="true" nullable="false" identity="true" comment="Campaign ID"/>
        <column xsi:type="varchar" name="name" length="255" nullable="false" comment="Name"/>
        <column xsi:type="varchar" name="status" length="32" nullable="false" default="draft" comment="Status"/>
        <column xsi:type="varchar" name="subject" length="255" nullable="false" comment="Subject"/>
        <column xsi:type="varchar" name="sender_name" length="120" nullable="false" comment="Sender Name"/>
        <column xsi:type="varchar" name="sender_email" length="255" nullable="false" comment="Sender Email"/>
        <column xsi:type="varchar" name="template_identifier" length="255" nullable="false" comment="Template Identifier"/>
        <column xsi:type="varchar" name="audience_type" length="32" nullable="false" default="newsletter" comment="Audience Type"/>
        <column xsi:type="text" name="audience_filter_json" nullable="true" comment="Audience Filter JSON"/>
        <column xsi:type="timestamp" name="scheduled_at" nullable="true" comment="Scheduled At"/>
        <column xsi:type="timestamp" name="started_at" nullable="true" comment="Started At"/>
        <column xsi:type="timestamp" name="finished_at" nullable="true" comment="Finished At"/>
        <column xsi:type="int" name="total_recipients" unsigned="true" nullable="false" default="0" comment="Total Recipients"/>
        <column xsi:type="int" name="processed_count" unsigned="true" nullable="false" default="0" comment="Processed Count"/>
        <column xsi:type="int" name="sent_count" unsigned="true" nullable="false" default="0" comment="Sent Count"/>
        <column xsi:type="int" name="failed_count" unsigned="true" nullable="false" default="0" comment="Failed Count"/>
        <column xsi:type="timestamp" name="created_at" nullable="false" default="CURRENT_TIMESTAMP" comment="Created At"/>
        <column xsi:type="timestamp" name="updated_at" nullable="false" default="CURRENT_TIMESTAMP" on_update="true" comment="Updated At"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="campaign_id"/>
        </constraint>
        <index referenceId="LEISPET_MARKETING_CAMPAIGN_STATUS" indexType="btree">
            <column name="status"/>
        </index>
    </table>

    <table name="leispet_marketing_campaign_job" resource="default" engine="innodb" comment="LeisPet Marketing Campaign Job">
        <column xsi:type="int" name="job_id" unsigned="true" nullable="false" identity="true" comment="Job ID"/>
        <column xsi:type="int" name="campaign_id" unsigned="true" nullable="false" comment="Campaign ID"/>
        <column xsi:type="varchar" name="recipient_email" length="255" nullable="false" comment="Recipient Email"/>
        <column xsi:type="varchar" name="recipient_name" length="255" nullable="true" comment="Recipient Name"/>
        <column xsi:type="varchar" name="recipient_type" length="32" nullable="false" default="subscriber" comment="Recipient Type"/>
        <column xsi:type="varchar" name="status" length="32" nullable="false" default="queued" comment="Status"/>
        <column xsi:type="smallint" name="attempts" nullable="false" default="0" comment="Attempts"/>
        <column xsi:type="text" name="last_error" nullable="true" comment="Last Error"/>
        <column xsi:type="varchar" name="message_id" length="255" nullable="true" comment="Message ID"/>
        <column xsi:type="timestamp" name="processed_at" nullable="true" comment="Processed At"/>
        <column xsi:type="timestamp" name="created_at" nullable="false" default="CURRENT_TIMESTAMP" comment="Created At"/>
        <column xsi:type="timestamp" name="updated_at" nullable="false" default="CURRENT_TIMESTAMP" on_update="true" comment="Updated At"/>

        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="job_id"/>
        </constraint>

        <constraint xsi:type="foreign" referenceId="LEISPET_MARKETING_JOB_CAMPAIGN_ID"
                    table="leispet_marketing_campaign_job" column="campaign_id"
                    referenceTable="leispet_marketing_campaign" referenceColumn="campaign_id"
                    onDelete="CASCADE"/>

        <constraint xsi:type="unique" referenceId="LEISPET_MARKETING_JOB_CAMPAIGN_EMAIL_UNQ">
            <column name="campaign_id"/>
            <column name="recipient_email"/>
        </constraint>

        <index referenceId="LEISPET_MARKETING_JOB_STATUS" indexType="btree">
            <column name="status"/>
        </index>
    </table>

</schema>
```

---

## 6.4 Admin Route, ACL, Menu

### `etc/adminhtml/routes.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="admin">
        <route id="leispet_marketing" frontName="leispet_marketing">
            <module name="LeisPet_Marketing" before="Magento_Backend"/>
        </route>
    </router>
</config>
```

### `etc/acl.xml`

```xml
<?xml version="1.0"?>
<acl xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="LeisPet_Marketing::marketing" title="LeisPet Marketing" sortOrder="10">
                <resource id="LeisPet_Marketing::campaigns" title="Campaigns" sortOrder="10"/>
            </resource>
        </resource>
    </resources>
</acl>
```

### `etc/adminhtml/menu.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Backend:etc/menu.xsd">
    <menu>
        <add id="LeisPet_Marketing::marketing"
             title="LeisPet Marketing"
             module="LeisPet_Marketing"
             sortOrder="90"
             resource="LeisPet_Marketing::marketing"/>

        <add id="LeisPet_Marketing::campaigns"
             title="Email Campaigns"
             module="LeisPet_Marketing"
             sortOrder="10"
             parent="LeisPet_Marketing::marketing"
             action="leispet_marketing/campaign/index"
             resource="LeisPet_Marketing::campaigns"/>
    </menu>
</config>
```

---

## 6.5 Queue Wiring (Async)

### `etc/communication.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Communication/etc/communication.xsd">
    <topic name="leispet.marketing.campaign.send" request="string"/>
</config>
```

### `etc/queue_topology.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework-message-queue:etc/topology.xsd">
    <exchange name="magento" type="topic" connection="amqp">
        <binding id="leispetMarketingSendBinding"
                 topic="leispet.marketing.campaign.send"
                 destinationType="queue"
                 destination="leispet.marketing.campaign.send"/>
    </exchange>
</config>
```

### `etc/queue_publisher.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework-message-queue:etc/publisher.xsd">
    <publisher topic="leispet.marketing.campaign.send">
        <connection name="amqp" exchange="magento"/>
    </publisher>
</config>
```

### `etc/queue_consumer.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework-message-queue:etc/consumer.xsd">
    <consumer name="leispet.marketing.campaign.send"
              queue="leispet.marketing.campaign.send"
              connection="amqp"
              handler="LeisPet\Marketing\Model\Queue\Consumer\CampaignSendConsumer::process"/>
</config>
```

---

## 6.6 Model + ResourceModel (minimum)

### `Model/Campaign.php`

```php
<?php
namespace LeisPet\Marketing\Model;

use Magento\Framework\Model\AbstractModel;

class Campaign extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Marketing\Model\ResourceModel\Campaign::class);
    }
}
```

### `Model/ResourceModel/Campaign.php`

```php
<?php
namespace LeisPet\Marketing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Campaign extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('leispet_marketing_campaign', 'campaign_id');
    }
}
```

### `Model/ResourceModel/Campaign/Collection.php`

```php
<?php
namespace LeisPet\Marketing\Model\ResourceModel\Campaign;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Marketing\Model\Campaign::class, \LeisPet\Marketing\Model\ResourceModel\Campaign::class);
    }
}
```

Create equivalent files for `CampaignJob`.

---

## 6.7 Publisher + Consumer (core async behavior)

### `Model/Queue/Publisher.php`

```php
<?php
namespace LeisPet\Marketing\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;

class Publisher
{
    public const TOPIC = 'leispet.marketing.campaign.send';

    public function __construct(private readonly PublisherInterface $publisher)
    {
    }

    public function publish(int $jobId): void
    {
        $this->publisher->publish(self::TOPIC, (string) $jobId);
    }
}
```

### `Model/Queue/Consumer/CampaignSendConsumer.php`

```php
<?php
namespace LeisPet\Marketing\Model\Queue\Consumer;

use LeisPet\Marketing\Model\CampaignJobFactory;
use LeisPet\Marketing\Model\ResourceModel\CampaignJob as CampaignJobResource;
use Psr\Log\LoggerInterface;

class CampaignSendConsumer
{
    public function __construct(
        private readonly CampaignJobFactory $jobFactory,
        private readonly CampaignJobResource $jobResource,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(string $jobIdPayload): void
    {
        $jobId = (int) $jobIdPayload;
        $job = $this->jobFactory->create();
        $this->jobResource->load($job, $jobId);

        if (!$job->getId()) {
            return;
        }

        if ((string)$job->getData('status') !== 'queued') {
            return;
        }

        try {
            $job->setData('status', 'processing');
            $this->jobResource->save($job);

            // TODO: build and send email via TransportBuilder.

            $job->setData('status', 'sent');
            $job->setData('processed_at', date('Y-m-d H:i:s'));
            $this->jobResource->save($job);
        } catch (\Throwable $e) {
            $job->setData('status', 'failed');
            $job->setData('last_error', $e->getMessage());
            $job->setData('attempts', ((int)$job->getData('attempts')) + 1);
            $this->jobResource->save($job);

            $this->logger->error('LeisPet Marketing consumer failure', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

---

## 6.8 Admin Controllers (minimum AJAX contract)

Implement these in `Controller/Adminhtml/Campaign`:

- `Index.php` (grid page)
Example `Index.php`:

```php
<?php
namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(Context $context, private readonly PageFactory $resultPageFactory)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('LeisPet_Marketing::campaigns');
        $resultPage->getConfig()->getTitle()->prepend(__('Email Campaigns'));

        return $resultPage;
    }
}
```
```
{
  "success": true,
  "campaign_id": 1,
  "status": "processing",
  "total_recipients": 1200,
  "processed_count": 340,
  "sent_count": 330,
  "failed_count": 10
}
```

- `Save.php` (save campaign)
Example `Save.php`:

```php
<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $data = $this->getRequest()->getPostValue();

        try {
            $campaign = $this->campaignRepository->saveCampaignData($data);
            return $result->setData([
                'success' => true,
                'campaign_id' => $campaign->getId(),
                'status' => $campaign->getData('status'),
                'total_recipients' => $campaign->getData('total_recipients'),
                'processed_count' => $campaign->getData('processed_count'),
                'sent_count' => $campaign->getData('sent_count'),
                'failed_count' => $campaign->getData('failed_count')
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

- `Enqueue.php` (create recipient jobs + publish)
Example `Enqueue.php`:

```php
<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Enqueue extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $this->campaignRepository->enqueueCampaign($campaignId);
            return $result->setData([
                'success' => true,
                'message' => __('Campaign enqueued successfully')
                ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

- `Status.php` (return counters and status)
Example `Status.php`:

```php
<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Status extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $statusData = $this->campaignRepository->getCampaignStatus($campaignId);
            return $result->setData(array_merge(['success' => true], $statusData));
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

- `Pause.php` / `Resume.php` / `Cancel.php` (state transitions)
Example `Pause.php`:

```php
<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Pause extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $this->campaignRepository->pauseCampaign($campaignId);
            return $result->setData(['success' => true]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

Example `Resume.php`:

```php
<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Resume extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $this->campaignRepository->resumeCampaign($campaignId);
            return $result->setData(['success' => true]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

Example `Cancel.php`:

```php
<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Cancel extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $this->campaignRepository->cancelCampaign($campaignId);
            return $result->setData(['success' => true]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

Use consistent JSON:

```json
{
  "success": true,
  "campaign_id": 1,
  "status": "processing",
  "total_recipients": 1200,
  "processed_count": 340,
  "sent_count": 330,
  "failed_count": 10
}
```

---

## 6.9 DI (`etc/di.xml`)

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="LeisPet\Marketing\Api\CampaignRepositoryInterface"
                type="LeisPet\Marketing\Model\CampaignRepository"/>
</config>
```

---

## 6.10 Enable + Run

```bash
cd /home/cmedeiros/Documents/Cassiano-Portfolio/PHP-Projects/magento2_ecomm
./magento.sh module:enable LeisPet_Marketing
./magento.sh setup:upgrade
./magento.sh cache:flush
```

Start consumer:

```bash
./magento.sh queue:consumers:start leispet.marketing.campaign.send
```

Note: this consumer is long-running. If there are no messages it waits for new ones.
Pressing `Ctrl+C` will stop it and usually returns exit code `130` (this is expected, not a failure).

---

## 6.11 Progress Recovery Pattern (important)

1. Save campaign and jobs in DB first
2. Push only `job_id` to queue
3. Worker updates `job.status`
4. UI polls `Status.php`
5. On refresh, UI re-reads campaign and resumes progress bar

This avoids losing progress when admin page reloads.

---

## 6.12 Beginner Pitfalls

- Doing email send in controller request (blocks UI and causes timeouts)
- Not idempotent worker logic
- Missing ACL for admin endpoints
- Missing `setup:upgrade` after schema changes
- Ignoring queue consumer process state
- Not logging worker errors in `system.log`

---

## 7) Suggested Next Step

After this starter works, add:

- newsletter recipient import service
- customer group filters
- email template variable preview
- open/click tracking endpoints
- campaign analytics grid
