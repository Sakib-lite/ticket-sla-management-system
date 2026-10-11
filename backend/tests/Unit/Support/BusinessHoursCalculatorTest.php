<?php

namespace Tests\Unit\Support;

use App\Support\BusinessHours\BusinessHoursCalculator;
use App\Support\BusinessHours\BusinessSchedule;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BusinessHoursCalculatorTest extends TestCase
{
    private const TIMEZONE = 'Asia/Dhaka';

    private BusinessHoursCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new BusinessHoursCalculator(
            new BusinessSchedule(self::TIMEZONE, [0, 1, 2, 3, 4], '09:00', '18:00')
        );
    }

    /** @return array<string, array{string, float, string}> */
    public static function deadlines(): array
    {
        return [
            'critical ticket on Thursday 17:00 carries one hour into Sunday' => ['2026-10-08 17:00', 2, '2026-10-11 10:00'],
            'ticket raised on Friday starts the clock on Sunday morning' => ['2026-10-09 11:00', 2, '2026-10-11 11:00'],
            'ticket raised before opening starts at opening time' => ['2026-10-11 07:30', 1, '2026-10-11 10:00'],
            'ticket raised exactly at closing rolls to the next day' => ['2026-10-11 18:00', 1, '2026-10-12 10:00'],
            'ticket raised inside hours with room left is simply added' => ['2026-10-12 10:15', 3.5, '2026-10-12 13:45'],
            'ticket raised at closing on Thursday waits for Sunday' => ['2026-10-08 18:00', 0.5, '2026-10-11 09:30'],
            'low priority ticket spans a full week and a weekend' => ['2026-10-11 09:00', 48, '2026-10-18 12:00'],
            'zero hours returns the next working moment' => ['2026-10-10 12:00', 0, '2026-10-11 09:00'],
        ];
    }

    #[DataProvider('deadlines')]
    public function test_it_adds_business_hours_across_days_and_weekends(string $from, float $hours, string $expected): void
    {
        $result = $this->calculator->addHours(CarbonImmutable::parse($from, self::TIMEZONE), $hours);

        $this->assertSame($expected, $result->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'));
    }

    public function test_the_result_is_returned_in_utc(): void
    {
        $result = $this->calculator->addHours(CarbonImmutable::parse('2026-10-12 10:00', self::TIMEZONE), 1);

        $this->assertSame('UTC', $result->timezoneName);
        $this->assertSame('2026-10-12 05:00', $result->format('Y-m-d H:i'));
    }

    public function test_input_in_another_timezone_is_converted_first(): void
    {
        $thursdayFivePmInDhaka = CarbonImmutable::parse('2026-10-08 11:00', 'UTC');

        $result = $this->calculator->addHours($thursdayFivePmInDhaka, 2);

        $this->assertSame('2026-10-11 10:00', $result->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'));
    }

    public function test_a_schedule_needs_at_least_one_working_day(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BusinessSchedule(self::TIMEZONE, [], '09:00', '18:00');
    }

    public function test_a_schedule_must_open_before_it_closes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BusinessSchedule(self::TIMEZONE, [1], '18:00', '09:00');
    }
}
