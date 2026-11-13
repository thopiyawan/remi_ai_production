document.addEventListener('DOMContentLoaded', () => {
    initializeDashboardCharts();
    initializeModals();
    initializeDateRangePicker();
    initializeNutritionReview();
    initializeConversations();
    initializeDoctorMenu();
    initializeUiSelects();
    initializeInsulinAdjustment();

    document.querySelectorAll('[data-auto-submit-select]').forEach((select) => {
        select.addEventListener('change', () => select.form?.requestSubmit());
    });

    const widget = document.querySelector('[data-chat-widget]');
    if (!widget) return;

    const panel = widget.querySelector('[data-chat-panel]');
    const launcher = widget.querySelector('[data-chat-toggle]');
    const badge = widget.querySelector('[data-chat-badge]');
    const openButtons = document.querySelectorAll('[data-chat-open]');
    const closeButtons = widget.querySelectorAll('[data-chat-close], [data-chat-minimize]');
    const form = widget.querySelector('[data-chat-form]');
    const input = form.querySelector('input');
    const submitButton = form.querySelector('button[type="submit"]');
    const status = widget.querySelector('[data-chat-status]');
    const list = widget.querySelector('[data-chat-list]');
    const messages = widget.querySelector('[data-chat-messages]');
    const tabs = widget.querySelector('[data-chat-tabs]');
    const filterButtons = [...widget.querySelectorAll('[data-chat-filter]')];
    const conversationButtons = [...widget.querySelectorAll('[data-chat-conversation]')];
    const backButton = widget.querySelector('[data-chat-back]');
    const title = widget.querySelector('[data-chat-title]');
    const subtitle = widget.querySelector('[data-chat-subtitle]');
    const avatar = widget.querySelector('[data-chat-avatar]');
    let selectedConversation = null;

    const setOpen = (open) => {
        widget.classList.toggle('chat-widget--open', open);
        panel.setAttribute('aria-hidden', String(!open));
        launcher.setAttribute('aria-expanded', String(open));
        if (open) {
            badge?.remove();
        }
    };

    const setFilter = (type) => {
        filterButtons.forEach((button) => {
            const active = button.dataset.chatFilter === type;
            button.classList.toggle('is-active', active);
            if (active) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        conversationButtons.forEach((button) => {
            button.hidden = button.dataset.type !== type;
        });
    };

    const showList = () => {
        selectedConversation = null;
        list.hidden = false;
        tabs.hidden = false;
        messages.hidden = true;
        form.hidden = true;
        backButton.hidden = true;
        title.textContent = 'ข้อความ';
        subtitle.textContent = 'เลือก Direct หรือกลุ่มเพื่อเริ่มสนทนา';
        avatar.textContent = 'แชท';
    };

    const appendChatMessage = (message) => {
        const article = document.createElement('article');
        article.className = `chat-message${message.direction === 'outgoing' ? ' chat-message--mine' : ''}`;
        const meta = document.createElement('p');
        meta.className = 'chat-message__meta';
        meta.textContent = `${message.sender || 'REMI'} • ${message.time || ''}`;
        const body = document.createElement('p');
        body.textContent = message.body || '';
        article.append(meta, body);
        messages.append(article);
    };

    const openConversation = async (button) => {
        selectedConversation = {
            id: button.dataset.conversationId,
            type: button.dataset.type,
            targetId: button.dataset.targetId,
        };
        list.hidden = true;
        tabs.hidden = true;
        messages.hidden = false;
        form.hidden = true;
        backButton.hidden = false;
        title.textContent = button.dataset.title;
        subtitle.textContent = button.dataset.subtitle;
        avatar.textContent = button.dataset.initials;
        messages.replaceChildren();
        const loading = document.createElement('p');
        loading.className = 'chat-panel__date';
        loading.textContent = 'กำลังโหลดบทสนทนา…';
        messages.append(loading);

        try {
            const url = new URL(widget.dataset.threadUrl, window.location.origin);
            url.searchParams.set('conversation', selectedConversation.id);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'ไม่สามารถโหลดบทสนทนาได้');

            messages.replaceChildren();
            const conversationMessages = payload.conversation?.messages || [];
            if (conversationMessages.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'chat-panel__date';
                empty.textContent = 'ยังไม่มีข้อความ เริ่มสนทนาได้เลย';
                messages.append(empty);
            } else {
                conversationMessages.forEach(appendChatMessage);
            }
            form.hidden = false;
            status.textContent = 'ข้อความจะถูกส่งผ่าน LINE และบันทึกในประวัติ';
            messages.scrollTop = messages.scrollHeight;
            input.focus();
        } catch (error) {
            loading.textContent = error.message;
        }
    };

    launcher.addEventListener('click', () => setOpen(!widget.classList.contains('chat-widget--open')));
    openButtons.forEach((button) => button.addEventListener('click', () => setOpen(true)));
    closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
    backButton?.addEventListener('click', showList);
    filterButtons.forEach((button) => button.addEventListener('click', () => setFilter(button.dataset.chatFilter)));
    conversationButtons.forEach((button) => button.addEventListener('click', () => openConversation(button)));

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        if (!body || !selectedConversation || submitButton.disabled) return;

        submitButton.disabled = true;
        input.disabled = true;
        status.textContent = 'กำลังส่งผ่าน LINE…';

        try {
            const message = await sendLineConversationMessage(widget.dataset.sendUrl, {
                type: selectedConversation.type,
                target_id: selectedConversation.targetId,
                message: body,
            });
            messages.querySelector('.chat-panel__date')?.remove();
            appendChatMessage(message);
            input.value = '';
            status.textContent = message.delivered_count > 1
                ? `ส่งและบันทึกแล้ว • ถึงผู้รับ ${message.delivered_count} คน`
                : 'ส่งและบันทึกในประวัติแล้ว';
            messages.scrollTop = messages.scrollHeight;
        } catch (error) {
            status.textContent = error.message;
        } finally {
            submitButton.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });

    const initialFilter = conversationButtons.some((button) => button.dataset.type === 'direct') ? 'direct' : 'group';
    setFilter(initialFilter);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
});

