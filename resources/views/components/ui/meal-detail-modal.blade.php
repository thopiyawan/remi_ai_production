<x-ui.modal id="meal-detail-modal" title="รายละเอียดอาหาร">
    <div class="meal-detail">
        <div class="meal-detail__identity">
            <span class="meal-detail__icon"><i class="fa-solid fa-bowl-food" aria-hidden="true"></i></span>
            <div><strong data-meal-detail-patient>-</strong><p>HN <span data-meal-detail-hn>-</span> • รายการ #<span data-meal-detail-reference>-</span></p></div>
        </div>
        <figure class="meal-detail__media" data-meal-detail-media hidden>
            <img src="" alt="" loading="lazy" data-meal-detail-image>
        </figure>
        <dl class="meal-detail__facts">
            <div><dt>มื้ออาหาร</dt><dd data-meal-detail-meal>-</dd></div>
            <div><dt>วันที่</dt><dd data-meal-detail-date>-</dd></div>
            <div><dt>เวลา</dt><dd data-meal-detail-time>-</dd></div>
            <div><dt>พลังงาน</dt><dd data-meal-detail-energy>-</dd></div>
        </dl>
        <section class="meal-detail__section"><h3>รายการอาหาร</h3><p data-meal-detail-foods>-</p></section>
        <section class="meal-detail__section"><h3>คำแนะนำ</h3><p data-meal-detail-recommendation>-</p></section>
    </div>
    <x-slot:footer>
        <button type="button" class="ui-button meal-detail__close" data-modal-close>ปิด</button>
        <a class="ui-button ui-button--primary meal-detail__review" href="#" data-meal-detail-review>ตรวจรายการนี้</a>
    </x-slot:footer>
</x-ui.modal>
