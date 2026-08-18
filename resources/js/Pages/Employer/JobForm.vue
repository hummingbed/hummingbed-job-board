<script
    setup>    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; import { Head, Link, useForm } from '@inertiajs/vue3'; const p = defineProps({ job: { type: Object, default: null }, companies: Array, categories: Array }); const f = useForm({ company_id: p.job?.company_id || p.companies[0]?.id || '', job_category_id: p.job?.job_category_id || '', title: p.job?.title || '', description: p.job?.description || '', responsibilities: p.job?.responsibilities || '', requirements: p.job?.requirements || '', employment_type: p.job?.employment_type || 'full_time', workplace_type: p.job?.workplace_type || 'onsite', city: p.job?.city || '', country: p.job?.country || '', salary_min: p.job?.salary_min || '', salary_max: p.job?.salary_max || '', application_deadline: p.job?.application_deadline || '', application_type: p.job?.application_type || 'internal', external_url: p.job?.external_url || '' }); const submit = () => p.job ? f.patch(route('employer.jobs.update', p.job.id)) : f.post(route('employer.jobs.store'));</script>
<template>

    <Head :title="job ? 'Edit job' : 'Post a job'" />
    <AuthenticatedLayout><template #header>
            <div>
                <Link :href="route('employer.workspace')" class="text-sm font-bold text-[#309689]">← Workspace</Link>
                <h2 class="mt-1 text-2xl font-bold">{{ job ? 'Edit job listing' : 'Create job listing' }}</h2>
            </div>
        </template>
        <form @submit.prevent="submit" class="mx-auto grid max-w-5xl gap-6 px-4 py-10 lg:grid-cols-3">
            <section class="space-y-5 rounded-2xl bg-white p-7 shadow-sm lg:col-span-2"><label
                    v-for="x in [['title', 'Job title'], ['city', 'City'], ['country', 'Country'], ['salary_min', 'Minimum salary'], ['salary_max', 'Maximum salary'], ['application_deadline', 'Application deadline']]"
                    :key="x[0]" class="block"><span class="mb-2 block text-sm font-semibold">{{ x[1] }}</span><input
                        v-model="f[x[0]]"
                        :type="x[0].includes('salary') ? 'number' : x[0].includes('deadline') ? 'date' : 'text'"
                        :required="x[0] === 'title'" class="w-full rounded-lg border-gray-300"><small
                        class="text-red-600">{{ f.errors[x[0]] }}</small></label><label
                    v-for="x in [['description', 'Description'], ['responsibilities', 'Responsibilities'], ['requirements', 'Requirements']]"
                    :key="x[0]" class="block"><span class="mb-2 block text-sm font-semibold">{{ x[1] }}</span><textarea
                        v-model="f[x[0]]" rows="5" :required="x[0] === 'description'"
                        class="w-full rounded-lg border-gray-300"></textarea></label></section>
            <aside class="space-y-5 rounded-2xl bg-white p-7 shadow-sm"><label class="block"><span>Company</span><select
                        v-model="f.company_id" class="mt-2 w-full rounded-lg border-gray-300">
                        <option v-for="c in companies" :value="c.id">{{ c.name }}</option>
                    </select></label><label class="block"><span>Category</span><select v-model="f.job_category_id"
                        class="mt-2 w-full rounded-lg border-gray-300">
                        <option value="">Uncategorized</option>
                        <option v-for="c in categories" :value="c.id">{{ c.name }}</option>
                    </select></label><label
                    v-for="x in [['employment_type', ['full_time', 'part_time', 'freelance', 'contract']], ['workplace_type', ['onsite', 'remote', 'hybrid']], ['application_type', ['internal', 'external']]]"
                    :key="x[0]" class="block"><span class="capitalize">{{ x[0].replace('_', ' ') }}</span><select
                        v-model="f[x[0]]" class="mt-2 w-full rounded-lg border-gray-300">
                        <option v-for="v in x[1]" :value="v">{{ v.replace('_', ' ') }}</option>
                    </select></label><label v-if="f.application_type === 'external'" class="block"><span>External
                        URL</span><input v-model="f.external_url" type="url"
                        class="mt-2 w-full rounded-lg border-gray-300"></label><button :disabled="f.processing"
                    class="w-full rounded-lg bg-[#309689] py-3 font-bold text-white">{{ job ? 'Save changes' : 'Submit for review' }}</button></aside>
        </form>
    </AuthenticatedLayout>
</template>