function initializeInsulinAdjustment() {
    const form = document.querySelector('[data-insulin-adjustment-form]');
    if (!form) return;

    const modal = form.closest('[data-modal]');
    const status = modal?.querySelector('[data-insulin-form-status]');
    const statusTitle = status?.querySelector('strong');
    const statusDetail = status?.querySelector('p');
    const submitButton = modal?.querySelector(`[type="submit"][form="${form.id}"]`);
    const defaultButtonLabel = submitButton?.textContent || 'บันทึกคำสั่งยา';
    let closeTimer = null;

    const showStatus = (state, title, detail) => {
        status?.classList.remove('api-ready--success', 'api-ready--error');
        if (state) status?.classList.add(`api-ready--${state}`);
        if (statusTitle) statusTitle.textContent = title;
        if (statusDetail) statusDetail.textContent = detail;
    };

    form.addEventListener('invalid', () => {
        showStatus('error', 'ไม่สามารถบันทึกคำสั่งยาได้', 'กรุณาตรวจสอบและกรอกข้อมูลที่จำเป็นให้ครบถ้วน');
        if (submitButton) submitButton.textContent = 'ตรวจสอบข้อมูลอีกครั้ง';
    }, true);

    const resetStatus = () => {
        showStatus('', 'ตรวจสอบข้อมูลก่อนบันทึก', 'กรุณาตรวจสอบรายละเอียดคำสั่งปรับยาให้ครบถ้วน');
        if (submitButton) submitButton.textContent = defaultButtonLabel;
    };

    const resetForm = () => {
        form.reset();
        form.querySelectorAll('[data-ui-select]').forEach((select) => {
            const native = select.querySelector('[data-ui-select-native]');
            const label = select.querySelector('[data-ui-select-label]');
            const options = [...select.querySelectorAll('[data-ui-select-option]')];
            if (!native) return;

            const selectedOption = options.find((option) => option.dataset.value === native.value);
            if (label && selectedOption) label.textContent = selectedOption.textContent.trim();
            options.forEach((option) => {
                option.setAttribute('aria-selected', String(option === selectedOption));
            });
        });
        resetStatus();
        if (submitButton) submitButton.disabled = false;
    };

    form.addEventListener('input', resetStatus);
    form.addEventListener('change', resetStatus);

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        try {
            const fields = new FormData(form);
            const optionalText = (name) => String(fields.get(name) || '').trim() || null;
            const oldDose = Number(fields.get('previous_dose'));
            const newDose = Number(fields.get('current_dose'));

            if (!Number.isFinite(oldDose) || !Number.isFinite(newDose) || oldDose < 0 || newDose < 0) {
                throw new Error('Invalid insulin dose');
            }

            const payload = {
                user_id: form.dataset.patientId,
                type: fields.get('type'),
                dose_units: newDose,
                injection_period: fields.get('period'),
                injection_time: optionalText('time'),
                injection_method: fields.get('method'),
                old_dose_units: oldDose,
                new_dose_units: newDose,
                reason: fields.get('reason'),
                note: optionalText('reason_detail'),
                recommendation: optionalText('instruction'),
                recorded_at: new Date().toISOString(),
            };

            console.info('[REMI] insulin adjustment payload\n' + JSON.stringify(payload, null, 2));
            showStatus('success', 'บันทึกคำสั่งยาสำเร็จ', 'เตรียมข้อมูลคำสั่งปรับยาเรียบร้อยแล้ว');
            if (submitButton) {
                submitButton.textContent = 'บันทึกสำเร็จ';
                submitButton.disabled = true;
            }

            window.clearTimeout(closeTimer);
            closeTimer = window.setTimeout(() => {
                resetForm();
                modal?.querySelector('[data-modal-close]')?.click();
            }, 1000);
        } catch (error) {
            console.error('[REMI] unable to prepare insulin adjustment payload', error);
            showStatus('error', 'ไม่สามารถบันทึกคำสั่งยาได้', 'เกิดข้อผิดพลาดระหว่างจัดเตรียมข้อมูล กรุณาตรวจสอบข้อมูลแล้วลองอีกครั้ง');
            if (submitButton) submitButton.textContent = 'ลองบันทึกอีกครั้ง';
        }
    });
}

async function sendLineConversationMessage(url, data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    console.log('[REMI conversation API] request', {
        url,
        method: 'POST',
        payload: data,
    });
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(data),
    });
    const payload = await response.json();

    if (!response.ok) {
        const validationMessage = payload.errors
            ? Object.values(payload.errors).flat()[0]
            : null;
        throw new Error(validationMessage || payload.message || 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่');
    }

    return payload.data;
}

function initializeUiSelects() {
    const closeSelect = (select, restoreFocus = false) => {
        if (!select) return;
        select.classList.remove('is-open');
        const toggle = select.querySelector('[data-ui-select-toggle]');
        toggle?.setAttribute('aria-expanded', 'false');
        if (restoreFocus) toggle?.focus();
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-ui-select-toggle]');
        const option = event.target.closest('[data-ui-select-option]');

        document.querySelectorAll('[data-ui-select].is-open').forEach((select) => {
            if (!select.contains(event.target)) closeSelect(select);
        });

        if (toggle) {
            const select = toggle.closest('[data-ui-select]');
            const shouldOpen = !select.classList.contains('is-open');
            document.querySelectorAll('[data-ui-select].is-open').forEach((candidate) => closeSelect(candidate));
            select.classList.toggle('is-open', shouldOpen);
            toggle.setAttribute('aria-expanded', String(shouldOpen));
            if (shouldOpen) {
                const rect = toggle.getBoundingClientRect();
                select.classList.toggle('is-dropup', window.innerHeight - rect.bottom < 280 && rect.top > 280);
                (select.querySelector('[data-ui-select-option][aria-selected="true"]') || select.querySelector('[data-ui-select-option]'))?.focus();
            }
            return;
        }

        if (option) {
            const select = option.closest('[data-ui-select]');
            const native = select.querySelector('[data-ui-select-native]');
            const value = option.dataset.value;
            if (native) {
                native.value = value;
                native.dispatchEvent(new Event('change', { bubbles: true }));
            }
            select.querySelector('[data-ui-select-label]').textContent = option.textContent.trim();
            select.querySelectorAll('[data-ui-select-option]').forEach((candidate) => {
                candidate.setAttribute('aria-selected', String(candidate === option));
            });
            closeSelect(select, true);
        }
    });

    document.addEventListener('keydown', (event) => {
        const select = event.target.closest('[data-ui-select]');
        if (!select) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeSelect(select, true);
            return;
        }

        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        const options = [...select.querySelectorAll('[data-ui-select-option]')];
        const currentIndex = options.indexOf(document.activeElement);
        if (currentIndex < 0) return;
        event.preventDefault();
        const offset = event.key === 'ArrowDown' ? 1 : -1;
        options[(currentIndex + offset + options.length) % options.length]?.focus();
    });
}

