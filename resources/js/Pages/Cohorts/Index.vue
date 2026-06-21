<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../Components/Layout/AppShell.vue';

const props = defineProps({
    programs: Array,
    can_manage_cohorts: { type: Boolean, default: false },
});

const page = usePage();
const copiedSlug = ref(null);
const showForm = ref(false);
const editingProgram = ref(null);
const deletingProgram = ref(null);

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'archived', label: 'Archived' },
];

const form = useForm({
    name: '',
    slug: '',
    description: '',
    status: 'draft',
    opens_at: '',
    closes_at: '',
    max_applications: '',
});

const isEditing = computed(() => editingProgram.value !== null);
const formTitle = computed(() => (isEditing.value ? 'Edit cohort' : 'New cohort'));

function blankForm() {
    return {
        name: '',
        slug: '',
        description: '',
        status: 'draft',
        opens_at: '',
        closes_at: '',
        max_applications: '',
    };
}

function toDateInput(iso) {
    if (!iso) return '';
    return iso.slice(0, 10);
}

function openCreate() {
    editingProgram.value = null;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function openEdit(program) {
    editingProgram.value = program;
    form.defaults({
        name: program.name,
        slug: program.slug,
        description: program.description ?? '',
        status: program.status,
        opens_at: toDateInput(program.opens_at),
        closes_at: toDateInput(program.closes_at),
        max_applications: program.max_applications ?? '',
    });
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingProgram.value = null;
    form.clearErrors();
}

function submitForm() {
    const payload = {
        ...form.data(),
        max_applications: form.max_applications === '' ? null : Number(form.max_applications),
        opens_at: form.opens_at || null,
        closes_at: form.closes_at || null,
    };

    if (isEditing.value) {
        form.transform(() => payload).put(`/cohorts/${editingProgram.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
        return;
    }

    form.transform(() => ({
        ...payload,
        slug: form.slug || undefined,
    })).post('/cohorts', {
        preserveScroll: true,
        onSuccess: () => closeForm(),
    });
}

function confirmDelete(program) {
    deletingProgram.value = program;
}

function cancelDelete() {
    deletingProgram.value = null;
}

function deleteProgram() {
    if (!deletingProgram.value) return;

    router.delete(`/cohorts/${deletingProgram.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingProgram.value = null;
        },
    });
}

function copyApplyUrl(url, slug) {
    navigator.clipboard.writeText(url);
    copiedSlug.value = slug;
    setTimeout(() => {
        copiedSlug.value = null;
    }, 2000);
}

function formatDate(iso) {
    if (!iso) return '—';
    return iso.slice(0, 10);
}
</script>

<template>
    <AppShell
        title="Cohorts"
        subtitle="Manage programs, share apply links, and review submissions per cohort."
    >
        <template v-if="can_manage_cohorts" #actions>
            <button type="button" class="vl-btn-primary" @click="openCreate">
                Add cohort
            </button>
        </template>

        <div v-if="page.props.flash?.success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <div v-if="page.props.errors?.delete" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ page.props.errors.delete }}
        </div>

        <div v-if="programs.length" class="space-y-4">
            <div
                v-for="program in programs"
                :key="program.id"
                class="vl-card p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-slate-900">{{ program.name }}</h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset"
                                :class="program.status === 'open'
                                    ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
                                    : 'bg-slate-100 text-slate-700 ring-slate-200'"
                            >
                                {{ program.status }}
                            </span>
                            <span
                                v-if="program.status === 'open' && !program.accepting"
                                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
                            >
                                Not accepting
                            </span>
                        </div>
                        <p v-if="program.description" class="mt-2 text-sm text-slate-600">{{ program.description }}</p>
                        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                            <div>
                                <dt class="text-slate-400">Applications</dt>
                                <dd class="font-semibold text-slate-900">
                                    {{ program.applications_count }}{{ program.max_applications ? ` / ${program.max_applications}` : '' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Opens</dt>
                                <dd class="font-medium">{{ formatDate(program.opens_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Closes</dt>
                                <dd class="font-medium">{{ formatDate(program.closes_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link :href="`/programs/${program.id}/applications`" class="vl-btn-primary text-sm">
                            View applications
                        </Link>
                        <template v-if="can_manage_cohorts">
                            <button type="button" class="vl-btn-secondary text-sm" @click="openEdit(program)">
                                Edit
                            </button>
                            <button
                                type="button"
                                class="vl-btn-secondary text-sm text-red-600 hover:border-red-200 hover:bg-red-50 hover:text-red-700"
                                @click="confirmDelete(program)"
                            >
                                Delete
                            </button>
                        </template>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500">Founder apply link</p>
                        <p class="mt-1 truncate font-mono text-sm text-slate-700">{{ program.apply_url }}</p>
                    </div>
                    <button
                        type="button"
                        class="vl-btn-secondary shrink-0 text-sm"
                        @click="copyApplyUrl(program.apply_url, program.slug)"
                    >
                        {{ copiedSlug === program.slug ? 'Copied!' : 'Copy link' }}
                    </button>
                    <a :href="program.apply_url" target="_blank" rel="noopener" class="vl-btn-ghost shrink-0 text-sm">
                        Preview
                    </a>
                </div>
            </div>
        </div>

        <div v-else class="vl-card border-dashed p-12 text-center">
            <p class="text-slate-600">No cohorts yet.</p>
            <p class="mt-2 text-sm text-slate-400">
                <template v-if="can_manage_cohorts">
                    Create your first cohort to start collecting applications.
                </template>
                <template v-else>
                    The Onboarding Agent creates your first program after signup, or run
                    <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan agents:run-onboarding</code>.
                </template>
            </p>
            <button
                v-if="can_manage_cohorts"
                type="button"
                class="vl-btn-primary mt-6"
                @click="openCreate"
            >
                Add cohort
            </button>
        </div>

        <!-- Create / edit modal -->
        <Teleport to="body">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
            >
                <div class="absolute inset-0 bg-slate-900/50" @click="closeForm" />
                <div class="relative z-10 w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-card-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ formTitle }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ isEditing ? 'Update cohort details and intake window.' : 'Set up a new program and apply link.' }}
                            </p>
                        </div>
                        <button type="button" class="vl-btn-ghost px-2 py-1 text-slate-400" @click="closeForm">
                            ✕
                        </button>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submitForm">
                        <div>
                            <label class="block text-sm font-medium">Name</label>
                            <input v-model="form.name" required class="vl-input mt-1.5" placeholder="Summer 2026 Cohort" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">
                                Apply link slug
                                <span v-if="!isEditing" class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <div class="mt-1.5 flex items-center gap-2">
                                <span class="shrink-0 text-sm text-slate-400">/apply/</span>
                                <input
                                    v-model="form.slug"
                                    :required="isEditing"
                                    class="vl-input"
                                    :placeholder="isEditing ? '' : 'auto-generated from name'"
                                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                />
                            </div>
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="vl-input mt-1.5 resize-y"
                                placeholder="What founders should know about this cohort."
                            />
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <select v-model="form.status" class="vl-input mt-1.5">
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Max applications</label>
                                <input
                                    v-model="form.max_applications"
                                    type="number"
                                    min="1"
                                    class="vl-input mt-1.5"
                                    placeholder="No limit"
                                />
                                <p v-if="form.errors.max_applications" class="mt-1 text-sm text-red-600">{{ form.errors.max_applications }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium">Opens</label>
                                <input v-model="form.opens_at" type="date" class="vl-input mt-1.5" />
                                <p v-if="form.errors.opens_at" class="mt-1 text-sm text-red-600">{{ form.errors.opens_at }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Closes</label>
                                <input v-model="form.closes_at" type="date" class="vl-input mt-1.5" />
                                <p v-if="form.errors.closes_at" class="mt-1 text-sm text-red-600">{{ form.errors.closes_at }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="button" class="vl-btn-secondary" @click="closeForm">
                                Cancel
                            </button>
                            <button type="submit" class="vl-btn-primary" :disabled="form.processing">
                                {{ isEditing ? 'Save changes' : 'Create cohort' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete confirmation -->
        <Teleport to="body">
            <div
                v-if="deletingProgram"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
            >
                <div class="absolute inset-0 bg-slate-900/50" @click="cancelDelete" />
                <div class="relative z-10 w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-card-lg">
                    <h2 class="text-lg font-semibold text-slate-900">Delete cohort?</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        <span class="font-medium text-slate-900">{{ deletingProgram.name }}</span>
                        will be permanently removed.
                        <template v-if="deletingProgram.applications_count > 0">
                            It has {{ deletingProgram.applications_count }} application(s) — delete is blocked; archive the cohort instead.
                        </template>
                        <template v-else>
                            This cannot be undone.
                        </template>
                    </p>
                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <button type="button" class="vl-btn-secondary" @click="cancelDelete">
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="vl-btn-primary bg-red-600 hover:bg-red-700 focus:ring-red-500"
                            :disabled="deletingProgram.applications_count > 0"
                            @click="deleteProgram"
                        >
                            Delete cohort
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppShell>
</template>
