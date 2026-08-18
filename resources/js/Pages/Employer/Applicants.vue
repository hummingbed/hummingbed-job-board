<script
    setup>    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; import { Head, Link, useForm } from '@inertiajs/vue3'; defineProps({ job: Object }); const stages = ['submitted', 'reviewing', 'shortlisted', 'interview', 'offer', 'hired', 'rejected']; const update = (a, status) => useForm({ status, note: '' }).patch(route('employer.applications.status', a.id), { preserveScroll: true }); const interview = a => { const value = prompt('Interview date and time (YYYY-MM-DD HH:MM)'); if (value) useForm({ scheduled_at: value, type: 'video', location_or_url: 'https://meet.example.test/interview', notes: '' }).post(route('employer.interviews.store', a.id), { preserveScroll: true }); };</script>
<template>

    <Head :title="`${job.title} applicants`" />
    <AuthenticatedLayout><template #header>
            <div>
                <Link :href="route('employer.workspace')" class="text-sm font-bold text-[#309689]">← Workspace</Link>
                <h2 class="mt-1 text-2xl font-bold">{{ job.title }} applicants</h2>
                <p class="text-sm text-gray-500">{{ job.company.name }} · {{ job.applications.length }} applicants</p>
            </div>
        </template>
        <div class="mx-auto max-w-7xl px-4 py-10">
            <div v-if="$page.props.flash.success" class="mb-6 rounded-xl bg-emerald-50 p-4 text-emerald-800">
                {{ $page.props.flash.success }}</div>
            <div class="grid gap-5">
                <article v-for="a in job.applications" :key="a.id" class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex flex-col justify-between gap-5 lg:flex-row">
                        <div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid size-12 place-items-center rounded-full bg-[#ebf5f4] font-bold text-[#309689]">
                                    {{ a.candidate.name[0] }}</div>
                                <div>
                                    <h3 class="font-bold">{{ a.candidate.name }}</h3>
                                    <p class="text-sm text-gray-500">{{ a.candidate.email }}</p>
                                </div>
                            </div>
                            <p class="mt-4 max-w-2xl text-sm text-gray-600">{{ a.cover_letter || 'No cover letter was provided.' }}
                            </p>
                            <div v-if="a.history.length" class="mt-4 text-xs text-gray-400">Last updated:
                                {{ a.history[a.history.length - 1].to_status }}</div>
                        </div>
                        <div class="min-w-64"><label class="text-xs font-bold uppercase text-gray-500">Pipeline
                                stage</label><select :value="a.status" @change="update(a, $event.target.value)"
                                class="mt-2 w-full rounded-lg border-gray-300">
                                <option v-for="s in stages" :value="s">{{ s }}</option>
                            </select><button @click="interview(a)"
                                class="mt-3 w-full rounded-lg bg-[#309689] py-2 text-sm font-bold text-white">Schedule
                                interview</button></div>
                    </div>
                </article>
                <div v-if="!job.applications.length" class="rounded-2xl bg-white p-12 text-center text-gray-500">No
                    applications
                    have been received yet.</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
