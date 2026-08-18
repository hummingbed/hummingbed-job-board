<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Plan;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'Admin User', 'role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')]);
        $employer = User::updateOrCreate(['email' => 'employer@example.com'], ['name' => 'Employer User', 'role' => 'employer', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')]);
        $employerTwo = User::updateOrCreate(['email' => 'recruiter@example.com'], ['name' => 'Recruiter User', 'role' => 'employer', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')]);
        $candidate = User::updateOrCreate(['email' => 'candidate@example.com'], ['name' => 'Candidate User', 'role' => 'candidate', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')]);
        $candidateTwo = User::updateOrCreate(['email' => 'amara@example.com'], ['name' => 'Amara Okafor', 'role' => 'candidate', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')]);

        DB::table('candidate_profiles')->updateOrInsert(['user_id' => $candidate->id], ['headline' => 'Frontend Engineer', 'bio' => 'Vue developer focused on accessible, dependable web products.', 'phone' => '+234 800 000 0001', 'city' => 'Lagos', 'country' => 'Nigeria', 'visibility' => 'public', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('candidate_profiles')->updateOrInsert(['user_id' => $candidateTwo->id], ['headline' => 'Customer Success Specialist', 'bio' => 'Customer advocate with experience supporting growing technology teams.', 'phone' => '+234 800 000 0002', 'city' => 'Abuja', 'country' => 'Nigeria', 'visibility' => 'employers', 'created_at' => now(), 'updated_at' => now()]);

        $companies = collect([
            ['hummingbed-talent', $employer->id, 'Hummingbed Talent', 'Technology', 'Lagos', true],
            ['northstar-finance', $employerTwo->id, 'Northstar Finance', 'Financial Services', 'Lagos', true],
            ['savanna-hospitality', $employerTwo->id, 'Savanna Hospitality', 'Hotels & Tourism', 'Abuja', false],
        ])->mapWithKeys(function ($item) {
            [$slug,$owner,$name,$industry,$city,$featured] = $item;
            $company = Company::updateOrCreate(['slug' => $slug], ['owner_id' => $owner, 'name' => $name, 'email' => 'jobs@'.$slug.'.test', 'website' => 'https://'.$slug.'.test', 'industry' => $industry, 'size' => '51-200', 'city' => $city, 'country' => 'Nigeria', 'description' => $name.' builds thoughtful teams and offers meaningful career opportunities.', 'verification_status' => 'verified', 'is_featured' => $featured]);
            $company->members()->syncWithoutDetaching([$owner => ['role' => 'owner']]);
            return [$slug => $company];
        });

        foreach ([['Technology','technology'],['Commerce','commerce'],['Hotels & Tourism','hotels-tourism'],['Education','education'],['Financial Services','financial-services'],['Construction','construction']] as [$name,$slug]) JobCategory::firstOrCreate(['slug' => $slug], ['name' => $name]);
        $categories = JobCategory::pluck('id', 'slug');
        $skills = collect(['Vue.js','Laravel','JavaScript','PHP','Customer Success','Communication','Financial Analysis','Figma','SQL','Project Management'])->mapWithKeys(fn ($name) => [$name => Skill::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)])]);

        $definitions = [
            ['corporate-solutions-executive','Corporate Solutions Executive','hummingbed-talent','commerce','Lagos','hybrid',40000,52000,'published'],
            ['senior-frontend-engineer','Senior Frontend Engineer','hummingbed-talent','technology','Remote','remote',65000,85000,'published'],
            ['customer-success-manager','Customer Success Manager','hummingbed-talent','commerce','Abuja','hybrid',35000,48000,'published'],
            ['hotel-operations-lead','Hotel Operations Lead','savanna-hospitality','hotels-tourism','Abuja','onsite',45000,60000,'published'],
            ['finance-operations-analyst','Finance Operations Analyst','northstar-finance','financial-services','Lagos','hybrid',38000,52000,'published'],
            ['learning-experience-designer','Learning Experience Designer','hummingbed-talent','education','Remote','remote',42000,58000,'published'],
            ['product-design-lead','Product Design Lead','hummingbed-talent','technology','Lagos','hybrid',70000,90000,'pending'],
            ['site-project-coordinator','Site Project Coordinator','savanna-hospitality','construction','Abuja','onsite',32000,45000,'draft'],
        ];
        $jobs = collect($definitions)->mapWithKeys(function ($d) use ($companies, $categories, $employer, $employerTwo, $skills) {
            [$slug,$title,$company,$category,$city,$workplace,$min,$max,$status] = $d;
            $job = JobListing::updateOrCreate(['slug' => $slug], ['company_id' => $companies[$company]->id, 'created_by' => $companies[$company]->owner_id, 'job_category_id' => $categories[$category], 'title' => $title, 'description' => 'Join a collaborative team solving practical problems for growing organisations. You will own meaningful work and help customers succeed.', 'responsibilities' => "Own key projects\nCommunicate progress across teams\nContinuously improve delivery", 'requirements' => "Relevant professional experience\nStrong written and verbal communication\nSound judgement and attention to detail", 'employment_type' => 'full_time', 'workplace_type' => $workplace, 'experience_level' => 'Mid level', 'education_level' => 'Bachelor', 'city' => $city, 'country' => 'Nigeria', 'salary_min' => $min, 'salary_max' => $max, 'currency' => 'USD', 'salary_visible' => true, 'openings' => 2, 'application_deadline' => now()->addDays(45), 'application_type' => 'internal', 'status' => $status, 'is_featured' => in_array($slug, ['senior-frontend-engineer','finance-operations-analyst']), 'published_at' => $status === 'published' ? now()->subDays(random_int(1, 14)) : null]);
            $job->skills()->syncWithoutDetaching($category === 'technology' ? [$skills['Vue.js']->id,$skills['Laravel']->id,$skills['JavaScript']->id] : [$skills['Communication']->id,$skills['Project Management']->id]);
            return [$slug => $job];
        });

        if (! Storage::exists('resumes/demo-candidate-resume.txt')) Storage::put('resumes/demo-candidate-resume.txt', "Demo résumé for Candidate User\nFrontend Engineer\nVue.js, Laravel, JavaScript");
        if (! Storage::exists('resumes/demo-amara-resume.txt')) Storage::put('resumes/demo-amara-resume.txt', "Demo résumé for Amara Okafor\nCustomer Success Specialist\nCommunication, Customer Success, Project Management");
        $resume = Resume::firstOrCreate(['user_id' => $candidate->id, 'name' => 'Frontend Engineer Résumé'], ['path' => 'resumes/demo-candidate-resume.txt', 'is_default' => true]);
        $resumeTwo = Resume::firstOrCreate(['user_id' => $candidateTwo->id, 'name' => 'Customer Success Résumé'], ['path' => 'resumes/demo-amara-resume.txt', 'is_default' => true]);
        $application = Application::firstOrCreate(['job_listing_id' => $jobs['finance-operations-analyst']->id, 'candidate_id' => $candidate->id], ['resume_id' => $resume->id, 'cover_letter' => 'I would love to bring my product and analytical experience to this role.', 'status' => 'shortlisted']);
        $applicationTwo = Application::firstOrCreate(['job_listing_id' => $jobs['customer-success-manager']->id, 'candidate_id' => $candidateTwo->id], ['resume_id' => $resumeTwo->id, 'cover_letter' => 'Customer advocacy and structured problem-solving are at the centre of my work.', 'status' => 'interview']);
        DB::table('application_status_history')->updateOrInsert(['application_id' => $application->id, 'to_status' => 'shortlisted'], ['changed_by' => $employerTwo->id, 'from_status' => 'submitted', 'note' => 'Strong initial application.', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('application_notes')->updateOrInsert(['application_id' => $application->id, 'user_id' => $employerTwo->id], ['body' => 'Review portfolio during the next call.', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('saved_jobs')->insertOrIgnore([['user_id' => $candidate->id, 'job_listing_id' => $jobs['senior-frontend-engineer']->id, 'created_at' => now(), 'updated_at' => now()], ['user_id' => $candidateTwo->id, 'job_listing_id' => $jobs['hotel-operations-lead']->id, 'created_at' => now(), 'updated_at' => now()]]);
        $alert = DB::table('job_alerts')->where('user_id', $candidate->id)->where('name', 'Remote technology roles')->first();
        if (! $alert) { $alertId = DB::table('job_alerts')->insertGetId(['user_id' => $candidate->id, 'name' => 'Remote technology roles', 'filters' => json_encode(['keyword' => 'Engineer', 'location' => 'Remote']), 'frequency' => 'daily', 'active' => true, 'created_at' => now(), 'updated_at' => now()]); } else $alertId = $alert->id;
        DB::table('interviews')->updateOrInsert(['application_id' => $applicationTwo->id], ['scheduled_at' => now()->addDays(3), 'type' => 'video', 'location_or_url' => 'https://meet.example.test/customer-success', 'notes' => 'Meet the hiring manager.', 'status' => 'scheduled', 'created_at' => now(), 'updated_at' => now()]);

        $plan = Plan::where('name', 'Growth')->firstOrFail();
        Subscription::firstOrCreate(['company_id' => $companies['hummingbed-talent']->id, 'status' => 'active'], ['plan_id' => $plan->id, 'dummy_reference' => 'DUMMY-SEED-GROWTH', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        if (DB::table('reports')->doesntExist()) DB::table('reports')->insert(['reporter_id' => $candidate->id, 'reportable_type' => JobListing::class, 'reportable_id' => $jobs['hotel-operations-lead']->id, 'reason' => 'Incorrect location', 'details' => 'Please confirm whether this role is in Abuja.', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        if (DB::table('audit_logs')->doesntExist()) DB::table('audit_logs')->insert(['user_id' => $admin->id, 'action' => 'demo.seeded', 'auditable_type' => Company::class, 'auditable_id' => $companies['hummingbed-talent']->id, 'metadata' => json_encode(['source' => 'DemoMarketplaceSeeder']), 'ip_address' => '127.0.0.1', 'created_at' => now(), 'updated_at' => now()]);
        if (DB::table('analytics_events')->doesntExist()) foreach ($jobs->take(4) as $job) DB::table('analytics_events')->insert(['user_id' => $candidate->id, 'event' => 'job_viewed', 'subject_type' => JobListing::class, 'subject_id' => $job->id, 'properties' => json_encode(['source' => 'seed']), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('newsletter_subscribers')->updateOrInsert(['email' => 'subscriber@example.com'], ['subscribed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        if (DB::table('contact_messages')->doesntExist()) DB::table('contact_messages')->insert(['job_listing_id' => $jobs['senior-frontend-engineer']->id, 'name' => 'Demo Visitor', 'email' => 'visitor@example.com', 'phone' => '+234 800 000 0010', 'message' => 'Is this position open to candidates across Africa?', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([$admin,$employer,$employerTwo,$candidate,$candidateTwo] as $user) DB::table('notification_preferences')->updateOrInsert(['user_id' => $user->id], ['in_app' => true, 'email' => true, 'application_updates' => true, 'job_alerts' => true, 'interview_reminders' => true, 'created_at' => now(), 'updated_at' => now()]);
        if (DB::table('notifications')->doesntExist()) DB::table('notifications')->insert(['id' => (string) Str::uuid(), 'type' => 'App\\Notifications\\ApplicationStatusChangedNotification', 'notifiable_type' => User::class, 'notifiable_id' => $candidate->id, 'data' => json_encode(['kind' => 'application_status', 'title' => 'Application updated', 'message' => 'Your demo application was shortlisted.', 'url' => route('candidate.workspace')]), 'read_at' => null, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_alert_deliveries')->insertOrIgnore(['job_alert_id' => $alertId, 'job_listing_id' => $jobs['senior-frontend-engineer']->id, 'delivered_at' => now()]);
    }
}
