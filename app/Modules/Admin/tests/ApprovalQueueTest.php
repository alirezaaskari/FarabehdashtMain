<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests;

use App\Contracts\ApprovalQueueSource;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Admin\Services\AdminAccess;
use App\Modules\Admin\Services\ApprovalQueue;
use App\Support\Admin\PendingItem;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صف یکپارچه تأیید.
 *
 * هنوز هیچ ماژول محتوایی وجود ندارد، پس منابع ساختگی‌اند — ولی قراردادشان
 * همان است که ماژول‌های بخش‌های بعدی پیاده می‌کنند.
 */
final class ApprovalQueueTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    /** @param  list<PendingItem>  $items */
    private function queueWith(array $items): ApprovalQueue
    {
        $this->app->bind('test.queue.source', fn (): ApprovalQueueSource => new FakeQueueSource($items));
        $this->app->tag(['test.queue.source'], AdminServiceProvider::APPROVAL_SOURCES);

        return new ApprovalQueue(
            $this->app->tagged(AdminServiceProvider::APPROVAL_SOURCES),
            $this->app->make(AdminAccess::class),
        );
    }

    public function test_the_queue_is_empty_while_no_module_contributes(): void
    {
        $this->assertSame([], $this->app->make(ApprovalQueue::class)->for($this->adminWith(AdminRole::Super)));
    }

    public function test_each_admin_sees_only_what_they_can_decide(): void
    {
        $queue = $this->queueWith([
            new PendingItem('admin.content.review', 'article', 'مقاله اندازه‌گیری صدا', '/x/1'),
            new PendingItem('admin.settlement.approve', 'settlement', 'تسویه فروشنده ایمن‌کار', '/x/2'),
            new PendingItem('admin.jobs.review', 'job', 'آگهی کارشناس HSE', '/x/3'),
        ]);

        $titles = static fn (array $items): array => array_map(
            static fn (PendingItem $item): string => $item->title,
            $items,
        );

        $this->assertSame(['مقاله اندازه‌گیری صدا'], $titles($queue->for($this->adminWith(AdminRole::Content))));
        $this->assertSame(['تسویه فروشنده ایمن‌کار'], $titles($queue->for($this->adminWith(AdminRole::Finance))));
        $this->assertSame(['آگهی کارشناس HSE'], $titles($queue->for($this->adminWith(AdminRole::Jobs))));
    }

    public function test_the_super_admin_sees_everything(): void
    {
        $queue = $this->queueWith([
            new PendingItem('admin.content.review', 'article', 'مقاله', '/x/1'),
            new PendingItem('admin.settlement.approve', 'settlement', 'تسویه', '/x/2'),
            new PendingItem('admin.jobs.review', 'job', 'آگهی', '/x/3'),
        ]);

        $this->assertCount(3, $queue->for($this->adminWith(AdminRole::Super)));
    }

    public function test_the_longest_waiting_item_comes_first(): void
    {
        $queue = $this->queueWith([
            new PendingItem('admin.content.review', 'article', 'تازه', '/x/1', new DateTimeImmutable('2026-03-01')),
            new PendingItem('admin.content.review', 'article', 'قدیمی', '/x/2', new DateTimeImmutable('2026-01-01')),
        ]);

        $items = $queue->for($this->adminWith(AdminRole::Content));

        $this->assertSame('قدیمی', $items[0]->title);
    }

    public function test_an_item_without_a_waiting_date_goes_last(): void
    {
        $queue = $this->queueWith([
            new PendingItem('admin.content.review', 'article', 'بی‌تاریخ', '/x/1'),
            new PendingItem('admin.content.review', 'article', 'تاریخ‌دار', '/x/2', new DateTimeImmutable('2026-01-01')),
        ]);

        $items = $queue->for($this->adminWith(AdminRole::Content));

        $this->assertSame('تاریخ‌دار', $items[0]->title);
    }

    public function test_items_are_counted_by_kind(): void
    {
        $queue = $this->queueWith([
            new PendingItem('admin.content.review', 'article', 'یک', '/x/1'),
            new PendingItem('admin.content.review', 'article', 'دو', '/x/2'),
            new PendingItem('admin.content.review', 'course', 'سه', '/x/3'),
        ]);

        $this->assertSame(
            ['article' => 2, 'course' => 1],
            $queue->countsByKind($this->adminWith(AdminRole::Content)),
        );
    }

    public function test_a_user_without_an_admin_role_sees_nothing(): void
    {
        $queue = $this->queueWith([
            new PendingItem('admin.content.review', 'article', 'مقاله', '/x/1'),
        ]);

        $this->assertSame([], $queue->for(User::factory()->create()));
    }
}

/** منبع ساختگی، به‌جای یک ماژول محتوایی واقعی. */
final readonly class FakeQueueSource implements ApprovalQueueSource
{
    /** @param  list<PendingItem>  $items */
    public function __construct(private array $items) {}

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        return $this->items;
    }
}
