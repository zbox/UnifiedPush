<?php

namespace Zbox\UnifiedPush\Notification;

use Zbox\UnifiedPush\Message\Type\APNS as APNSMessage;
use Zbox\UnifiedPush\Notification\PayloadHandler\APNS as APNSPayloadHandler;
use Zbox\UnifiedPush\NotificationService\NotificationServices;

class NotificationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NotificationBuilder
     */
    protected $notificationBuilder;

    public function setUp()
    {
        $this->notificationBuilder = new NotificationBuilder();
    }

    public function testAddPayloadHandler()
    {
        $handler = $this->getMock('\Zbox\UnifiedPush\Notification\PayloadHandlerInterface');

        $this
            ->notificationBuilder
            ->addPayloadHandler($handler);

        $this->assertContains(
            $handler,
            $this->notificationBuilder->getPayloadHandlers()
        );
    }

    public function testBuildNotifications()
    {
        $this
            ->notificationBuilder
            ->addPayloadHandler(new APNSPayloadHandler());

        $notifications = $this->notificationBuilder->buildNotifications($this->createAPNSMessage());

        $this->assertContainsOnlyInstancesOf(new Notification(), $notifications);
        $this->assertNotificationType($notifications);
    }

    /**
     * @param \ArrayIterator $notifications
     */
    protected function assertNotificationType(\ArrayIterator $notifications)
    {
        $notifications->rewind();
        /** @var Notification $notification */
        $notification = $notifications->current();

        $this->assertSame($notification->getType(), NotificationServices::APPLE_PUSH_NOTIFICATIONS_SERVICE);
    }

    /**
     * @return APNSMessage
     */
    protected function createAPNSMessage()
    {
        $message = new APNSMessage();

        $message
            ->addRecipient('4efa148eb41f2e7103f21410bf48346c1afa148eb41f2e7103f21410bf48346c')
            ->setAlert('Text of an alert')
            ->setSound('test')
            ->setCategory('test')
            ->setBadge(1)
            ->setContentAvailable(true)
            ->setCustomPayloadData(array('key' => 'val'))
        ;
        return $message;
    }
}
