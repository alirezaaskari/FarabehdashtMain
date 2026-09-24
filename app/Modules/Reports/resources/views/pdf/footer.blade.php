{{-- پانویس هر صفحه: شناسه رهگیری، تا برگه جدا‌شده هم قابل رهگیری باشد. --}}
<table class="footer">
    <tr>
        <td style="width: 50%;">
            @if ($document->trackingCode)
                شناسه رهگیری <span dir="ltr">{{ $document->trackingCode }}</span>
            @else
                پیش‌نمایش — صادر نشده
            @endif
        </td>
        <td style="width: 50%; text-align: left;">صفحه {PAGENO} از {nbpg}</td>
    </tr>
</table>
