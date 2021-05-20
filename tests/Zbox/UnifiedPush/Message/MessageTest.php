<?php

namespace Zbox\UnifiedPush\Message;

use Zbox\UnifiedPush\Message\Type\APNSAlert;
use Zbox\UnifiedPush\Notification\PayloadHandlerInterface;
use Zbox\UnifiedPush\Message\Type\APNS as APNSMessage;
use Zbox\UnifiedPush\Message\Type\GCM as GCMMessage;
use Zbox\UnifiedPush\Message\Type\MPNSRaw as MPNSRawMessage;
use Zbox\UnifiedPush\Exception\DomainException;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    const APNS_MESSAGE = 'APNS';
    const APNS_EXT_MESSAGE = 'APNS_EXT';
    const GCM_MESSAGE  = 'GCM';
    const MPNS_MESSAGE = 'MPNS';

    /**
     * @dataProvider createMessageProvider
     * @param string $messageType
     * @param string $deviceToken
     * @param array $messageSample
     */
    public function testCreateMessage($messageType, $deviceToken, $messageSample)
    {
        $message         = $this->createMessageOfType($messageType);
        $recipients      = new \ArrayIterator();

        $recipients->append($deviceToken);

        $message->addRecipientIdentifiers($recipients);

        $handlerName = substr($messageType, 0, strcspn($messageType,'_'));

        $this->assertEquals(
            $messageSample,
            $this
                ->getPayloadHandlerByType($handlerName)
                ->setMessage($message)
                ->createPayload()
        );
    }

    /**
     * Create Message data provider
     */
    public static function createMessageProvider()
    {
        return array(
            'TestApnsMessage' => array(
                self::APNS_MESSAGE,
                '4efa148eb41f2e7103f21410bf48346c1afa148eb41f2e7103f21410bf48346c',
                self::getAPNSPayload('Text of an alert'),
            ),
            'TestApnsExtendedMessage' => array(
                self::APNS_EXT_MESSAGE,
                '4efa148eb41f2e7103f21410bf48346c1afa148eb41f2e7103f21410bf48346c',
                self::getAPNSPayload(
                    array(
                        'body'              => 'b',
                        'title'             => 't',
                        'title-loc-key'     => 'tlc',
                        'title-loc-args'    => array('tla'),
                        'action-loc-key'    => 'alk',
                        'loc-key'           => 'lk',
                        'loc-args'          => array('la'),
                        'launch-image'      => 'li'
                    )
                ),
            ),
            'TestGCMMessage' => array(
                self::GCM_MESSAGE,
                'device1',
                array(
                    'collapse_key'     => 1,
                    'delay_while_idle' => true,
                    'registration_ids' => array('device1'),
                    'data'             => array('key' => 'val'),
                    'time_to_live'     => 10,
                    'dry_run'          => true
            )),
            'TestMPNSMessage' => array(
                self::MPNS_MESSAGE,
                'ZGV2aWNlIGlkZW50aWZpZXI=',
                self::exampleMPNSMessage()
            )
        );
    }

    /**
     * @dataProvider validateRecipientProvider
     * @param string $messageType
     * @param string $token
     * @param bool $isVaid
     */
    public function testValidateRecipient($messageType, $token, $isVaid)
    {
        $message = $this->createMessageOfType($messageType);

        if (!$isVaid) {
            $this->setExpectedException('Zbox\UnifiedPush\Exception\InvalidArgumentException');
        }

        $this->assertEquals(
            $message->validateRecipient($token),
            $isVaid
        );
    }

    /**
     * Validate Recipient data provider
     */
    public static function validateRecipientProvider()
    {
        return array(
            'Valid APNS token'    => array(
                self::APNS_MESSAGE,
                '4efa148eb41f2e7103f21410bf48346c1afa148eb41f2e7103f21410bf48346c',
                true
            ),
            'Invalid APNS token1' => array(
                self::APNS_MESSAGE,
                '4efa148eb41f2e7103f21410bf48346c1afa148eb41f2e7103f21410bf48346*',
                false
            ),
            'Invalid APNS token2' => array(
                self::APNS_MESSAGE,
                '4efa148e',
                false
            ),
            'Valid GCM token'     => array(
                self::GCM_MESSAGE,
                'VWX4efa148e',
                true
            ),
            'Invalid GCM token'   => array(
                self::GCM_MESSAGE,
                'VWX4efa148*',
                false
            ),
            'Valid MPNS token'     => array(
                self::MPNS_MESSAGE,
                'ZGV2aWNlIGlkZW50aWZpZXI=',
                true
            ),
            'Invalid MPNS token'   => array(
                self::MPNS_MESSAGE,
                'VWX4efa148*',
                false
            )
        );
    }

    /**
     * @param string $messageType
     * @return APNSMessage|GCMMessage
     */
    public function createMessageOfType($messageType)
    {
        switch ($messageType) {
            case self::APNS_MESSAGE:
                return $this->createAPNSMessage();
                break;

            case self::APNS_EXT_MESSAGE:
                return $this->createAPNSExtendedMessage();
                break;

            case self::GCM_MESSAGE:
                return $this->createGCMMessage();
                break;

            case self::MPNS_MESSAGE:
                return $this->createMPNSMessage();
                break;

            default:
                throw new DomainException(sprintf("Unsupported message type '%s'", $messageType));
                break;
        }
    }

    /**
     * @return APNSMessage
     */
    public function createAPNSMessage()
    {
        return
            $this
                ->createBaseAPNSMessage()
                ->setAlert('Text of an alert')
            ;
    }

    /**
     * @return APNSMessage
     */
    public function createAPNSExtendedMessage()
    {
        $dict = new APNSAlert();
        $dict
            ->setBody('b')
            ->setActionLocKey('alk')
            ->setLaunchImage('li')
            ->setLocArgs(array('la'))
            ->setLocKey('lk')
            ->setTitle('t')
            ->setTitleLocArgs(array('tla'))
            ->setTitleLocKey('tlc')
        ;

        return
            $this
                ->createBaseAPNSMessage()
                ->setAlertDictionary($dict)
            ;
    }

    /**
     * @return GCMMessage
     */
    public function createGCMMessage()
    {
        $message = new GCMMessage();

        $message
            ->setCollapseKey(1)
            ->setDryRun(true)
            ->setDelayWhileIdle(true)
            ->setPackageName(true)
            ->setPayloadData(array('key' => 'val'))
            ->setExpirationTime(new \DateTime('+10 seconds'))
        ;
        return $message;
    }

    /**
     * @return MPNSRawMessage
     */
    public function createMPNSMessage()
    {
        $message = new MPNSRawMessage(array(
            'userDefinedRaw' => 'value'
        ));

        return $message;
    }

    /**
     * Example MPNS raw message
     * @return string
     */
    public static function exampleMPNSMessage()
    {
        $message      = new \DOMDocument("1.0", "utf-8");
        $baseElement  = $message->createElement("wp:Notification");
        $baseElement->setAttribute("xmlns:wp", "WPNotification");
        $message->appendChild($baseElement);

        $rootElement = $message->createElement("root");
        $baseElement->appendChild($rootElement);
        $element = $message->createElement("userDefinedRaw", "value");
        $rootElement->appendChild($element);

        return $message;
    }

    /**
     * @return APNSMessage
     */
    protected function createBaseAPNSMessage()
    {
        $message = new APNSMessage();

        $message
            ->setSound('test')
            ->setCategory('test')
            ->setBadge(1)
            ->setUrlArgs(array('test', '1'))
            ->setContentAvailable(true)
            ->setMutableContent(true)
            ->setCustomPayloadData(array('key' => 'val'))
        ;
        return $message;
    }

    /**
     * @param array|string $alert
     * @return array
     */
    protected static function getAPNSPayload($alert)
    {
        return
            array(
                'aps' => array(
                    'alert' => $alert,
                    'badge' => 1,
                    'sound' => 'test',
                    'category' => 'test',
                    'content-available' => 1,
                    'mutable-content' => 1,
                    'url-args' => array('test', '1')
                ),
                'key' => 'val'
            );
    }

    /**
     * @param string $type
     * @return PayloadHandlerInterface
     */
    protected function getPayloadHandlerByType($type)
    {
        $handlerClass = sprintf('\Zbox\UnifiedPush\Notification\PayloadHandler\%s', $type);

        return new $handlerClass;
    }
}
