<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\AuditableEvent;
use App\Contracts\AuditTrail;
use App\Contracts\AuditTrailReader;
use App\Models\User;
use App\Modules\Core\Domain\AuditLog;
use App\Support\Audit\AuditEntry;
use App\Support\Audit\AuditFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

/**
 * دفتر رویداد در سطح قرارداد.
 *
 * این‌جا عمداً هیچ ماژول دیگری صدا زده نمی‌شود: Core نباید برای اثبات درستی‌اش
 * به وجود ماژول محتوایی تکیه کند. اینکه ماژول Identity رویداد درست منتشر
 * می‌کند، در تست خودِ آن ماژول بررسی می‌شود.
 */
final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_event_implementing_the_contract_is_recorded(): void
    {
        Event::dispatch(new FakeAuditableEvent(new AuditEntry(
            action: 'fake.happened',
            subjectType: User::class,
            subjectId: 42,
            before: ['status' => 'pending'],
            after: ['status' => 'active'],
            context: ['reason' => 'آزمایش'],
        )));

        $log = AuditLog::query()->sole();

        $this->assertSame('fake.happened', $log->action);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame('42', $log->subject_id);
        $this->assertSame(['status' => 'pending'], $log->before);
        $this->assertSame(['status' => 'active'], $log->after);
        $this->assertSame(['reason' => 'آزمایش'], $log->context);
    }

    public function test_the_signed_in_user_becomes_the_actor_when_none_is_given(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->app->make(AuditTrail::class)->record(new AuditEntry(action: 'fake.happened'));

        $this->assertSame($user->getKey(), AuditLog::query()->sole()->actor_id);
    }

    public function test_an_explicit_actor_wins_over_the_signed_in_user(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other);
        $this->app->make(AuditTrail::class)->record(new AuditEntry(
            action: 'fake.happened',
            actorId: (int) $actor->getKey(),
        ));

        $this->assertSame($actor->getKey(), AuditLog::query()->sole()->actor_id);
    }

    public function test_a_system_action_without_a_signed_in_user_has_no_actor(): void
    {
        $this->app->make(AuditTrail::class)->record(new AuditEntry(action: 'system.cleanup'));

        $this->assertNull(AuditLog::query()->sole()->actor_id);
    }

    public function test_empty_payloads_are_stored_as_null_not_as_an_empty_object(): void
    {
        $this->app->make(AuditTrail::class)->record(new AuditEntry(action: 'system.cleanup'));

        $log = AuditLog::query()->sole();

        $this->assertNull($log->before);
        $this->assertNull($log->after);
        $this->assertNull($log->context);
    }

    public function test_records_can_be_filtered_by_subject(): void
    {
        $one = User::factory()->create();
        $two = User::factory()->create();

        $trail = $this->app->make(AuditTrail::class);
        $trail->record(new AuditEntry(action: 'a', subjectType: User::class, subjectId: $one->getKey()));
        $trail->record(new AuditEntry(action: 'b', subjectType: User::class, subjectId: $one->getKey()));
        $trail->record(new AuditEntry(action: 'c', subjectType: User::class, subjectId: $two->getKey()));

        $this->assertSame(2, AuditLog::query()->forSubject($one)->count());
        $this->assertSame(1, AuditLog::query()->forAction('c')->count());
    }

    public function test_the_reader_never_exposes_a_full_mobile_number(): void
    {
        // دفتر رویداد نباید از راه صفحه نمایش، نسخه دومی از داده شخصی بسازد.
        $user = User::factory()->create(['name' => null, 'mobile' => '09121234567']);

        $this->app->make(AuditTrail::class)->record(new AuditEntry(
            action: 'fake.happened',
            actorId: (int) $user->getKey(),
        ));

        $record = $this->app->make(AuditTrailReader::class)->search(new AuditFilter)[0];

        $this->assertSame('0912***4567', $record->actorName);
        $this->assertStringNotContainsString('09121234567', (string) $record->actorName);
    }

    public function test_the_reader_prefers_a_real_name_when_there_is_one(): void
    {
        $user = User::factory()->create(['name' => 'علیرضا عسکری']);

        $this->app->make(AuditTrail::class)->record(new AuditEntry(
            action: 'fake.happened',
            actorId: (int) $user->getKey(),
        ));

        $this->assertSame(
            'علیرضا عسکری',
            $this->app->make(AuditTrailReader::class)->search(new AuditFilter)[0]->actorName,
        );
    }

    public function test_the_reader_filters_and_counts(): void
    {
        $trail = $this->app->make(AuditTrail::class);
        $trail->record(new AuditEntry(action: 'a.one'));
        $trail->record(new AuditEntry(action: 'a.one'));
        $trail->record(new AuditEntry(action: 'b.two'));

        $reader = $this->app->make(AuditTrailReader::class);

        $this->assertSame(3, $reader->count(new AuditFilter));
        $this->assertSame(2, $reader->count(new AuditFilter(action: 'a.one')));
        $this->assertSame(['a.one', 'b.two'], $reader->knownActions());
    }

    public function test_the_reader_returns_newest_first(): void
    {
        $trail = $this->app->make(AuditTrail::class);
        $trail->record(new AuditEntry(action: 'first'));
        $trail->record(new AuditEntry(action: 'second'));

        $this->assertSame('second', $this->app->make(AuditTrailReader::class)->search(new AuditFilter)[0]->action);
    }

    public function test_a_record_can_never_be_edited(): void
    {
        $log = AuditLog::query()->create(['action' => 'fake.happened']);

        $this->expectException(RuntimeException::class);

        $log->update(['action' => 'fake.tampered']);
    }

    public function test_a_record_can_never_be_deleted(): void
    {
        $log = AuditLog::query()->create(['action' => 'fake.happened']);

        $this->expectException(RuntimeException::class);

        $log->delete();
    }
}

/** رویداد ساختگی، فقط برای اثبات اینکه پل روی خود قرارداد بسته شده است. */
final readonly class FakeAuditableEvent implements AuditableEvent
{
    public function __construct(private AuditEntry $entry) {}

    public function auditEntry(): AuditEntry
    {
        return $this->entry;
    }
}
