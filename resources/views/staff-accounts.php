<?php
/** @var bool $ok */
/** @var \Illuminate\Support\Collection<int, \App\Models\User> $staffAccounts */
/** @var \Illuminate\Support\ViewErrorBag $errors */
$pageTitle = 'Staff Accounts';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<?php if (session('status')): ?><div class="card text-green-400 mb-4"><?= e(session('status')) ?></div><?php endif; ?>
<?php if ($errors->any()): ?><div class="card text-red-300 mb-4"><?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="POST" action="<?= e(route('staff.store')) ?>" class="card mb-6">
    <?= csrf_field() ?>
    <h2 class="font-semibold mb-3">Add Staff Account</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div><label class="lbl" for="first_name">First name</label><input id="first_name" type="text" name="first_name" value="<?= e(old('first_name')) ?>" class="inp" autocomplete="given-name" required></div>
        <div><label class="lbl" for="middle_name">Middle name</label><input id="middle_name" type="text" name="middle_name" value="<?= e(old('middle_name')) ?>" class="inp" autocomplete="additional-name"></div>
        <div><label class="lbl" for="last_name">Last name</label><input id="last_name" type="text" name="last_name" value="<?= e(old('last_name')) ?>" class="inp" autocomplete="family-name" required></div>
        <div><label class="lbl" for="username">Username</label><input id="username" type="text" name="username" value="<?= e(old('username')) ?>" class="inp" autocomplete="username" required></div>
        <div>
            <label class="lbl" for="email">Staff Gmail address</label>
            <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" class="inp" autocomplete="email" placeholder="name@gmail.com" required>
            <p class="text-xs text-gray-500 mt-1">Only @gmail.com addresses are accepted. We will email the username and a one-time password setup link.</p>
        </div>
    </div>
    <div class="flex justify-between items-end mt-4">
        <div class="text-xs"><p class="mb-2">Staff can open</p>
            <?php foreach(['Dashboard','Record sale','Products (view only)','Own transactions'] as $p): ?>
                <span class="inline-block px-3 py-1 mr-1 rounded-full bg-[#0b0e0b] border border-[#1f271f]"><?= e($p) ?></span>
            <?php endforeach; ?>
            <p class="text-gray-500 mt-2">Not included: Sales reports, Monthly records, Staff accounts</p></div>
        <div class="space-x-2"><button type="reset" class="btn-dark">Clear</button><button type="submit" class="btn">Create Account</button></div>
    </div>