function initializeModals() {
    const modals = [...document.querySelectorAll('[data-modal]')];
    if (!modals.length) return;

    let returnFocus = null;
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const close = (modal) => {
        const filter = modal.querySelector('[data-modal-filter]');
        if (filter) {
            filter.value = '';
            filter.dispatchEvent(new Event('input'));
        }
        modal.hidden = true;
        document.body.classList.toggle('has-modal-open', modals.some((candidate) => !candidate.hidden));
        returnFocus?.focus();
        returnFocus = null;
    };

    const open = (modal, trigger) => {
        returnFocus = trigger;
        modal.hidden = false;
        document.body.classList.add('has-modal-open');
        (modal.querySelector('[data-modal-filter]') || modal.querySelector('[data-modal-close]') || modal.querySelector('.remi-modal__dialog'))?.focus();
    };

    const hydrateMealDetail = (modal, trigger) => {
        if (modal.id !== 'meal-detail-modal') return;
        const fields = ['patient', 'hn', 'reference', 'meal', 'date', 'time', 'foods', 'energy', 'recommendation'];
        fields.forEach((field) => {
            const target = modal.querySelector(`[data-meal-detail-${field}]`);
            if (target) target.textContent = trigger.dataset[`detail${field.charAt(0).toUpperCase()}${field.slice(1)}`] || '-';
        });
        const modalTitle = modal.querySelector('.remi-modal__header h2');
        if (modalTitle) {
            modalTitle.textContent = trigger.dataset.detailFoods || 'รายละเอียดอาหาร';
            modalTitle.title = trigger.dataset.detailFoods || '';
        }
        const mealImage = modal.querySelector('[data-meal-detail-image]');
        const mealMedia = modal.querySelector('[data-meal-detail-media]');
        const imageUrl = trigger.dataset.detailImage || '';
        if (mealImage && mealMedia) {
            mealMedia.hidden = !imageUrl;
            mealImage.src = imageUrl;
            mealImage.alt = imageUrl
                ? `ภาพอาหาร ${trigger.dataset.detailMeal || ''} ของ ${trigger.dataset.detailPatient || 'ผู้รับบริการ'}`.trim()
                : '';
        }
        const reviewLink = modal.querySelector('[data-meal-detail-review]');
        if (reviewLink) {
            const canReview = trigger.dataset.detailReviewable === 'true' && Boolean(trigger.dataset.detailReviewUrl);
            reviewLink.hidden = !canReview;
            reviewLink.href = canReview ? trigger.dataset.detailReviewUrl : '#';
        }
    };

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => trigger.addEventListener('click', () => {
        const modal = document.getElementById(trigger.dataset.modalOpen);
        if (modal) {
            hydrateMealDetail(modal, trigger);
            open(modal, trigger);
        }
    }));

    modals.forEach((modal) => {
        modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => close(modal)));
        const filter = modal.querySelector('[data-modal-filter]');
        if (!filter) return;
        const status = modal.querySelector('[data-modal-filter-status]');
        const items = [...modal.querySelectorAll('.action-item, tbody tr, .remi-modal-list > li, .health-record-list--modal > li, .patient-appointments--modal > li')];
        const pagination = modal.querySelector('[data-modal-pagination]');
        const pageSize = Math.max(1, Number(pagination?.dataset.pageSize) || items.length || 1);
        const summary = pagination?.querySelector('[data-modal-page-summary]');
        const numbers = pagination?.querySelector('[data-modal-page-numbers]');
        const previous = pagination?.querySelector('[data-modal-page-prev]');
        const next = pagination?.querySelector('[data-modal-page-next]');
        let currentPage = 1;

        const renderPageNumbers = (pageCount) => {
            if (!numbers) return;
            numbers.replaceChildren();
            const visiblePages = [...new Set([1, currentPage - 1, currentPage, currentPage + 1, pageCount])]
                .filter((page) => page >= 1 && page <= pageCount)
                .sort((a, b) => a - b);

            visiblePages.forEach((page, index) => {
                if (index > 0 && page - visiblePages[index - 1] > 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'remi-modal__page-ellipsis';
                    ellipsis.textContent = '…';
                    numbers.append(ellipsis);
                }
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = page;
                button.classList.toggle('is-current', page === currentPage);
                if (page === currentPage) button.setAttribute('aria-current', 'page');
                button.addEventListener('click', () => {
                    currentPage = page;
                    render();
                });
                numbers.append(button);
            });
        };

        const render = () => {
            const query = filter.value.trim().toLocaleLowerCase('th');
            const matchedItems = items.filter((item) => !query || item.textContent.toLocaleLowerCase('th').includes(query));
            const pageCount = Math.max(1, Math.ceil(matchedItems.length / pageSize));
            currentPage = Math.min(currentPage, pageCount);
            const firstIndex = (currentPage - 1) * pageSize;
            const pageItems = new Set(matchedItems.slice(firstIndex, firstIndex + pageSize));

            items.forEach((item) => { item.hidden = !pageItems.has(item); });
            status.textContent = query ? `พบ ${matchedItems.length} รายการ` : `${items.length} รายการ`;

            if (pagination) {
                const firstNumber = matchedItems.length ? firstIndex + 1 : 0;
                const lastNumber = Math.min(firstIndex + pageSize, matchedItems.length);
                summary.textContent = `แสดง ${firstNumber}–${lastNumber} จาก ${matchedItems.length} รายการ`;
                previous.disabled = currentPage === 1;
                next.disabled = currentPage === pageCount || matchedItems.length === 0;
                renderPageNumbers(pageCount);
            }
        };

        previous?.addEventListener('click', () => {
            if (currentPage <= 1) return;
            currentPage -= 1;
            render();
        });
        next?.addEventListener('click', () => {
            const query = filter.value.trim().toLocaleLowerCase('th');
            const matchedCount = items.filter((item) => !query || item.textContent.toLocaleLowerCase('th').includes(query)).length;
            if (currentPage >= Math.max(1, Math.ceil(matchedCount / pageSize))) return;
            currentPage += 1;
            render();
        });
        filter.addEventListener('input', () => {
            currentPage = 1;
            render();
        });
        render();
    });

    document.addEventListener('keydown', (event) => {
        const modal = modals.find((candidate) => !candidate.hidden);
        if (!modal) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            close(modal);
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll(focusableSelector)];
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
}

function initializeDoctorMenu() {
    const account = document.querySelector('[data-doctor-menu]');
    const menu = account?.querySelector('[data-doctor-menu-panel]');
    const toggle = account?.querySelector('[data-doctor-menu-toggle]');
    const qrOpen = account?.querySelector('[data-doctor-qr-open]');
    const modal = document.querySelector('[data-doctor-qr-modal]');
    const modalDialog = modal?.querySelector('.doctor-qr-modal__dialog');
    const closeButtons = modal?.querySelectorAll('[data-doctor-qr-close]') || [];

    if (!account || !menu || !toggle) return;

    const setMenuOpen = (open) => {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        account.classList.toggle('is-open', open);
        if (open) qrOpen?.focus();
    };

    const setModalOpen = (open) => {
        if (!modal) return;
        modal.hidden = !open;
        document.body.classList.toggle('has-modal-open', open);
        if (open) {
            modalDialog?.querySelector('[data-doctor-qr-close]')?.focus();
        } else {
            toggle.focus();
        }
    };

    toggle.addEventListener('click', () => setMenuOpen(menu.hidden));
    qrOpen?.addEventListener('click', () => {
        setMenuOpen(false);
        setModalOpen(true);
    });
    closeButtons.forEach((button) => button.addEventListener('click', () => setModalOpen(false)));

    document.addEventListener('click', (event) => {
        if (!account.contains(event.target)) setMenuOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (modal && !modal.hidden) {
            setModalOpen(false);
            return;
        }
        if (!menu.hidden) {
            setMenuOpen(false);
            toggle.focus();
        }
    });
}

