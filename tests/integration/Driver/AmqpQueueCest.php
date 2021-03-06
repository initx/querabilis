<?php declare(strict_types=1);

namespace Tests\Integration\Driver;

use Codeception\Example;
use Initx\Querabilis\Driver\AmqpQueue;
use Initx\Querabilis\Exception\NoSuchElementException;
use Initx\Querabilis\Tests\Double\AmqpConnectionMother;
use Initx\Querabilis\Tests\Double\EnvelopeMother;
use Initx\Querabilis\Tests\IntegrationTester;

class AmqpQueueCest
{
    private const EXCHANGE = 'amq.fanout';

    private const QUEUE = 'kue1';

    public function _before(): void
    {
        $channel = AmqpConnectionMother::default()->channel();
        $channel->queue_declare(self::QUEUE);
        $channel->queue_purge(self::QUEUE);
        $channel->queue_bind(self::QUEUE, self::EXCHANGE);
    }

    public function add(IntegrationTester $I): void
    {
        $envelope = EnvelopeMother::any();
        $queue = new AmqpQueue(AmqpConnectionMother::default(), self::QUEUE, self::EXCHANGE);

        $actual = $queue->add($envelope);

        $I->assertTrue($actual);
    }

    public function offer(IntegrationTester $I): void
    {
        $envelope = EnvelopeMother::any();
        $queue = new AmqpQueue(AmqpConnectionMother::default(), self::QUEUE, self::EXCHANGE);

        $actual = $queue->offer($envelope);

        $I->assertTrue($actual);
    }

    /**
     * @example { "method": "remove" }
     * @example { "method": "poll" }
     */
    public function removeMethods(IntegrationTester $I, Example $example): void
    {
        $envelopeOne = EnvelopeMother::any();
        $envelopeTwo = EnvelopeMother::any();
        $queue = new AmqpQueue(AmqpConnectionMother::default(), self::QUEUE, self::EXCHANGE);
        $queue->add($envelopeOne);
        $queue->add($envelopeTwo);
        $method = $example['method'];

        $actualOne = $queue->$method();
        $actualTwo = $queue->$method();

        $I->assertEquals($envelopeOne, $actualOne);
        $I->assertEquals($envelopeTwo, $actualTwo);
    }

    /**
     * @example { "method": "peek" }
     * @example { "method": "element" }
     */
    public function examineMethods(IntegrationTester $I, Example $example): void
    {
        $envelopeOne = EnvelopeMother::any();
        $queue = new AmqpQueue(AmqpConnectionMother::default(), self::QUEUE, self::EXCHANGE);
        $queue->add($envelopeOne);
        $queue->add(EnvelopeMother::any());
        $method = $example['method'];

        $actualOne = $queue->$method();
        $actualTwo = $queue->$method();

        $I->assertEquals($envelopeOne, $actualOne);
        // actual two = envelope one
        $I->assertEquals($envelopeOne, $actualTwo);
    }

    /**
     * @example { "method": "remove" }
     * @example { "method": "element" }
     */
    public function throwsWhenEmpty(IntegrationTester $I, Example $example): void
    {
        $queue = new AmqpQueue(AmqpConnectionMother::default(), self::QUEUE, self::EXCHANGE);
        $method = $example['method'];

        $I->expectException(NoSuchElementException::class, function () use ($queue, $method): void {
            $queue->$method();
        });
    }
}