</form>
<div class="card overflow-x-auto">
    <p id="staff-status-feedback" class="sr-only" role="status" aria-live="polite"></p>
    <div class="flex justify-end mb-3">
        <label class="flex items-center gap-2 text-sm text-gray-400" for="staff-status-filter">
            Account status
            <select id="staff-status-filter" class="inp !w-auto">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </label>
    </div>
    <table class="tbl"><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th style="text-align:right">Action</th></tr></thead>
        <tbody id="staff-account-rows" data-status-url="<?= e(route('staff.statuses')) ?>">
        <?php foreach ($staffAccounts as $staffAccount): ?>
            <tr data-staff-id="<?= e($staffAccount->id) ?>" data-account-active="<?= $staffAccount->is_active ? 'true' : 'false' ?>">
                <td class="font-semibold"><?= e($staffAccount->name) ?></td>
                <td><?= e($staffAccount->username) ?></td>
                <td><?= e($staffAccount->email) ?></td>
                <td>Staff</td>
                <td data-staff-status class="<?= $staffAccount->must_set_password ? 'text-amber-400' : ($staffAccount->is_active ? 'text-green-500' : 'text-gray-400') ?>"><?= $staffAccount->must_set_password ? 'Setup pending' : ($staffAccount->is_active ? 'Active' : 'Inactive') ?></td>
                <td>
                    <div class="relative flex justify-end" data-action-menu-container>
                        <button type="button" class="btn-dark !rounded-lg !p-2" data-action-menu-toggle aria-label="Account actions for <?= e($staffAccount->name) ?>" aria-haspopup="true" aria-controls="staff-actions-<?= e($staffAccount->id) ?>" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/>
                            </svg>
                        </button>
                        <div id="staff-actions-<?= e($staffAccount->id) ?>" class="hidden fixed z-[80] min-w-48 rounded-lg border border-[#1f271f] bg-[#141914] p-1 shadow-xl" data-action-menu>
                            <form method="POST" action="<?= e(route('staff.setup-email', $staffAccount)) ?>" data-staff-setup-form class="<?= $staffAccount->must_set_password ? '' : 'hidden' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-[#1f271f]">Resend Setup Email</button>
                            </form>
                            <form method="POST" action="<?= e(route('staff.status', $staffAccount)) ?>" data-staff-status-form class="<?= $staffAccount->must_set_password ? 'hidden' : '' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="PATCH">
                                <input type="hidden" name="is_active" value="<?= $staffAccount->is_active ? '0' : '1' ?>">
                                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-[#1f271f]" data-staff-status-button><?= $staffAccount->is_active ? 'Mark Inactive' : 'Mark Active' ?></button>
                            </form>
                            <form method="POST" action="<?= e(route('staff.password', $staffAccount)) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-[#1f271f]">Reset Password</button>
                            </form>
                            <form method="POST" action="<?= e(route('staff.destroy', $staffAccount)) ?>" id="delete-staff-<?= e($staffAccount->id) ?>" data-delete-form class="<?= $staffAccount->is_active ? 'hidden' : '' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="button" class="w-full rounded-md px-3 py-2 text-left text-sm text-red-300 hover:bg-[#3a1c20]" data-delete-account="<?= e($staffAccount->id) ?>" data-account-name="<?= e($staffAccount->name) ?>" data-account-username="<?= e($staffAccount->username) ?>" aria-label="Delete account for <?= e($staffAccount->name) ?>">
                                    Delete Account
                                </button>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr id="staff-no-accounts" class="<?= $staffAccounts->isEmpty() ? '' : 'hidden' ?>"><td colspan="6" class="text-gray-400 text-center" style="padding:3rem 0"><?= $staffAccounts->isEmpty() ? 'No staff accounts yet. Create an account using the form above.' : 'No active staff accounts.' ?></td></tr>
        </tbody>
    </table>
</div>
<div id="deleteAccountModal" class="hidden fixed inset-0 z-[60] items-center justify-center p-4 backdrop-blur-sm" style="background:rgba(4,7,4,.82)" role="dialog" aria-modal="true" aria-labelledby="deleteAccountModalTitle" aria-describedby="deleteAccountModalDescription">
    <div class="card w-full max-w-md" style="background:#141914;border-color:#422328;padding:24px;box-shadow:0 24px 80px rgba(0,0,0,.55)">
        <h2 id="deleteAccountModalTitle" class="text-xl font-bold">Delete this account?</h2>
        <p id="deleteAccountModalDescription" class="mt-2 text-sm leading-6 text-gray-400">Permanently delete this staff account? This cannot be undone.</p>
        <div class="mt-5 rounded-lg border border-[#252d25] bg-[#0b0e0b] px-4 py-3">
            <p id="deleteAccountName" class="truncate text-sm font-semibold text-white"></p>
            <p id="deleteAccountUsername" class="mt-1 text-xs text-gray-400"></p>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" id="cancelDeleteAccount" class="btn-dark">Keep account</button>
            <button type="button" id="confirmDeleteAccount" class="btn-red">Delete account</button>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($ok): ob_start(); ?><script>
    const staffAccountRows = document.getElementById('staff-account-rows');
    const staffStatusFeedback = document.getElementById('staff-status-feedback');
    const staffStatusFilter = document.getElementById('staff-status-filter');
    function applyStaffStatusFilter() {
        const showActive = staffStatusFilter.value === 'active';
        const rows = [...staffAccountRows.querySelectorAll('tr[data-staff-id]')];
        let visibleCount = 0;
        rows.forEach(row => {
            const visible = (row.dataset.accountActive === 'true') === showActive;
            row.classList.toggle('hidden', !visible);
            if (visible) visibleCount++;
        });
        const emptyRow = document.getElementById('staff-no-accounts');
        emptyRow.classList.toggle('hidden', visibleCount > 0);
        if (visibleCount === 0 && rows.length > 0) {
            emptyRow.querySelector('td').textContent = `No ${staffStatusFilter.value} staff accounts.`;
        }
    }
    function updateStaffStatus(row, account) {
        row.querySelector('[data-staff-status]').textContent = account.must_set_password
            ? 'Setup pending'
            : (account.is_active ? 'Active' : 'Inactive');
        row.querySelector('[data-staff-status]').classList.remove('text-amber-400', 'text-green-500', 'text-gray-400');
        row.querySelector('[data-staff-status]').classList.add(account.must_set_password ? 'text-amber-400' : (account.is_active ? 'text-green-500' : 'text-gray-400'));
        row.querySelector('[data-staff-setup-form]').classList.toggle('hidden', !account.must_set_password);
        const statusForm = row.querySelector('[data-staff-status-form]');
        statusForm.classList.toggle('hidden', account.must_set_password);
        statusForm.querySelector('[name="is_active"]').value = account.is_active ? '0' : '1';
        statusForm.querySelector('[data-staff-status-button]').textContent = account.is_active ? 'Mark Inactive' : 'Mark Active';
        row.dataset.accountActive = String(account.is_active);
        row.querySelector('[data-delete-form]').classList.toggle('hidden', account.is_active);
        applyStaffStatusFilter();
    }
    staffStatusFilter.addEventListener('change', applyStaffStatusFilter);
    applyStaffStatusFilter();
    async function refreshStaffStatuses() {
        if (document.hidden) return;
        try {
            const response = await fetch(staffAccountRows.dataset.statusUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) throw new Error('Status refresh failed.');
            const accounts = await response.json();
            accounts.forEach(account => {
                const row = staffAccountRows.querySelector(`[data-staff-id="${account.id}"]`);
                if (row) updateStaffStatus(row, account);
            });
            staffStatusFeedback.textContent = '';
        } catch {
            staffStatusFeedback.textContent = 'Live status updates are temporarily unavailable. Retrying shortly.';
        }
    }
    staffAccountRows.addEventListener('submit', async event => {
        const form = event.target.closest('[data-staff-status-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-staff-status-button]');
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not update staff status.');
            updateStaffStatus(form.closest('[data-staff-id]'), result);
            staffStatusFeedback.textContent = 'Staff status updated.';
        } catch (error) {
            staffStatusFeedback.textContent = error.message || 'Could not update staff status. Please try again.';
        } finally {
            button.disabled = false;
        }
    });
    window.setInterval(refreshStaffStatuses, 5000);
    document.addEventListener('visibilitychange', refreshStaffStatuses);

    function closeActionMenus() {
        document.querySelectorAll('[data-action-menu]').forEach(menu => menu.classList.add('hidden'));
        document.querySelectorAll('[data-action-menu-toggle]').forEach(button => button.setAttribute('aria-expanded', 'false'));
    }
    function positionActionMenu(button, menu) {
        const buttonRect = button.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();
        let top = buttonRect.bottom + 8;
        if (top + menuRect.height > window.innerHeight - 8) {
            top = Math.max(8, buttonRect.top - menuRect.height - 8);
        }
        const left = Math.max(8, Math.min(window.innerWidth - menuRect.width - 8, buttonRect.right - menuRect.width));
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
    }
    document.querySelectorAll('[data-action-menu-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const menu = button.closest('[data-action-menu-container]').querySelector('[data-action-menu]');
            const willOpen = menu.classList.contains('hidden');
            closeActionMenus();
            menu.classList.toggle('hidden', !willOpen);
            if (willOpen) positionActionMenu(button, menu);
            button.setAttribute('aria-expanded', String(willOpen));
        });
    });
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.togglePassword);
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('[data-eye]').classList.toggle('hidden', !visible);
            button.querySelector('[data-eye-off]').classList.toggle('hidden', visible);
            button.setAttribute('aria-pressed', String(!visible));
            button.setAttribute('aria-label', visible
                ? button.getAttribute('aria-label').replace('Hide', 'Show')
                : button.getAttribute('aria-label').replace('Show', 'Hide'));
        });
    });
    document.addEventListener('click', event => {
        if (event.target.closest('[data-action-menu-container]')) return;
        closeActionMenus();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        closeActionMenus();
    });
    window.addEventListener('resize', closeActionMenus);
    window.addEventListener('scroll', closeActionMenus, true);
    const deleteAccountModal = document.getElementById('deleteAccountModal');
    let activeDeleteAccountForm = null;
    let activeDeleteAccountButton = null;
    function closeDeleteAccountModal() {
        deleteAccountModal.classList.add('hidden');
        deleteAccountModal.classList.remove('flex');
        if (activeDeleteAccountButton) activeDeleteAccountButton.focus();
        activeDeleteAccountForm = null;
        activeDeleteAccountButton = null;
    }
    document.querySelectorAll('[data-delete-account]').forEach(button => {
        button.addEventListener('click', () => {
            activeDeleteAccountButton = button;
            activeDeleteAccountForm = document.getElementById(`delete-staff-${button.dataset.deleteAccount}`);
            document.getElementById('deleteAccountName').textContent = button.dataset.accountName;
            document.getElementById('deleteAccountUsername').textContent = `@${button.dataset.accountUsername}`;
            deleteAccountModal.classList.remove('hidden');
            deleteAccountModal.classList.add('flex');
            document.getElementById('cancelDeleteAccount').focus();
        });
    });
    document.getElementById('cancelDeleteAccount').addEventListener('click', closeDeleteAccountModal);
    document.getElementById('confirmDeleteAccount').addEventListener('click', () => {
        if (activeDeleteAccountForm) activeDeleteAccountForm.requestSubmit();
    });
    deleteAccountModal.addEventListener('click', event => {
        if (event.target === deleteAccountModal) closeDeleteAccountModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !deleteAccountModal.classList.contains('hidden')) closeDeleteAccountModal();
    });
</script>
<?php $extraScript = ob_get_clean(); endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