function initializeConversations() {
    const page = document.querySelector('[data-conversations-page]');
    if (!page) return;

    const items = [...page.querySelectorAll('[data-conversation-item]')];
    const filters = [...page.querySelectorAll('[data-conversation-filter]')];
    const listSearch = page.querySelector('[data-conversation-search]');
    const globalSearch = page.querySelector('[data-conversation-global-search]');
    const messageSearch = page.querySelector('[data-message-search]');
    const messages = [...page.querySelectorAll('[data-message]')];
    const form = page.querySelector('[data-conversation-form]');
    let activeFilter = 'all';

    const applyConversationFilter = () => {
        const query = (listSearch?.value || globalSearch?.value || '').trim().toLocaleLowerCase('th');
        items.forEach((item) => {
            const matchesQuery = !query || item.dataset.searchText.toLocaleLowerCase('th').includes(query);
            const matchesFilter = activeFilter === 'all'
                || (activeFilter === 'unread' && Number(item.dataset.unread) > 0)
                || item.dataset.type === activeFilter;
            item.hidden = !(matchesQuery && matchesFilter);
        });
    };

    filters.forEach((filter) => filter.addEventListener('click', () => {
        activeFilter = filter.dataset.conversationFilter;
        filters.forEach((candidate) => candidate.classList.toggle('is-active', candidate === filter));
        applyConversationFilter();
    }));
    listSearch?.addEventListener('input', applyConversationFilter);
    globalSearch?.addEventListener('input', () => {
        if (listSearch) listSearch.value = globalSearch.value;
        applyConversationFilter();
    });
    messageSearch?.addEventListener('input', () => {
        const query = messageSearch.value.trim().toLocaleLowerCase('th');
        messages.forEach((message) => { message.hidden = query !== '' && !message.dataset.searchText.toLocaleLowerCase('th').includes(query); });
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = form.querySelector('input[name="message"]');
        const button = form.querySelector('button[type="submit"]');
        const status = form.querySelector('[data-conversation-status]');
        const body = input?.value.trim();
        if (!body || button.disabled) return;

        button.disabled = true;
        input.disabled = true;
        status.textContent = 'กำลังส่งผ่าน LINE…';

        try {
            const message = await sendLineConversationMessage(page.dataset.sendUrl, {
                type: form.dataset.conversationType,
                target_id: form.dataset.conversationTarget,
                message: body,
            });
            const article = document.createElement('article');
            article.className = 'conversation-message conversation-message--outgoing conversation-message--info';
            article.dataset.message = '';
            article.dataset.searchText = `${message.sender} ${message.body}`;
            const meta = document.createElement('p');
            meta.className = 'conversation-message__meta';
            meta.textContent = `${message.sender} • ${message.time}`;
            const text = document.createElement('p');
            text.textContent = message.body;
            article.append(meta, text);
            page.querySelector('[data-conversation-messages]')?.append(article);
            input.value = '';
            status.textContent = message.delivered_count > 1
                ? `ส่งและบันทึกแล้ว • ถึงผู้รับ ${message.delivered_count} คน`
                : 'ส่งและบันทึกในประวัติแล้ว';
            article.scrollIntoView({ behavior: 'smooth', block: 'end' });
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });
}

function initializeNutritionReview() {
    const review = document.querySelector('[data-nutrition-review]');
    if (!review) return;

    const queueItems = [...review.querySelectorAll('[data-review-item]')];
    const search = review.querySelector('[data-review-search]');
    const foodRows = review.querySelector('[data-reviewed-foods]');
    const feedback = review.querySelector('[data-review-feedback]');
    const draftNotice = review.querySelector('[data-review-draft-notice]');
    const form = review.querySelector('[data-food-review-form]');
    const saveButton = form?.querySelector('[data-review-save]');
    const defaultSaveLabel = saveButton?.textContent || 'บันทึกผลการตรวจสอบ';
    let saveResetTimer;

    const showReviewFeedback = (state, message) => {
        if (!feedback) return;
        feedback.classList.remove('is-success', 'is-error');
        if (state) feedback.classList.add(`is-${state}`);
        feedback.textContent = message;
    };

    const markReviewAsChanged = () => {
        window.clearTimeout(saveResetTimer);
        showReviewFeedback('', '');
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = defaultSaveLabel;
        }
    };

    const updateDraftState = () => {
        const draftCount = foodRows?.querySelectorAll('[data-draft-food-row]').length || 0;
        if (draftNotice) draftNotice.hidden = draftCount === 0;
    };

    queueItems.forEach((item) => item.addEventListener('click', () => {
        queueItems.forEach((candidate) => candidate.classList.toggle('is-active', candidate === item));
    }));

    search?.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase('th');
        queueItems.forEach((item) => {
            item.hidden = query !== '' && !item.dataset.searchText.toLocaleLowerCase('th').includes(query);
        });
    });

    review.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-reviewed-food]');
        if (removeButton) {
            removeButton.closest('tr')?.remove();
            updateDraftState();
            markReviewAsChanged();
        }

        if (event.target.closest('[data-add-reviewed-food]')) {
            const row = document.createElement('tr');
            row.dataset.draftFoodRow = '';
            row.dataset.reviewedFoodRow = '';
            row.className = 'review-food-row--draft';
            row.innerHTML = '<td><input name="food_name" aria-label="ชื่อรายการอาหารใหม่" placeholder="ชื่ออาหาร" required></td><td><div class="review-amount-input"><input name="amount_value" aria-label="ค่าปริมาณรายการอาหารใหม่" type="number" min="0.01" step="0.01" inputmode="decimal" placeholder="0.5" required><div class="ui-select" data-ui-select><select class="ui-select__native sr-only" name="amount_unit" tabindex="-1" aria-hidden="true" data-ui-select-native required><option value="">เลือกหน่วย</option><option value="egg">ฟอง</option><option value="piece">ชิ้น</option><option value="cup">ถ้วย</option><option value="plate">จาน</option><option value="ladle">ทัพพี</option><option value="tablespoon">ช้อนโต๊ะ</option><option value="teaspoon">ช้อนชา</option><option value="glass">แก้ว</option><option value="gram">กรัม</option><option value="milliliter">มล.</option></select><button class="ui-select__toggle" type="button" data-ui-select-toggle aria-haspopup="listbox" aria-expanded="false"><span data-ui-select-label>เลือกหน่วย</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button><div class="ui-select__menu" role="listbox" aria-label="หน่วยปริมาณ"><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="egg">ฟอง</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="piece">ชิ้น</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="cup">ถ้วย</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="plate">จาน</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="ladle">ทัพพี</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="tablespoon">ช้อนโต๊ะ</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="teaspoon">ช้อนชา</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="glass">แก้ว</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="gram">กรัม</button><button type="button" role="option" aria-selected="false" data-ui-select-option data-value="milliliter">มล.</button></div></div></div></td><td><label class="review-energy-input"><input name="energy_kcal" aria-label="พลังงานรายการอาหารใหม่" type="number" min="0" step="1" placeholder="0"><span>kcal</span></label></td><td><strong class="text-warning">เพิ่มโดยแพทย์</strong></td><td><button type="button" data-remove-reviewed-food aria-label="ลบรายการอาหารฉบับร่าง">ลบ</button></td>';
            foodRows?.append(row);
            row.querySelector('input')?.focus();
            updateDraftState();
            markReviewAsChanged();
        }

    });

    form?.addEventListener('invalid', () => {
        showReviewFeedback('error', 'ไม่สามารถบันทึกได้ กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน');
        if (saveButton) saveButton.textContent = 'ตรวจสอบข้อมูลอีกครั้ง';
    }, true);

    form?.addEventListener('input', markReviewAsChanged);
    form?.addEventListener('change', markReviewAsChanged);

    form?.addEventListener('submit', (event) => {
        event.preventDefault();

        try {
            const fields = new FormData(form);
            const numberOrNull = (value) => {
                if (value === null || value === undefined || value === '') return null;
                const number = Number(value);
                return Number.isFinite(number) ? number : null;
            };
            const parseAmount = (value) => {
                const match = String(value || '').trim().match(/^([0-9]+(?:\.[0-9]+)?)\s*(.*)$/);
                return {
                    portion: match ? Number(match[1]) : null,
                    unit: match?.[2]?.trim() || null,
                };
            };
            const foods = [...foodRows.querySelectorAll('[data-reviewed-food-row]')].map((row) => {
                const draftPortion = row.querySelector('[name="amount_value"]')?.value;
                const existingAmount = parseAmount(row.querySelector('[name="food_amount"]')?.value);
                const energyInput = row.querySelector('[name="energy_kcal"]')?.value;

                return {
                    id: numberOrNull(row.dataset.foodId),
                    name: row.querySelector('[name="food_name"]')?.value.trim(),
                    portion: draftPortion !== undefined ? numberOrNull(draftPortion) : existingAmount.portion,
                    unit: row.querySelector('[name="amount_unit"]')?.value || existingAmount.unit,
                    weight_g: numberOrNull(row.dataset.weightG),
                    energy_kcal: numberOrNull(energyInput ?? row.dataset.energyKcal),
                    nutrients: {
                        carbohydrate_g: numberOrNull(row.dataset.carbohydrateG),
                        protein_g: numberOrNull(row.dataset.proteinG),
                        fat_g: numberOrNull(row.dataset.fatG),
                        fiber_g: numberOrNull(row.dataset.fiberG),
                        sugar_g: numberOrNull(row.dataset.sugarG),
                        sodium_mg: numberOrNull(row.dataset.sodiumMg),
                    },
                };
            });

            if (!foods.length || foods.some((food) => !food.name || food.portion === null || !food.unit)) {
                throw new Error('Invalid reviewed food items');
            }

            const mealId = Number(form.dataset.mealId);
            const payload = {
                meal_id: Number.isFinite(mealId) ? mealId : form.dataset.mealId,
                dish_name: String(fields.get('dish_name') || '').trim(),
                meal_type: fields.get('meal_type'),
                total_weight: fields.get('total_weight'),
                foods,
                recommendation: String(fields.get('recommendation') || '').trim() || null,
                note: String(fields.get('note') || '').trim() || null,
                review_status: 'reviewed',
                reviewed_at: new Date().toISOString(),
            };

            console.info('[REMI] food review payload\n' + JSON.stringify(payload, null, 2));
            showReviewFeedback('success', 'บันทึกผลการตรวจสอบสำเร็จ');
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'บันทึกสำเร็จ';
            }
            saveResetTimer = window.setTimeout(() => {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = defaultSaveLabel;
                }
            }, 1000);
        } catch (error) {
            console.error('[REMI] unable to prepare food review payload', error);
            showReviewFeedback('error', 'ไม่สามารถบันทึกผลการตรวจสอบได้ กรุณาตรวจสอบข้อมูลแล้วลองอีกครั้ง');
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.textContent = 'ลองบันทึกอีกครั้ง';
            }
        }
    });

}

