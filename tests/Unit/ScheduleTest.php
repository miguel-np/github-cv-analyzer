<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Schedule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Contracts\Cache\CacheInterface;

final class ScheduleTest extends TestCase
{
    public function testGetScheduleReturnsScheduleWithRecurringMessage(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $scheduleProvider = new Schedule($cache, '12 hours');

        $schedule = $scheduleProvider->getSchedule();

        self::assertInstanceOf(SymfonySchedule::class, $schedule);

        $messages = $schedule->getRecurringMessages();
        self::assertCount(1, $messages);
    }

    public function testGetScheduleIsStateful(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $scheduleProvider = new Schedule($cache, '12 hours');

        $schedule = $scheduleProvider->getSchedule();

        self::assertNotEmpty($schedule->getRecurringMessages());
    }
}