function initializeDateRangePicker() {
    if (typeof flatpickr === 'undefined') return;
    document.querySelectorAll('[data-date-range]').forEach((input) => {
        const form = input.closest('form');
        const fromInput = form.querySelector('[data-date-range-from], [name="date_from"], [name="start_date"]');
        const toInput = form.querySelector('[data-date-range-to], [name="date_to"], [name="end_date"]');
        const defaultDate = [input.dataset.dateFrom, input.dataset.dateTo].filter(Boolean);

        flatpickr(input, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            locale: { rangeSeparator: ' ถึง ' },
            defaultDate,
            maxDate: 'today',
            disableMobile: true,
            onClose(selectedDates, dateString, instance) {
                if (selectedDates.length !== 2) return;
                fromInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                toInput.value = instance.formatDate(selectedDates[1], 'Y-m-d');
                if (input.hasAttribute('data-auto-submit')) form.submit();
            },
        });
    });
}

function initializeDashboardCharts() {
    if (typeof Chart === 'undefined') return;

    const styles = getComputedStyle(document.documentElement);
    const color = (token, fallback) => styles.getPropertyValue(token).trim() || fallback;
    const fontFamily = styles.getPropertyValue('--remi-font-family').trim() || 'sans-serif';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = color('--remi-color-text-secondary', '#75666b');

    const externalChartTooltip = ({ chart, tooltip }) => {
        let tooltipElement = chart.$remiTooltip;
        if (!tooltipElement) {
            tooltipElement = document.createElement('div');
            tooltipElement.className = 'chart-html-tooltip';
            tooltipElement.setAttribute('role', 'tooltip');
            document.body.append(tooltipElement);
            chart.$remiTooltip = tooltipElement;
        }

        if (!tooltip || tooltip.opacity === 0) {
            tooltipElement.hidden = true;
            return;
        }

        const lines = [
            ...(tooltip.title || []),
            ...(tooltip.body || []).flatMap((item) => item.lines || []),
        ].filter(Boolean);
        tooltipElement.textContent = lines.join(' • ');
        tooltipElement.hidden = false;

        const canvasRect = chart.canvas.getBoundingClientRect();
        const tooltipRect = tooltipElement.getBoundingClientRect();
        const viewportPadding = 10;
        let left = canvasRect.left + tooltip.caretX - (tooltipRect.width / 2);
        let top = canvasRect.top + tooltip.caretY + 12;

        left = Math.max(viewportPadding, Math.min(left, window.innerWidth - tooltipRect.width - viewportPadding));
        if (top + tooltipRect.height > window.innerHeight - viewportPadding) {
            top = canvasRect.top + tooltip.caretY - tooltipRect.height - 12;
        }

        tooltipElement.style.left = `${left}px`;
        tooltipElement.style.top = `${Math.max(viewportPadding, top)}px`;
    };

    const thaiWeekdayNames = {
        'อา.': 'อาทิตย์',
        'จ.': 'จันทร์',
        'อ.': 'อังคาร',
        'พ.': 'พุธ',
        'พฤ.': 'พฤหัสบดี',
        'ศ.': 'ศุกร์',
        'ส.': 'เสาร์',
    };

    const valueLabels = {
        id: 'valueLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            ctx.fillStyle = color('--remi-color-text-primary', '#33262b');
            ctx.font = `500 14px ${fontFamily}`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                ctx.fillText(chart.data.datasets[0].data[index], bar.x, bar.y - 7);
            });
            ctx.restore();
        },
    };

    const activityCanvas = document.querySelector('[data-chart="activity"]');
    if (activityCanvas) {
        new Chart(activityCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(activityCanvas.dataset.labels),
                datasets: [{
                    label: 'ผู้รับบริการ',
                    data: JSON.parse(activityCanvas.dataset.values),
                    backgroundColor: color('--remi-color-action-primary', '#d94578'),
                    borderRadius: 9,
                    borderSkipped: false,
                    barThickness: 18,
                }],
            },
            plugins: [valueLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 20 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: false,
                        external: externalChartTooltip,
                        displayColors: false,
                        callbacks: {
                            title: () => '',
                            label: (context) => `${thaiWeekdayNames[context.label] ?? context.label} : ${context.parsed.y} คน`,
                        },
                    },
                },
                scales: {
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: { font: { size: 14, weight: 500 } },
                    },
                    y: {
                        display: false,
                        beginAtZero: true,
                        min: 0,
                        suggestedMax: 85,
                    },
                },
            },
        });
    }

    const centerText = {
        id: 'centerText',
        afterDatasetsDraw(chart) {
            const { ctx, chartArea } = chart;
            const centerX = (chartArea.left + chartArea.right) / 2;
            const centerY = (chartArea.top + chartArea.bottom) / 2;
            ctx.save();
            ctx.fillStyle = color('--remi-color-text-primary', '#33262b');
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = `600 14px ${fontFamily}`;
            ctx.fillText(Number(chart.canvas.dataset.total).toLocaleString('th-TH'), centerX, centerY - 9);
            ctx.font = `400 14px ${fontFamily}`;
            ctx.fillText('ราย', centerX, centerY + 11);
            ctx.restore();
        },
    };

    const weightCanvas = document.querySelector('[data-chart="weight"]');
    if (weightCanvas) {
        new Chart(weightCanvas, {
            type: 'doughnut',
            data: {
                labels: JSON.parse(weightCanvas.dataset.labels),
                datasets: [{
                    data: JSON.parse(weightCanvas.dataset.values),
                    backgroundColor: JSON.parse(weightCanvas.dataset.colors),
                    borderWidth: 0,
                    hoverOffset: 2,
                }],
            },
            plugins: [centerText],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: false,
                        external: externalChartTooltip,
                        displayColors: false,
                        callbacks: {
                            title: () => '',
                            label: (context) => `${context.label} ${context.parsed}%`,
                        },
                    },
                },
            },
        });
    }

    const patientWeightCanvas = document.querySelector('[data-chart="patient-weight"]');
    if (patientWeightCanvas) {
        const patientWeightValues = JSON.parse(patientWeightCanvas.dataset.values);
        new Chart(patientWeightCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(patientWeightCanvas.dataset.labels),
                datasets: [{
                    label: 'น้ำหนัก (กก.)',
                    data: patientWeightValues,
                    backgroundColor: color('--remi-color-action-primary', '#d94578'),
                    borderRadius: 999,
                    borderSkipped: false,
                    barThickness: 18,
                    clip: false,
                }],
            },
            plugins: [{
                id: 'patientWeightLabels',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    ctx.save();
                    ctx.fillStyle = color('--remi-color-action-primary', '#d94578');
                    ctx.font = `500 14px ${fontFamily}`;
                    ctx.textAlign = 'center';
                    chart.getDatasetMeta(0).data.forEach((bar, index) => ctx.fillText(patientWeightValues[index], bar.x, bar.y - 8));
                    ctx.restore();
                },
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 18 } },
                plugins: { legend: { display: false }, tooltip: { displayColors: false, callbacks: { label: (context) => `${patientWeightValues[context.dataIndex]} กก.` } } },
                scales: {
                    x: { border: { display: false }, grid: { display: false }, ticks: { font: { size: 14 } } },
                    y: { display: false, beginAtZero: true, min: 0, grace: '10%' },
                },
            },
        });
    }

    const patientGlucoseCanvas = document.querySelector('[data-chart="patient-glucose"]');
    if (patientGlucoseCanvas) {
        const glucoseDates = JSON.parse(patientGlucoseCanvas.dataset.labels);
        const glucoseReadings = JSON.parse(patientGlucoseCanvas.dataset.readings);
        const glucoseValues = glucoseReadings.map((reading) => Number(reading.value)).filter(Number.isFinite);
        const glucoseMin = Math.floor((Math.min(...glucoseValues, 95) - 10) / 10) * 10;
        const glucoseMax = Math.ceil((Math.max(...glucoseValues, 140) + 10) / 10) * 10;
        const glucoseHasSingleDate = glucoseDates.length === 1;
        const glucoseXAxisMin = glucoseHasSingleDate ? -0.5 : 0;
        const glucoseXAxisMax = glucoseHasSingleDate ? 0.5 : Math.max(glucoseDates.length - 1, 1);
        new Chart(patientGlucoseCanvas, {
            type: 'line',
            data: {
                datasets: [{
                    data: glucoseReadings.map((reading) => ({
                        x: reading.x,
                        y: reading.value,
                        type: reading.type,
                        status: reading.status,
                        meal: reading.meal,
                        period_label: reading.period_label,
                    })),
                    borderColor: '#cfc6ca',
                    borderWidth: 2,
                    tension: 0,
                    fill: false,
                    pointRadius: 8,
                    pointHoverRadius: 9,
                    pointBorderWidth: 2,
                    pointBorderColor: '#fff',
                    pointBackgroundColor: (context) => context.raw?.status === 'high' ? '#d94578' : '#34aa80',
                    pointStyle: (context) => ({ before_meal: 'triangle', fasting: 'triangle', bedtime: 'rectRounded' }[context.raw?.type] || 'circle'),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                layout: { padding: { top: 16, right: 16, bottom: 0, left: 0 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            title: (items) => glucoseDates[Math.round(items[0]?.parsed?.x)] || '',
                            label: (context) => `${[context.raw?.meal, context.raw?.period_label].filter(Boolean).join(' • ')}: ${context.parsed.y} mg/dL`,
                        },
                    },
                    annotation: {
                        annotations: {
                            targetRange: {
                                type: 'box',
                                yMin: 95,
                                yMax: 140,
                                borderWidth: 0,
                                backgroundColor: 'rgba(52, 170, 128, .055)',
                                drawTime: 'beforeDatasetsDraw',
                            },
                            afterMealLimit: {
                                type: 'line',
                                yMin: 140,
                                yMax: 140,
                                borderColor: color('--remi-color-action-primary', '#d94578'),
                                borderWidth: 1.5,
                                borderDash: [6, 5],
                                label: { display: true, content: '140 หลังอาหาร 1 ชม.', position: 'start', backgroundColor: 'transparent', color: color('--remi-color-action-primary', '#d94578'), font: { family: fontFamily, size: 14, weight: '400' }, yAdjust: -12 },
                            },
                            fastingLimit: {
                                type: 'line',
                                yMin: 95,
                                yMax: 95,
                                borderColor: color('--remi-color-action-primary', '#d94578'),
                                borderWidth: 1.5,
                                borderDash: [6, 5],
                                label: { display: true, content: '95 ก่อนอาหาร', position: 'start', backgroundColor: 'transparent', color: color('--remi-color-action-primary', '#d94578'), font: { family: fontFamily, size: 14, weight: '400' }, yAdjust: -12 },
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        type: 'linear', min: glucoseXAxisMin, max: glucoseXAxisMax,
                        border: { color: '#75666b', width: 1.5 },
                        grid: { color: '#eadfe3', lineWidth: 1, drawTicks: false },
                        ticks: { stepSize: 1, padding: 14, color: '#75666b', font: { size: 14 }, callback: (value) => Number.isInteger(value) ? (glucoseDates[value] || '') : '' },
                    },
                    y: {
                        min: glucoseMin, max: glucoseMax,
                        border: { color: '#75666b', width: 1.5 },
                        grid: { color: '#eadfe3', lineWidth: 1, drawTicks: false },
                        ticks: { stepSize: 10, padding: 12, color: '#75666b', font: { size: 14 }, callback: (value) => (value % 20 === 0 || [95, 140, glucoseMin, glucoseMax].includes(value)) ? value : '' },
                    },
                },
            },
        });
    }

    const glucoseSummaryCanvas = document.querySelector('[data-chart="glucose-summary"]');
    if (glucoseSummaryCanvas) {
        const glucoseLow = JSON.parse(glucoseSummaryCanvas.dataset.low);
        const glucoseNormal = JSON.parse(glucoseSummaryCanvas.dataset.normal);
        const glucoseHigh = JSON.parse(glucoseSummaryCanvas.dataset.high);
        const glucoseNoData = JSON.parse(glucoseSummaryCanvas.dataset.noData);
        const glucoseSummaryStacks = [glucoseLow, glucoseNormal, glucoseHigh, glucoseNoData];
        const nonZeroSegments = (values) => values.map((value) => Number(value) > 0 ? Number(value) : null);
        const glucoseSummaryRadius = (datasetIndex, dataIndex) => {
            const visibleDatasets = glucoseSummaryStacks
                .map((values, index) => Number(values[dataIndex]) > 0 ? index : null)
                .filter((index) => index !== null);
            const isBottom = datasetIndex === visibleDatasets[0];
            const isTop = datasetIndex === visibleDatasets[visibleDatasets.length - 1];

            return {
                topLeft: isTop ? 8 : 0,
                topRight: isTop ? 8 : 0,
                bottomLeft: isBottom ? 8 : 0,
                bottomRight: isBottom ? 8 : 0,
            };
        };
        const glucoseSummaryLabels = {
            id: 'glucoseSummaryLabels',
            beforeDatasetsDraw(chart) {
                const { ctx, chartArea } = chart;
                ctx.save();
                ctx.beginPath();
                ctx.lineWidth = 1;
                ctx.strokeStyle = color('--remi-color-border-default', '#ebdde2');
                ctx.moveTo(chartArea.left, chartArea.bottom);
                ctx.lineTo(chartArea.right, chartArea.bottom);
                ctx.stroke();
                ctx.restore();
            },
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.font = `600 14px ${fontFamily}`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    chart.getDatasetMeta(datasetIndex).data.forEach((bar, index) => {
                        const value = dataset.data[index];
                        if (!value) return;
                        ctx.fillStyle = dataset.noData ? color('--remi-color-text-muted', '#9e8f94') : '#fff';
                        ctx.fillText(dataset.noData ? 'ไม่มีข้อมูล' : `${value}%`, bar.x, (bar.y + bar.base) / 2);
                    });
                });
                ctx.restore();
            },
        };
        new Chart(glucoseSummaryCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(glucoseSummaryCanvas.dataset.labels),
                datasets: [
                    { label: 'ต่ำ', data: nonZeroSegments(glucoseLow), backgroundColor: '#e9aa32', borderRadius: (context) => glucoseSummaryRadius(0, context.dataIndex), borderSkipped: false },
                    { label: 'ปกติ', data: nonZeroSegments(glucoseNormal), backgroundColor: '#34aa80', borderRadius: (context) => glucoseSummaryRadius(1, context.dataIndex), borderSkipped: false },
                    { label: 'สูง', data: nonZeroSegments(glucoseHigh), backgroundColor: '#d83b57', borderRadius: (context) => glucoseSummaryRadius(2, context.dataIndex), borderSkipped: false },
                    { label: 'ไม่มีข้อมูล', data: nonZeroSegments(glucoseNoData), backgroundColor: '#f2edef', borderRadius: (context) => glucoseSummaryRadius(3, context.dataIndex), borderSkipped: false, noData: true },
                ],
            },
            plugins: [glucoseSummaryLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { position: 'bottom', align: 'start', labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, padding: 26, color: '#51484c', font: { size: 14 }, filter: (item) => item.text !== 'ไม่มีข้อมูล' } }, tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.parsed.y}%` } } },
                scales: {
                    x: { stacked: true, position: 'top', border: { display: false }, grid: { display: false }, ticks: { padding: 8, font: { size: 14 } } },
                    y: { stacked: true, display: false, beginAtZero: true, min: 0, max: 132 },
                },
                layout: { padding: { top: 0, right: 10, bottom: 0, left: 10 } },
                datasets: { bar: { barThickness: 50, categoryPercentage: .5 } },
            },
        });
    }

    const healthWeightCanvas = document.querySelector('[data-chart="health-weight"]');
    if (healthWeightCanvas) {
        new Chart(healthWeightCanvas, {
            type: 'line',
            data: {
                labels: JSON.parse(healthWeightCanvas.dataset.labels),
                datasets: [
                    { label: 'ขอบล่าง', data: JSON.parse(healthWeightCanvas.dataset.min), borderColor: 'transparent', pointRadius: 0, fill: false },
                    { label: 'ช่วงที่แนะนำ', data: JSON.parse(healthWeightCanvas.dataset.max), borderColor: 'transparent', backgroundColor: 'rgba(52, 170, 128, .13)', pointRadius: 0, fill: '-1' },
                    { label: 'น้ำหนัก', data: JSON.parse(healthWeightCanvas.dataset.values), borderColor: color('--remi-color-action-primary', '#d94578'), backgroundColor: color('--remi-color-action-primary', '#d94578'), borderWidth: 3, pointRadius: 5, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderWidth: 3, tension: .25 },
                ],
            },
            options: healthChartOptions(true),
        });
    }

    const healthExerciseCanvas = document.querySelector('[data-chart="health-exercise"]');
    if (healthExerciseCanvas) {
        const exerciseValues = JSON.parse(healthExerciseCanvas.dataset.values);
        const nonZeroExercise = (values) => values.map((value) => Number(value) > 0 ? value : null);
        const exerciseOptions = healthChartOptions(true, true);
        exerciseOptions.scales.x.stacked = true;
        exerciseOptions.scales.y.stacked = true;
        exerciseOptions.plugins.tooltip = {
            displayColors: true,
            callbacks: { label: (context) => `${context.dataset.label}: ${context.parsed.y ?? 0} นาที` },
        };
        const exerciseStackRadius = (context) => {
            const datasets = context.chart.data.datasets;
            const visibleDatasets = datasets
                .map((candidate, index) => candidate.data[context.dataIndex] === null ? null : index)
                .filter((index) => index !== null);
            const isBottom = context.datasetIndex === visibleDatasets[0];
            const isTop = context.datasetIndex === visibleDatasets[visibleDatasets.length - 1];

            return {
                topLeft: isTop ? 9 : 0,
                topRight: isTop ? 9 : 0,
                bottomLeft: isBottom ? 9 : 0,
                bottomRight: isBottom ? 9 : 0,
            };
        };
        new Chart(healthExerciseCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(healthExerciseCanvas.dataset.labels),
                datasets: [
                    { label: 'เบา', data: nonZeroExercise(JSON.parse(healthExerciseCanvas.dataset.light)), backgroundColor: '#4e79e8', borderRadius: exerciseStackRadius, borderSkipped: false, barThickness: 18 },
                    { label: 'ปานกลาง', data: nonZeroExercise(JSON.parse(healthExerciseCanvas.dataset.medium)), backgroundColor: '#34aa80', borderRadius: exerciseStackRadius, borderSkipped: false, barThickness: 18 },
                    { label: 'ค่อนข้างหนัก', data: nonZeroExercise(JSON.parse(healthExerciseCanvas.dataset.high)), backgroundColor: '#df476d', borderRadius: exerciseStackRadius, borderSkipped: false, barThickness: 18 },
                    { label: 'ไม่ระบุ', data: nonZeroExercise(JSON.parse(healthExerciseCanvas.dataset.unknown)), backgroundColor: '#a3979b', borderRadius: exerciseStackRadius, borderSkipped: false, barThickness: 18 },
                ],
            },
            plugins: [{ id: 'exerciseLabels', afterDatasetsDraw(chart) { const { ctx } = chart; ctx.save(); ctx.fillStyle = color('--remi-color-text-secondary', '#75666b'); ctx.font = `500 14px ${fontFamily}`; ctx.textAlign = 'center'; chart.getDatasetMeta(0).data.forEach((bar, index) => ctx.fillText(exerciseValues[index], bar.x, chart.scales.y.getPixelForValue(exerciseValues[index]) - 8)); ctx.restore(); } }],
            options: exerciseOptions,
        });
    }

    const healthFetalCanvas = document.querySelector('[data-chart="health-fetal"]');
    if (healthFetalCanvas) {
        new Chart(healthFetalCanvas, {
            type: 'line',
            data: { labels: JSON.parse(healthFetalCanvas.dataset.labels), datasets: [{ data: JSON.parse(healthFetalCanvas.dataset.values), borderColor: color('--remi-color-action-primary', '#d94578'), backgroundColor: color('--remi-color-action-primary', '#d94578'), borderWidth: 3, pointRadius: 5, pointHoverRadius: 6, tension: 0 }] },
            options: healthChartOptions(true),
        });
    }

    const nutritionEnergyCanvas = document.querySelector('[data-chart="nutrition-energy"]');
    if (nutritionEnergyCanvas) {
        const energyTarget = Number(nutritionEnergyCanvas.dataset.target);
        const energyLabels = JSON.parse(nutritionEnergyCanvas.dataset.labels);
        const energyCarbs = JSON.parse(nutritionEnergyCanvas.dataset.carbs);
        const energyProtein = JSON.parse(nutritionEnergyCanvas.dataset.protein);
        const energyFat = JSON.parse(nutritionEnergyCanvas.dataset.fat);
        const dailyEnergyTotals = energyLabels.map((_, index) => (
            Number(energyCarbs[index] || 0)
            + Number(energyProtein[index] || 0)
            + Number(energyFat[index] || 0)
        ));
        const energyScaleMax = Math.ceil(Math.max(energyTarget, ...dailyEnergyTotals, 1) * 1.1 / 100) * 100;
        const energyTargetGuide = {
            id: 'energyTargetGuide',
            beforeDatasetsDraw(chart) {
                if (energyTarget <= 0) return;
                const { ctx, chartArea, scales } = chart;
                const y = scales.y.getPixelForValue(energyTarget);
                if (y < chartArea.top || y > chartArea.bottom) return;
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([7, 6]);
                ctx.lineWidth = 2;
                ctx.strokeStyle = color('--remi-color-action-primary', '#d94578');
                ctx.moveTo(chartArea.left, y);
                ctx.lineTo(chartArea.right, y);
                ctx.stroke();
                ctx.restore();
            },
        };
        new Chart(nutritionEnergyCanvas, {
            type: 'bar',
            data: {
                labels: energyLabels,
                datasets: [
                    { label: 'คาร์โบไฮเดรต', data: energyCarbs, backgroundColor: '#f07a2b', borderRadius: { bottomLeft: 999, bottomRight: 999 }, borderSkipped: false },
                    { label: 'โปรตีน', data: energyProtein, backgroundColor: '#34aa80' },
                    { label: 'ไขมัน', data: energyFat, backgroundColor: '#d94578', borderRadius: { topLeft: 999, topRight: 999 }, borderSkipped: false },
                ],
            },
            plugins: [energyTargetGuide],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { position: 'bottom', align: 'start', labels: { usePointStyle: true, boxWidth: 8, padding: 18, font: { size: 14 } } } },
                scales: {
                    x: { stacked: true, border: { display: false }, grid: { display: false }, ticks: { font: { size: 14 } } },
                    y: { stacked: true, display: false, beginAtZero: true, min: 0, max: energyScaleMax },
                },
                datasets: { bar: { barThickness: 18, borderSkipped: false } },
            },
        });
    }
}

function healthChartOptions(showXLabels, beginAtZero = false) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 24, right: 14, left: 8 } },
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { display: false }, tooltip: { displayColors: false } },
        scales: {
            x: { border: { display: false }, grid: { display: false }, ticks: { display: showXLabels, autoSkip: true, maxTicksLimit: 7, minRotation: 0, maxRotation: 0, padding: 8, font: { size: 14 } } },
            y: { display: false, beginAtZero, min: beginAtZero ? 0 : undefined, grace: '15%' },
        },
    };
}

function patientLineChartOptions(xSuffix) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } } },
        scales: {
            x: { border: { display: false }, grid: { display: false }, ticks: { callback(value) { return `${xSuffix}${this.getLabelForValue(value)}`; } } },
            y: { border: { display: false }, grid: { color: 'rgba(117, 102, 107, .12)' } },
        },
    };
}
